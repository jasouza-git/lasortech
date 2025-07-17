<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once "db/basic.php";
require_once "db/insert.php";
require_once "db/query.php";
require_once "db/update.php";
require_once "db/delete.php";

$out = (object) [ "errno" => 0 ];

if (isset($_POST['new'])) {
    $paras = $_POST['new'];
    $db = new DB_INSERT();

    $res = match ($paras) {
        'employee'      => $db->employee($_POST),
        'customer'      => $db->customer($_POST),
        'item'          => $db->item($_POST),
        'order'         => $db->order($_POST),
        'state'         => $db->state($_POST),
        default         => null,
    };

    $out->data = $res;
} else if (isset($_POST['update'])) {
    $paras = $_POST['update'];
    $db = new DB_UPDATE();

    $res = match ($paras) {
        'employee'      => $db->employee($_POST),
        'customer'      => $db->customer($_POST),
        'item'          => $db->item($_POST),
        'order'         => $db->order($_POST),
        default         => null,
    };

    $out->data = $res;
} else if (isset($_GET['query'])) {
    $query = $_GET['query'];
    $db = new DB_QUERY();

    $res = match ($query) {
        'employees'     => $db->employees($_GET),
        'items'         => $db->items($_GET),
        'customers'     => $db->customers($_GET),
        'orders'        => $db->orders($_GET),
        'states'        => $db->states($_GET),
        default         => null,
    };

    $out->data = $res;
} else if (isset($_GET['get'])) {
    $get = $_GET['get'];
    $db = new DB_QUERY();

    $res = match ($get) {
        'employee'      => $db->get_employees($_GET),
        'item'          => $db->get($_GET, "items"),
        'customer'      => $db->get($_GET, "customers"),
        'order'         => $db->get($_GET, "orders"),
        'state'         => $db->get_states($_GET),
        default         => null,
    };

    $out->data = $res;
} else if (isset($_POST['delete'])) {
    $paras = $_POST['delete'];
    $db = new DB_DELETE();

    $res = match ($paras) {
        'employees'     => $db->delete($_POST, "employees"),
        'customers'     => $db->delete($_POST, "customers"),
        'items'         => $db->delete($_POST, "items"),
        'orders'        => $db->delete($_POST, "orders"),
        'states'        => $db->delete($_POST, "procedures"),
        default         => null,
    };

    $out->data = $res;
}

required(property_exists($out, 'data') && $out->data !== null, 17, "not found");

header("Content-Type: application/json");
echo json_encode($out);