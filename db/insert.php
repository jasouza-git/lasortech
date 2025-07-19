<?php
require_once "basic.php";
require_once "db.php";
class DB_INSERT extends DB {

    /**
     * @param array $data:
     *      - password_hashed: string
     * 
     * @return array:
     *      - id: string
     */
    public function user(array $data) {
        $user = parameter([
            "email" => "string",
            "password_hashed" => "string"
        ], $data);
        $combined = build_insert_sql($user, "users");
        return $this->insert($combined);
    }

    /**
     * @param array $data:
     *      - user_id: string
     * 
     * @return array:
     *      - id: string
     */
    public function session(array $data) {
        $session = parameter([
            "user_id" => "string"
        ], $data);
        $combined = build_insert_sql($session, "sessions");
        return $this->insert($combined);
    }

    /**
     * @param array $data:
     *      - name: string
     *      - contact_number: string
     *      - email: string
     *      - messenger_id?: string
     *      - description?: string
     *      - working: boolean
     *
     * @return array:
     *      - id: string
     *      - name: string
     *      - contact_number: string
     *      - email: string
     *      - messenger_id?: string
     *      - description?: string
     *      - working: boolean
     */
    public function employee(array $data) {
        $employee = parameter([
            "id" => "string",
            "name" => "string",
            "contact_number" => "string",
            "messenger_id?"=> "string",
            "description?" => "string",
            "working" => "bool"
        ], $data);
        $combined = build_insert_sql($employee, "employees", "id");
        $res = $this->insert($combined);
        $res["working"] = (bool)$res["working"];
        return $res;
    }
    
    /**
     * @param array $data:
     *      - name: string
     *      - contact_number: string
     *      - email: string
     *      - messenger_id?: string
     *      - description?: string
     * @return array:
     *      - id: string
     *      - name: string
     *      - contact_number: string
     *      - email: string
     *      - messenger_id?: string
     *      - description?: string
     */
    public function customer(array $data) {
        $customer = parameter([
            "name" => "string",
            "contact_number" => "string",
            "email" => "string",
            "messenger_id?" => "string",
            "description?" => "string"
        ], $data);
        $combined = build_insert_sql($customer, "customers");
        return $this->insert($combined);
    }

    /**
     * @param array $data:
     *      - belonged_customer_id: string
     *      - brand?: string
     *      - model?: string
     *      - name?: string
     *      - serial: string
     * @return array:
     *      - id: string
     *      - belonged_customer_id: string
     *      - brand?: string
     *      - model?: string
     *      - name?: string
     *      - serial: string
     */
    public function item(array $data) {
        $item = parameter([
            "belonged_customer_id" => "string",
            "brand?" => "string",
            "model?" => "string",
            "name?" => "string",
            "serial" => "string"
        ], $data);
        $combined = build_insert_sql($item, "items");
        return $this->insert($combined);
    }

    /**
     * @param array $data:
     *      - order_id: string
     *      - state_code: int (0~7)
     *      - other paramters depends on state type
     * @return array:
     *      - id: string
     *      - order_id: string
     *      - state_code: string
     *      - update_at: timestamp
     *      - state_data: array (other parameters depends on state type)
     */
    public function state(array $data) {
        $state = parameter([
            "order_id" => "string",
            "state_code" => "int"
        ], $data);

        $this->conn->begin_transaction();

        $state_attr = $this->get_state_map($state['state_code']);

        $combined = build_insert_sql($state,"procedures");

        $res = $this->insert($combined);
        $state_spc = null;
        if ($state_attr) {
            $param = parameter($state_attr[1], $data);
            $param['state_id'] = $combined['returning_new_id'];
            $c = build_insert_sql($param, $state_attr[0], "state_id");
            $state_spc = $this->insert($c);
        }

        $this->conn->commit();

        if ($state_spc) {
            $res['state_data'] = $state_spc;
        }

        return $res;
    }

    /**
     * @param array $data:
     *      - rms_code: string
     *      - description?: string
     *      - item_ids: string[]
     * @return array:
     *      - id: string
     *      - rms_code: string
     *      - description?: string
     *      - items: 
     *          - id: string
     *          - belonged_customer_id: string
     *          - brand?: string
     *          - model?: string
     *          - name?: string
     *          - serial: string
     */
    public function order(array $data) {
        $order = parameter([
            "customer_id" => "string",
            "rms_code" => "string",
            "description?" => "string",
        ], $data);

        $item_ids = parameter([
            "item_ids" => "string[]"
        ], $data)['item_ids'];

        $this->conn->begin_transaction();
        
        $combined = build_insert_sql($order, "orders");
        
        $res = $this->insert($combined);

        foreach ($item_ids as $item_id) {
            $c = build_insert_sql([
                "order_id" => $combined['returning_new_id'],
                "item_id" => $item_id
            ], "order_item_map", "order_id");

            $this->insert($c, true);
        }

        $state_combined = build_insert_sql([
            "order_id" => $combined["returning_new_id"],
            "state_code" => 0
        ], "procedures");

        $this->insert($state_combined);

        $items = $this->fetch([
            "sql" => <<<SQL
                SELECT i.*
                FROM items i
                JOIN order_item_map oim ON i.id = oim.item_id
                WHERE oim.order_id = ?
                SQL,
            "values" => [$combined['returning_new_id']],
            "types" => "s"
        ]);

        $this->conn->commit();

        $res['items'] = $items;

        return $res;
    }

    /**
     * @param array $data:
     *      - ids: string[]
     *      - order_id: string
     * @return bool
     */
    public function items_into_order(array $data) {
        $items = parameter([
            "ids" => "string[]",
            "order_id" => "string"
        ], $data);

        $this->conn->begin_transaction();
        foreach ($items['ids'] as $id) {
            $combined = build_insert_sql([
                "order_id" => $items['order_id'],
                "item_id" => $id
            ], "order_item_map", "order_id");

            $this->insert($combined, true);
        }
        $this->conn->commit();
        return true;
    }

    public function email_verification(array $data) {
        $codes = parameter([
            "email" => "string",
            "code" => "string"
        ], $data);

        $combined = build_insert_sql($codes, "email_validations");
        $this->insert($combined, true);
        return true;
    }
}
