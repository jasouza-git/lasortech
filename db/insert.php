<?php
require_once __DIR__ . "/../basic.php";
require_once "db.php";
class DB_INSERT extends DB {

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
            "name" => "string",
            "contact_number" => "string",
            "email" => "string",
            "messenger_id?"=> "string",
            "description?" => "string",
            "working" => "bool"
        ], $data);
        $combined = build_insert_sql($employee, "employees");
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
}
