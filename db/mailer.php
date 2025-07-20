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

        $replacing = [
            "{{code}}" => $code
        ];

        $this->delete->delete([
                "ids" => [$paras['email']],
            ], "email_validations", "email");

        $this->insert->email_verification([
            "email" => $paras["email"],
            "code" => $code
        ]);

        $mail_html = file_get_contents(__DIR__ . "/email_validation_code.html");

        foreach ($replacing as $key => $value) {
            $mail_html = str_replace($key, $value, $mail_html);
        }

        return $this->send(
            $paras['email'],
            "New User",
            "Your Verification Code from Lasortech",
            $mail_html
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
        $message = $paras['message'] ?? null;

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

            $item_rows .= <<<ROW
            <tr>
                <th>$index</th>
                <th>$name</th>
                <th>$brand</th>
                <th>$model</th>
                <th>$serial</th>
            </tr>
            ROW;
        }

        $msg = "";
        if ($message) {
            $msg = <<<MSG
            <div class="section">
                <h2>Message from Staff</h2>
                <p>$message<p>
            </div>
            MSG;
        }

        $replacing = [
            "{{customer_name}}" => $order['customer']['name'],
            "{{customer_email}}" => $order['customer']['email'],
            "{{order_id}}" => substr($order['id'], 0, 20),
            "{{order_rms}}" => $order['rms_code'],
            "{{order_status}}" => $order['state']['label'],
            "{{state_color}}" => $order['state']['color'],
            "{{last_update}}" => $order['update_at'],
            "{{items_rows}}" => $item_rows,
            "{{message}}" => $msg
        ];

        $mail_html = file_get_contents(__DIR__ . "/email_order.html");

        foreach ($replacing as $key => $value) {
            $mail_html = str_replace($key, $value, $mail_html);
        }

        return $this->send(
            $order['customer']['email'],
            $order['customer']['name'],
            "We're Working on Your Device - Here's the Latest",
            $mail_html
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