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

    public function send_verification_code(array $data) {
        $paras = parameter([
            "email" => "string"
        ], $data);

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

    public function send_order(array $data) {

        $paras = parameter([
            "id" => "string",
            "message?" => "string"
        ], $data);

        $order_id = $paras['id'];
        $message = $paras['message'] ?? null;

        $orders = $this->query->get_orders_detail([
            "ids" => [$order_id]
        ]);

        required(count($orders) == 1, 100, "order mail cannot send, because order id not valid");

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

    public function send(string $to, string $to_name, string $subject, $body) {
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
        $response = $this->client->post(Resources::$Email, ['body' => $body]);
        if (!$response->success()) {
            $errorInfo = [
                'status' => $response->getStatus(),
                'reason' => $response->getReasonPhrase(),
                'body' => $response->getBody()
            ];
            required(false, 30, $errorInfo);
        }

        return true;
    }
}