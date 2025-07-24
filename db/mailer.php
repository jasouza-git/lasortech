<?php
require 'vendor/autoload.php';
require_once "basic.php";
require_once "query.php";
require_once "insert.php";
require_once "delete.php";
use \Mailjet\Resources;

$email = (object) [
    'public_key' => '533947a9454170d1674c07221018e073',
    'private_key' => 'd844ad72e858c622ebccf46511e04c75',
    'email' => 'eqix.experiment@gmail.com'
];

class Mailer {

    private $client;
    private $query;
    private $insert;
    private $delete;

    public function __construct($conn) {
        global $email;
        $this->client = new \Mailjet\Client(
            $email->public_key,
            $email->private_key,
            true,
            ['version' => 'v3.1']
        );

        $this->client->setConnectionTimeout(30);

        $this->query = new DB_QUERY($conn);
        $this->insert = new DB_INSERT($conn);
        $this->delete = new DB_DELETE($conn);
    }

    /**
     * send the verification code for email authorization
     * @param array $data
     *      - email: the email you want send the code
     *      - check_exist: 
     *          if true: will check the email in database first, and the email must be exist
     *          if false: will check the email in database first, and the email must be not exist
     *          if null: will not check the database
     * @return bool true on success. On failure, the function calls die() and report error.
     */
    public function send_verification_code(array $data) {
        $paras = parameter([
            "email" => "string",
            "check_exist?" => "bool"
        ], $data);

        $error_title = "Email Verfication Send Failed";

        if (isset($paras['check_exist'])) {
            $check_exist = $paras['check_exist'];
            $users_check = $this->query->get([
                "ids" => $paras['email']
            ], "users", "email");
            if ($check_exist) {
                required(count($users_check) && $users_check[0], 50, $error_title, "user not exist.");
            } else {
                required(count($users_check) == 0, 25, $error_title, "user existed.");
            }
        }

        $code = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);

        $this->delete->delete([
                "ids" => [$paras['email']],
            ], "email_validations", "email");

        $this->insert->email_verification([
            "email" => $paras["email"],
            "code" => $code
        ]);

        $body = <<<HTML
            <div style="text-align: center">
                <h1>Your Verification Code</h1>
                <p>Please use the following code to complete your verification:</p>
                <code>$code</code>
                <p>If you did not request this code, you can safely ignore this email.</p>
            </div>
        HTML;


