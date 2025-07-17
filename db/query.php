<?php
require_once "basic.php";
require_once "db.php";
class DB_QUERY extends DB {

    /**
     * @param array $data:
     *      - "keywords?": the keyword used to filter employee datas
     *      - "mode?":
     *          - "all": no filter, all employees
     *          - "working": all working employees
     *          - "retired": all retired employees
     * @return array the filtered employees
     */
    public function employees(array $data) {

        $paras = parameter([
            "keywords?" => "string[]",
            "mode?" => "string",
            "page?" => "int",
            "count?" => "int"
        ], $data);
        
        $queries = $paras['keywords'] ?? [];
        $mode = $paras['mode'] ?? "all";

        $sql_combined = build_fetch_sql(
            $queries, 
            "SELECT * FROM employees",
            ["(name LIKE ? OR contact_number LIKE ? OR email LIKE ?)", 3],
            false,
            build_tail_sql($paras),
            fn() => match ($mode) {
                "working" => [
                    "sql" => "WHERE working = 1", 
                    "values" => [],
                    "types" => "",
                    "next_op" => " AND "
                ],
                "retired" => [
                    "sql" => "WHERE working = 0", 
                    "values" => [], 
                    "types" => "",
                    "next_op" => " AND "
                ],
                default => null
            }
        );

        $res = $this->fetch($sql_combined);
        foreach ($res as &$row) {
            $row['working'] = (bool)$row['working'];
        }
        unset($row);
        return $res;
    }

    /**
     * @param array $data:
     *      - "keywords?": the keyword used to filter order datas
     *      - "mode?":
     *          - "all": no filter, all orders
     *          - "ongoing": all ongoing orders
     *          - "finished": all finished orders
     * @return array the filtered orders
     */
    public function orders(array $data) {
        $paras = parameter([
            "keywords?" => "string[]",
            "mode?" => "string",
            "page?" => "int",
            "count?" => "int"
        ], $data);

        $queries = $paras['keywords'] ?? [];
        $mode = $paras['mode'] ?? "all";

        $sql_combined = build_fetch_sql(
            $queries,
            "SELECT o.* FROM orders o",
            ["(o.id like ? or o.rms_code like ? or o.description like ?)", 3],
            false,
            build_tail_sql($paras),
            function() use ($mode) {
                $joinSQL = <<<SQL
                JOIN (
                    SELECT order_id, MAX(update_at) AS latest_update_at
                    FROM procedures
                    GROUP BY order_id
                ) p1 ON o.id = p1.order_id
                JOIN procedures p2 ON p1.order_id = p2.order_id AND p1.latest_update_at = p2.update_at
                WHERE
                SQL;

                return match ($mode) {
                    "ongoing"   => [
                        "sql" => "$joinSQL p2.state_code NOT IN (3, 6)",
                        "values" => [],
                        "types" => "",
                        "next_op" => " AND\n"
                    ],
                    "finished"  => [
                        "sql" => "$joinSQL p2.state_code IN (3, 6)",
                        "values" => [],
                        "types" => "",
                        "next_op" => " AND\n"
                    ],
                    default => null
                };
            }
        );

        return $this->fetch($sql_combined);
    }

    /**
     * @param array $data 
     *      - "keywords?": the keyword used to filter customer datas
     *      - "mode?":
     *          - "all": no filter, all customers
     *          - "pending": all customers who have pending order(s)
     *          - "finished": all customers who have no pending order
     * @return array the filtered customers
     */
    public function customers(array $data) {

        $paras = parameter([
            "keywords?" => "string[]",
            "mode?" => "string",
            "page?" => "int",
            "count?" => "int"
        ], $data);

        $queries = $paras['keywords'] ?? [];
        $mode = $paras['mode'] ?? "all";

        $sql_combined = build_fetch_sql(
            $queries,
            "SELECT DISTINCT c.* FROM customers c",
            ["(c.name like ? or c.contact_number like ? or c.email like ?)", 3],
            false,
            build_tail_sql($paras),
            function() use ($mode) {
                $joinSQL = <<<SQL
                JOIN items i ON c.id = i.belonged_customer_id
                JOIN order_item_map oim ON i.id = oim.item_id
                JOIN orders o ON oim.order_id = o.id
                JOIN (
                    SELECT order_id, MAX(update_at) AS latest_update_at
                    FROM procedures
                    GROUP BY order_id
                ) p1 ON o.id = p1.order_id
                JOIN procedures p2 ON p1.order_id = p2.order_id AND p1.latest_update_at = p2.update_at
                WHERE
                SQL;

                return match ($mode) {
                    "pending"   => [
                        "sql" => "$joinSQL p2.state_code NOT IN (3, 6)",
                        "values" => [],
                        "types" => "",
                        "next_op" => " AND\n"
                    ],
                    "finished"  => [
                        "sql" => "$joinSQL p2.state_code IN (3, 6)",
                        "values" => [],
                        "types" => "",
                        "next_op" => " AND\n"
                    ],
                    default => null
                };
            }
        );

        return $this->fetch($sql_combined);
    }

