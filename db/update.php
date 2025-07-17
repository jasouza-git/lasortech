<?php
require_once "basic.php";
require_once "db.php";
class DB_UPDATE extends DB {

    public function employee(array $data) {
        required(isset($data['id']), 11, "provide the id of employee to modify the data");
        $employee = parameter([
            "name?" => "string",
            "contact_number?" => "string",
            "email?" => "string",
            "messenger_id?"=> "string",
            "description?" => "string",
            "working?" => "bool"
        ], $data);

        $combined = build_update_sql($data['id'], $employee, "employees");
        return $this->execute($combined);
    }

    public function customer(array $data) {
        required(isset($data['id']), 12, "provide the id of customer to modify the data");
        $customer = parameter([
            "name?" => "string",
            "contact_number?" => "string",
            "email?" => "string",
            "messenger_id?"=> "string",
            "description?" => "string"
        ], $data);

        $combined = build_update_sql($data['id'], $customer, "customers");
        return $this->execute($combined);
    }

    public function item(array $data) {
        required(isset($data['id']), 13, "provide the id of item to modify the data");
        $item = parameter([
            "belonged_customer_id?" => "string",
            "brand?" => "string",
            "model?" => "string",
            "name?" => "string",
            "serial?" => "string"
        ], $data);

        $combined = build_update_sql($data['id'], $item, "items");
        return $this->execute($combined);
    }

    public function order(array $data) {
        required(isset($data['id']), 14, "provide the id of order to modify the data");
        $order = parameter([
            "rms_code?" => "string",
            "description?" => "string",
        ], $data);

        $combined = build_update_sql($data['id'], $order, "orders");
        return $this->execute($combined);
    }
}