        return $this->send(
            $paras['email'],
            "New User",
            "Your Verification Code from Lasortech",
            str_replace('<!-- CONTENT -->', $body, file_get_contents(__DIR__ . "/email.html"))
        );

    }

    /**
     * send the order details to its customer
     * @param array $data
     *      - id: the order's id you want send
     *      - message?: the addtion message you want send to the customer
     * @return bool true on success. On failure, the function calls die() and report error.
     */
    public function send_order(array $data) {

        $error_title = "Order Email Send Failed";

        $paras = parameter([
            "id" => "string",
            "message?" => "string"
        ], $data);

        $order_id = $paras['id'];
        $message = $paras['message'] ?? "";
        if (!strlen($message)) $message = "We're Working on Your Device - Here's the Latest";

        // Get Order
        $orders = $this->query->fetch_orders([
            "ids" => [$order_id]
        ]);
        required(count($orders) == 1, 60, $error_title, "order id not valid.");
        $order = $orders[0];
        

        $item_rows = "";
        foreach ($order['items'] as $i => $item) {
            $index = $i + 1;
            $name = $item['name'] ?? "N/A";
            $brand = $item['brand'] ?? "N/A";
            $model = $item["model"] ?? "N/A";
            $serial = $item['serial'];
            $attr = $index % 2 ? '' : ' class="dark"';

            $item_rows .= <<<HTML
            <tr$attr>
                <td>$index</td>
                <td>$name</td>
                <td>$brand</td>
                <td>$model</td>
                <td>$serial</td>
            </tr>
            HTML;
        }

        $states = $this->query->states([
            "order_ids" => [$order_id]
        ]);
        $state_rows = "";
        $n = 0;
        $len_states = count($states);
        foreach ($states as $state) {
            $tag = ($n == 0 ? "first" : "") . ($n+1 == $len_states ? " last" : "");
            $cont = isset($state['reason']) ? $state['reason'] : (isset($state['amount']) ? 'Customer paid ' . $state['amount'] : "");
            if (strlen($cont)) $cont = '<div class="body">' . $cont . '</div>';
            $date = (new DateTime($state['create_at']))->format('F j, Y h:i:s A');
            $state_rows .= <<<HTML
                <div class="$tag">
                    <div class="num n{$state['state_code']}" style="background-color:{$state['color']}"></div>
                    <span class="name">{$state['label']}</span>
                    <span class="info">
                        <span class="date">{$date}</span>
                        <span class="employee">...</span>
                    </span>
                    $cont
                </div>
            HTML;
            $n++;
        }

        $body =<<<HTML
            <div>
                <h2>Customer Information</h2>
                <div>
                    <p><span>Customer:</span> {$order['customer']['name']}</p>
                    <p><span>Email:</span> {$order['customer']['email']}</p>
                </div>
            </div>
            <div>
                <h2>Order Details</h2>
                <div>
                    <p><span>Order No.:</span> {$order['id']}</p>
                    <p><span>RMS Code:</span> {$order['rms_code']}</p>
                    <p><span>Status:</span> <span style="color:{$order['state']['color']}">{$order['state']['label']}</span></p>
                    <p><span>Last Update</span> {$order['update_at']}</p>
                </div>
            </div>
            <div>
                <h2>Items in Service</h2>
                <table>
                    <thead><tr>
                        <th class="first">#</th>
                        <th>Name</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th class="last">Serial</th>
                    </tr></thead>
                    <tbody>
                        $item_rows
                    </tbody>
                </table>
            </div>
            <div>
                <div class="state">
                    $state_rows
                </div>
            </div>
        HTML;

        return $this->send(
            $order['customer']['email'],
            $order['customer']['name'],
            $message,
            str_replace('<!-- CONTENT -->', $body, file_get_contents(__DIR__ . "/email.html"))
            //$mail_html
        );
    }

    /**
     * send any email to any given email address
     * @param array $data
     *      - email: the destination email
     *      - to_name: the destination name, will shown in there email client
     *      - subject: email subject
     *      - body: the html content body
     * @return bool true on success. On failure, the function calls die() and report error.
     */
    public function send_email(array $data) {
        $paras = parameter([
            "email" => "string",
            "to_name" => "string",
            "subject" => "string",
            "body" => "string"
        ], $data);

        return $this->send(
            $paras['email'],
            $paras['to_name'],
            $paras['subject'],
            $paras['body']
        );
    }

    /**
     * send any email to any given email address
     * @param array $to: the destination email
     * @param array $to_name: the destination name, will shown in there email client
     * @param array $subject: email subject
     * @param array $body: the html content body
     * @return bool true on success. On failure, the function calls die() and report error.
     */
    private function send(string $to, string $to_name, string $subject, string $body) {

        $internal_error_title = "Email Send Failed";
        $error_title = "Email Send Failed";

        global $email;
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $email->email,
                        'Name' => "Lasortech"
                    ],
                    'To' => [
                        [
                            'Email' => $to,
                            'Name' => $to_name
                        ]
                    ],
                    'Subject' => $subject,
                    'HTMLPart' => $body
                ]
            ]
        ];

        $post_res = handleException(
            fn() => $this->client->post(Resources::$Email, ['body' => $body]),
            "possible cause - no internet connectivity. please verify network settings."
        );

        if ($post_res["error"]) {
            required(false, 56, $error_title, $post_res['trace']);
        } else {
            $response = $post_res['result'];
        }

        if (!$response->success()) {
            $unstruct_error_description = "mail send failed: bad response from mailjet, contact to your admin pls.";
            $error_body = $response->getBody();
            required(isset($error_body['Messages']),51, $internal_error_title, $unstruct_error_description);
            $errors = ["<ul>"];
            foreach ($error_body['Messages'] as $error_arr) {
                required(isset($error_arr['Errors']),52, $internal_error_title,$unstruct_error_description);
                foreach ($error_arr['Errors'] as $error) {
                    required(isset($error['ErrorMessage']),53, $internal_error_title,$unstruct_error_description);
                    $errors[] = "<li>" . $error['ErrorMessage'] . "</li>";
                }
            }
            
            $errors[] = "</ul>";
            $err_str = implode("", $errors);

            required(false, 30, $error_title, $err_str);
        }

        return true;
    }
}