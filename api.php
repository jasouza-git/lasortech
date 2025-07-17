<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once "db/basic.php";
require_once "db/insert.php";
require_once "db/query.php";
require_once "db/update.php";
require_once "db/delete.php";
require_once "db/auth.php";
require_once "db/mailer.php";

$out = (object) [ "errno" => 0 ];

if (isset($_POST["action"])) {
    $action = $_POST["action"];
    $db = new Auth();

    $res = match ($action) {
        "register"      => $db->register($_POST),
        "login"         => $db->login($_POST),
        "login_out"     => $db->logout($_POST),
        default         => null
    };

    $out->data = $res;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Auth();
    $db->auth($_POST);

    if (isset($_POST['new'])) {
        $paras = $_POST['new'];
        $db = new DB_INSERT($db->conn);

        $res = match ($paras) {
            'customer'          => $db->customer($_POST),
            'item'              => $db->item($_POST),
            'order'             => $db->order($_POST),
            'state'             => $db->state($_POST),
            'items_into_order'  => $db->items_into_order($_POST),
            default             => null,
        };

        $out->data = $res;
    } else if (isset($_POST['update'])) {
        $paras = $_POST['update'];
        $db = new DB_UPDATE($db->conn);

        $res = match ($paras) {
            'employee'      => $db->employee($_POST),
            'customer'      => $db->customer($_POST),
            'item'          => $db->item($_POST),
            'order'         => $db->order($_POST),
            default         => null,
        };

        $out->data = $res;
    } else if (isset($_POST['query'])) {
        $query = $_POST['query'];
        $db = new DB_QUERY($db->conn);

        $res = match ($query) {
            'employees'     => $db->employees($_POST),
            'items'         => $db->items($_POST),
            'customers'     => $db->customers($_POST),
            'orders'        => $db->orders($_POST),
            'states'        => $db->states($_POST),
            default         => null,
        };

        $out->data = $res;
    } else if (isset($_POST['get'])) {
        $get = $_POST['get'];
        $db = new DB_QUERY($db->conn);
        $res = match ($get) {
            'current'       => $db->get_current($_POST),
            'order_detail'  => $db->get_orders_detail($_POST),
            'employee'      => $db->get_employees($_POST),
            'item'          => $db->get($_POST, "items"),
            'customer'      => $db->get($_POST, "customers"),
            'order'         => $db->get($_POST, "orders"),
            'state'         => $db->get_states($_POST),
            default         => null,
        };

        $out->data = $res;
    } else if (isset($_POST['delete'])) {
        $paras = $_POST['delete'];
        $db = new DB_DELETE($db->conn);

        $res = match ($paras) {
            'employees'     => $db->delete($_POST, "employees"),
            'customers'     => $db->delete($_POST, "customers"),
            'items'         => $db->delete($_POST, "items"),
            'orders'        => $db->delete($_POST, "orders"),
            'states'        => $db->delete($_POST, "procedures"),
            default         => null,
        };

        $out->data = $res;
    } else if (isset($_POST["email"])) {
        $paras = $_POST["email"];
        $db = new Mailer($db->conn);

        $res = match ($paras) {
            'order'         => $db->send_order($_POST),
            default         => null
        };

        $out->data = $res;
    }
}

required(property_exists($out, 'data') && $out->data !== null, 17, "not found");

header("Content-Type: application/json");
echo json_encode($out);