    /**
     * @param array $data:
     *      - "keywords?": the keyword used to filter customer datas
     *      - "mode?":
     *          - "all": no filter, all customers
     *          - "handling": all items who is in pending order(s)
     *          - "finished": all items who is in finished order
     * @return array the filtered customers
     */
    public function items(array $data) {

        $paras = parameter([
            "keywords?" => "string[]",
            "mode?" => "string",
            "page?" => "int",
            "count?" => "int"
        ], $data);

        $queries = $paras['keywords'] ?? [];
        $mode = $paras['mode'] ?? "all";

        $sql_combined = build_fetch_sql(
            $queries,
            "SELECT DISTINCT i.* FROM items i",
            ["(i.brand like ? or i.model like ? or i.name like ? or i.serial like ?)", 4],
            false,
            build_tail_sql($paras),
            function() use ($mode) {
                $joinSQL = <<<SQL
                JOIN order_item_map oim ON i.id = oim.item_id
                JOIN orders o ON oim.order_id = o.id
                JOIN (
                    SELECT order_id, MAX(update_at) AS latest_update_at
                    FROM procedures
                    GROUP BY order_id
                ) p1 ON o.id = p1.order_id
                JOIN procedures p2 ON p1.order_id = p2.order_id AND p1.latest_update_at = p2.update_at
                WHERE
                SQL;

                return match ($mode) {
                    "handling"   => [
                        "sql" => "$joinSQL p2.state_code NOT IN (3, 6)",
                        "values" => [],
                        "types" => "",
                        "next_op" => " AND\n"
                    ],
                    "finished"  => [
                        "sql" => "$joinSQL p2.state_code IN (3, 6)",
                        "values" => [],
                        "types" => "",
                        "next_op" => " AND\n"
                    ],
                    default => null
                };
            }
        );

        return $this->fetch($sql_combined);
    }

    /**
     * @param array $data:
     *      - "keywords?": the keyword used to filter employee datas
     *      - "mode?":
     *          - "all": no filter, all employees
     *          - "working": all working employees
     *          - "retired": all retired employees
     * @return array the filtered employees
     */
    public function states(array $data) {
        $paras = parameter([
            "order_ids?" => "string[]",
            "page?" => "int",
            "count?" => "int"
        ], $data);

        $queries = $paras['order_ids'] ?? [];

        $sql_combined = build_fetch_sql(
            $queries, 
            "SELECT * FROM procedures",
            ["(order_id = ?)", 1],
            true,
            build_tail_sql($paras),
            fn() => null
        );

        return $this->fetch($sql_combined);
    }

    /**
     * get current logging employee
     * @param array $data: session_id
     * @return array:
     *      - email: string,
     *      - id: string,
     *      - name: string,
     *      - contact_number: string,
     *      - messenger_id?: string,
     *      - avatar?: string,
     *      - description?: string,
     *      - working: bool,
     */
    public function get_current(array $data) {
        $paras = parameter([
            "session_id" => "string"
        ], $data);

        $sql = <<<SQL
        SELECT 
            u.email,
            e.id,
            e.name,
            e.contact_number,
            e.messenger_id,
            e.avatar,
            e.description,
            e.working
        FROM sessions s
        JOIN users u ON s.user_id = u.id
        JOIN employees e ON u.id = e.id
        WHERE s.id = ?;
        SQL;

        $res = $this->fetch([
            "sql" => $sql,
            "values" => [$paras['session_id']],
            "types" => "s"
        ]);

        required(count($res) == 1, 25, "session not valid");

        $res = $res[0];
        $res['working'] = (bool)$res['working'];
        return $res;
    }

    public function get_employees(array $data) {
        $res = $this->get($data, "employees");
        foreach ($res as &$row) {
            $row['working'] = (bool)$row['working'];
        }
        unset($row);
        return $res;
    }

    /**
     * get states from database using id filter
     * @param array $ids: the ids used for filter states
     * @return array: 
     *      - id: string
     *      - order_id: string
     *      - state_code: string
     *      - update_at: timestamp
     *      - state_data: array (other parameters depends on state type)
     */
    public function get_states(array $data) {
        $states = $this->get($data, "procedures");
        foreach ($states as &$state) {
            $config = $this->get_state_map($state['state_code']);
            if ($config) {
                $r = $this->get([
                    "ids" => $state['id']
                ], $config[0], "state_id", false)[0];
                foreach ($config[1] as $field => $_) {
                    if (str_ends_with( $field, "?")) {
                        $field = substr($field, 0, -1);
                    }
                    $state[$field] = $r[$field];
                }
            }
        }
        return $states;
    }

    /**
     * get data from database using id filter
     * @param array $ids: the ids used for filter
     * @param string $table: table name
     * @param bool $with_order_statement: fetch with order?
     * @return array
     */
    public function get(
        array $data, 
        string $table, 
        string $id_field = "id",
        bool $with_order_statement = true
    ) {

        $paras = parameter([
            "ids?" => "string[]",
            "page?" => "int",
            "count?" => "int"
        ], $data);

        $ids = $paras["ids"] ?? [];

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $types = str_repeat('s', count($ids));

        $tail_sql = $with_order_statement ? (" " . build_tail_sql($paras)) : "";

        if ($placeholders) {
            $placeholders = " WHERE $id_field IN ($placeholders)";
        }

        $res = $this->fetch([
            "sql" => "SELECT * FROM $table$placeholders$tail_sql",
            "values" => $ids,
            "types" => $types
        ]);

        return $res;
    }

    public function get_orders_detail(array $data) {
        $paras = parameter([
            "ids" => "string[]"
        ], $data);

        $ids = $paras["ids"];
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $types = str_repeat('s', count($ids));

        $sql = <<<SQL
        SELECT 
            o.id AS order_id,
            o.rms_code,
            o.description AS order_description,
            c.name AS customer_name,
            c.contact_number AS customer_contact_number,
            c.email AS customer_email,
            i.id AS item_id,
            i.brand,
            i.model,
            i.serial AS item_serial,
            i.name AS item_name,
            p.id AS state_id,
            p.state_code,
            p.update_at
        FROM orders o
        JOIN order_item_map oim ON o.id = oim.order_id
        JOIN items i ON oim.item_id = i.id
        JOIN customers c ON i.belonged_customer_id = c.id
        LEFT JOIN (
            SELECT p1.*
            FROM procedures p1
            INNER JOIN (
                SELECT order_id, MAX(update_at) AS latest_update_at
                FROM procedures
                GROUP BY order_id
            ) p2
            ON p1.order_id = p2.order_id AND p1.update_at = p2.latest_update_at
        ) p ON o.id = p.order_id
        WHERE o.id IN ($placeholders)
        SQL;

        $res = $this->fetch([
            "sql" => $sql,
            "values" => $ids,
            "types" => $types
        ]);

        $out = [];
        foreach ($res as $row) {
            $order_id = $row["order_id"];

            if (!isset($out[$order_id])) {

                $state_data = $this->get_states([
                    "ids" => [$row['state_id']]
                ])[0];

                $out[$order_id] = [
                    "id" => $row["order_id"],
                    "rms_code" => $row["rms_code"],
                    "description" => $row["order_description"],
                    "state_code" => $row["state_code"],
                    "state" => $state_data,
                    "update_at" => $row["update_at"],
                    "customer" => [
                        "name" => $row["customer_name"],
                        "email" => $row["customer_email"],
                        "contact_number" => $row["customer_contact_number"]
                    ],
                    "items" => []
                ];
            }
            
            $out[$order_id]["items"][] = [
                "id" => $row["item_id"],
                "brand" => $row["brand"],
                "model" => $row["model"],
                "serial" => $row["item_serial"],
                "name" => $row["item_name"]
            ];
        }

        return array_values($out);
    }
}
