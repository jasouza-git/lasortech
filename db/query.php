<?php
require_once "basic.php";
require_once "db.php";
class DB_QUERY extends DB {

    /// -------------------------------------Queries-------------------------------------------
    ///
    /// The Query API provides [Structured Data] through query conditions.
    /// Unlike [Raw Data], which is read directly from the database without processing,
    /// the Query API returns filtered and organized results according to your needs,
    /// making it easier to use directly.

    /**
     * @param array $data:
     *      - "get_count_only?": 
     *          - if true, means query the counts of the employees using current filter conditions
     *              This parameter is not affected by the pagination parameters “page” and “count_per_page”. 
     *              Even if pagination limits are set, this function will still return the correct total number of records.
     *          - if false or null, will return the employees normally
     * 
     *      - "keywords?": the keyword used to filter customer datas
     * 
     *      - "mode?" (optional):
     *          - "all": no filter, return all employees.
     *          - "ongoing":  will fetch employees who are still handling ongoing orders.
     *          - "finished": will fetch employees who have completed all assigned orders.
     * 
     *      - "page?": the page you query, start at 0, if null, query all employees
     * 
     *      - "count_per_page?": how many employees shown in one page? if null, query all employees
     * 
     * @return array the filtered employees (detailed):
     *      - if get_count_only is true
     *          - count: int
     * 
     *      - otherwise -> return array with such structure:
     *          - id: string,
     *          - name: string,
     *          - email: string,
     *          - contact_number: string,
     *          - messenger_id?: string,
     *          - avatar?: string,
     *          - description?: string,
     *          - working: bool,
     *          - update_at: string,
     *          - create_at: string,
     *          - order: <Order Data>[]
     */
    public function employees(array $data) {
        $paras = parameter([
            "get_count_only?" => "bool",
            "keywords?" => "string[]",
            "mode?" => "string",
            "page?" => "int",
            "count_per_page?" => "int"
        ], $data);
        
        $queries = $paras['keywords'] ?? [];
        $mode = $paras['mode'] ?? "all";
        $get_count_only = $paras["get_count_only"] ?? false;
        $selected_fields = $get_count_only ? "COUNT(id) AS count" : "id, update_at";

        $sql_combined = build_fetch_sql(
            $queries, 
            "SELECT $selected_fields FROM employees",
            ["(name LIKE ? OR contact_number LIKE ?)", 2],
            false,
            $get_count_only ? null : build_tail_sql($paras),
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

        if ($get_count_only) {
            return $res[0];
        }

        $employee_ids = array_map(fn($employee) => $employee['id'], $res);
        $employees_detailed = $this->fetch_employees([
            "ids" => $employee_ids
        ]);

        return $employees_detailed;
    }

    /**
     * @param array $data:
     *      - "get_count_only?": 
     *          - if true, means query the counts of the orders using current filter conditions
     *              This parameter is not affected by the pagination parameters “page” and “count_per_page”. 
     *              Even if pagination limits are set, this function will still return the correct total number of records.
     *          - if false or null, will return the orders normally
     * 
     *      - "keywords?": the keyword used to filter customer datas
     * 
     *      - "mode?":
     *          - "all": no filter, all orders
     *          - "ongoing": will fetch all orders not finish yet
     *          - "finished": will fetch all orders finished
     * 
     *      - "page?": the page you query, start at 0, if null, query all orders
     * 
     *      - "count_per_page?": how many orders shown in one page? if null, query all orders
     * 
     * @return array the filtered orders (detailed):
     *      - if get_count_only is true
     *          - count: int
     * 
     *      - otherwise -> return array with such structure:
     *          - "id": string,
     *          - "rms_code": string,
     *          - "description?": string,
     *          - "state_code": smallint,
     *          - "state": <State Data>,
     *          - "update_at": string,
     *          - "create_at": string,
     *          - "customer": <Customer Data>,
     *          - "items": <Item Data>[]
     */
    public function orders(array $data) {
        $paras = parameter([
            "get_count_only?" => "bool",
            "keywords?" => "string[]",
            "mode?" => "string",
            "page?" => "int",
            "count_per_page?" => "int"
        ], $data);

        $queries = $paras['keywords'] ?? [];
        $mode = $paras['mode'] ?? "all";
        $get_count_only = $paras["get_count_only"] ?? false;
        $selected_fields = $get_count_only ? "COUNT(*) AS count" : "o.id";

        $sql_combined = build_fetch_sql(
            $queries,
            <<<SQL
            SELECT $selected_fields FROM orders o
            JOIN customers c ON c.id = o.customer_id
            SQL,
            [<<<SQL
            (
                o.id LIKE ? OR
                o.rms_code LIKE ? OR
                o.description LIKE ? OR
                c.name LIKE ? OR
                c.email LIKE ? OR
                c.contact_number LIKE ?
            )
            SQL, 6],
            false,
            $get_count_only ? null : build_tail_sql($paras, "ORDER BY o.update_at"),
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

        $orders = $this->fetch($sql_combined);

        if ($get_count_only) {
            return $orders[0];
        }

        $order_ids = array_map(fn($order) => $order['id'], $orders);
        $orders_detailed = $this->fetch_orders([
            "ids" => $order_ids
        ]);

        return $orders_detailed;
    }

    /**
     * @param array $data 
     *      - "get_count_only?": 
     *          - if true, means query the counts of the customers using current filter conditions
     *              This parameter is not affected by the pagination parameters “page” and “count_per_page”. 
     *              Even if pagination limits are set, this function will still return the correct total number of records.
     *          - if false or null, will return the customers normally
     * 
     *      - "keywords?": the keyword used to filter customer datas
     * 
     *      - "mode?":
     *          - "all": no filter, all customers
     *          - "pending": will fetch all customers who have pending order(s)
     *          - "finished": will fetch all customers whose orders are all finished
     * 
     *      - "page?": the page you query, start at 0, if null, query all customers
     * 
     *      - "count_per_page?": how many customers shown in one page? if null, query all customers
     * 
     * @return array the filtered customers:
     *      - if get_count_only is true
     *          - count: int
     * 
     *      - otherwise -> return array with such structure:
     *          - id: string,
     *          - name: string,
     *          - contact_number: string,
     *          - email: string,
     *          - messenger_id?: string,
     *          - description?: string,
     *          - update_at: string,
     *          - create_at: string,
     *          - orders: <Order Data>[],
     *          - items: <Item Data>[]
     */
    public function customers(array $data) {
        $paras = parameter([
            "get_count_only?" => "bool",
            "keywords?" => "string[]",
            "mode?" => "string",
            "page?" => "int",
            "count_per_page?" => "int"
        ], $data);

        $queries = $paras['keywords'] ?? [];
        $mode = $paras['mode'] ?? "all";
        $get_count_only = $paras["get_count_only"] ?? false;
        $selected_fields = $get_count_only ? "SELECT COUNT(DISTINCT c.id) AS count" : "SELECT DISTINCT c.id, c.update_at";

        $sql_combined = build_fetch_sql(
            $queries,
            "$selected_fields FROM customers c",
            ["(c.name like ? or c.contact_number like ? or c.email like ?)", 3],
            false,
            $get_count_only ? null : build_tail_sql($paras, "ORDER BY c.update_at"),
            function() use ($mode) {
                $join_sql = $mode == "finished" ? "LEFT JOIN" : "JOIN";
                $joinSQL = <<<SQL
                $join_sql items i ON c.id = i.belonged_customer_id
                $join_sql order_item_map oim ON i.id = oim.item_id
                $join_sql orders o ON c.id = o.customer_id
                $join_sql (
                    SELECT order_id, MAX(update_at) AS latest_update_at
                    FROM procedures
                    GROUP BY order_id
                ) p1 ON o.id = p1.order_id
                $join_sql procedures p2 ON p1.order_id = p2.order_id AND p1.latest_update_at = p2.update_at
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
                        "sql" => "$joinSQL p2.state_code IN (3, 6) OR p2.state_code IS NULL",
                        "values" => [],
                        "types" => "",
                        "next_op" => " AND\n"
                    ],
                    default => null
                };
            }
        );

        $res = $this->fetch($sql_combined);

        if ($get_count_only) {
            return $res[0];
        }

        $customer_ids = array_map(fn($customer) => $customer['id'], $res);
        $customers_detailed = $this->fetch_customers([
            "ids" => $customer_ids
        ]);

        return $customers_detailed;
    }

    /**
     * @param array $data 
     *      - "get_count_only?": 
     *          - if true, means query the counts of the items using current filter conditions
     *              This parameter is not affected by the pagination parameters “page” and “count_per_page”. 
     *              Even if pagination limits are set, this function will still return the correct total number of records.
     *          - if false or null, will return the items normally
     * 
     *      - "keywords?": the keyword used to filter item datas
     * 
     *      - "mode?" (optional):
     *          - "all": No filter, return all items.
     *          - "handling": will fetch items that have at least one ongoing (pending) order.
     *          - "finished": will fetch items where all associated orders have been completed.
     * 
     *      - "page?": the page you query, start at 0, if null, query all items
     * 
     *      - "count_per_page?": how many items shown in one page? if null, query all items
     * 
     * @return array the filtered items:
     *      - if get_count_only is true
     *          - count: int
     * 
     *      - otherwise -> return array with such structure:
     *          - id: string,
     *          - brand?: string,
     *          - model?: string,
     *          - name?: string,
     *          - serial: string,
     *          - update_at: string,
     *          - create_at: string,
     *          - orders: <Order Data>[],
     *          - customer: <Customer Data>
     */
    public function items(array $data) {
        $paras = parameter([
            "get_count_only?" => "bool",
            "keywords?" => "string[]",
            "mode?" => "string",
            "page?" => "int",
            "count_per_page?" => "int"
        ], $data);

        $queries = $paras['keywords'] ?? [];
        $mode = $paras['mode'] ?? "all";
        $get_count_only = $paras["get_count_only"] ?? false;
        $selected_fields = $get_count_only ? "COUNT(i.id) AS count" : "i.id, i.update_at";

        $sql_combined = build_fetch_sql(
            $queries,
            "SELECT DISTINCT $selected_fields FROM items i",
            ["(i.brand like ? or i.model like ? or i.name like ? or i.serial like ?)", 4],
            false,
            $get_count_only ? null : build_tail_sql($paras, "ORDER BY i.update_at"),
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

        $res = $this->fetch($sql_combined);

        if ($get_count_only) {
            return $res[0];
        }

        $item_ids = array_map(fn($item) => $item['id'], $res);
        $items_detailed = $this->fetch_items([
            "ids" => $item_ids
        ]);

        return $items_detailed;
    }

    /**
     * @param array $data 
     *      - "get_count_only?": 
     *          - if true, means query the counts of the states using current filter conditions
     *              This parameter is not affected by the pagination parameters “page” and “count_per_page”. 
     *              Even if pagination limits are set, this function will still return the correct total number of records.
     *          - if false or null, will return the states normally
     * 
     *      - "order_ids?": filter states using order's id,
     *           will return all states for each order_id, 
     *           null means get all states of every orders
     * 
     *      - "page?": the page you query, start at 0, if null, query all items
     * 
     *      - "count_per_page?": how many items shown in one page? if null, query all items
     * 
     * @return array the filtered items:
     *      - if get_count_only is true
     *          - count: int
     * 
     *      - otherwise -> return array with such structure:
     *          - id: string
     *          - order_id: string
     *          - state_code: string
     *          - update_at: timestamp
     *          - ...state_data: (parameters depends on state type, see `append_state_labels`)[]
     */
    public function states(array $data) {
        $paras = parameter([
            "get_count_only?" => "bool",
            "order_ids?" => "string[]",
            "page?" => "int",
            "count_per_page?" => "int"
        ], $data);

        $queries = $paras['order_ids'] ?? [];
        $get_count_only = $paras["get_count_only"] ?? false;
        $selected_fields = $get_count_only ? "COUNT(id) AS count" : "id, update_at";

        $sql_combined = build_fetch_sql(
            $queries, 
            "SELECT $selected_fields FROM procedures",
            ["(order_id = ?)", 1],
            true,
            $get_count_only ? null : build_tail_sql($paras),
            fn() => null
        );

        $states = $this->fetch($sql_combined);

        if ($get_count_only) {
            return $states[0];
        }

        $state_ids = array_map(fn($state) => $state['id'], $states);
        $states_detailed = $this->fetch_states([
            "ids" => $state_ids
        ]);

        return $states_detailed;
    }

    /// -------------------------------------Fetches-------------------------------------------
    ///
    /// Fetch API allows retrieving [Structured Data] directly by specifying the data ID. 
    /// It is designed for situations where you already know the exact data you want to retrieve.
    /// Therefore, Fetch API will not provide pagination query, the parameter "page" and "count_of_page" won't work.
    /// For pagination query, you should try Query and Get APIs.
    ///
    /// The [Raw Data], which is read directly from the database without processing,
    /// the Query API returns filtered and organized results according to your needs,
    /// making it easier to use directly.

    /**
     * fetch the employees detailed using its ids, if want get all employees, use query=employees or get=employees api instead.
     * @param array $data:
     *      - ids: the employees's id you want get.
     * @return array the employees (detailed):
     *      - id: string,
     *      - name: string,
     *      - email: string,
     *      - contact_number: string,
     *      - messenger_id?: string,
     *      - avatar?: string,
     *      - description?: string,
     *      - working: bool,
     *      - update_at: string,
     *      - create_at: string,
     *      - order: <Order Data>[]
     */
    public function fetch_employees(array $data) {
        $data = parameter([
            "ids" => "string[]"
        ], $data);

        $ids = $data["ids"];
        if (empty($ids)) return [];
        
        $combined = make_combined_using_ids(
            $ids,
            fn($placeholders) => $sql = [
                "sql" => <<<SQL
                    SELECT DISTINCT 
                        e.*,
                        u.email,
                        o.id AS order_id
                    FROM employees e
                    JOIN users u ON u.id = e.id
                    LEFT JOIN state_processings sp ON e.id = sp.employee_id
                    LEFT JOIN procedures p ON sp.state_id = p.id
                    LEFT JOIN orders o ON p.order_id = o.id
                    WHERE e.id IN ($placeholders)
                    ORDER BY FIELD(e.id, $placeholders)
                SQL,
                "placeholder_count" => 2
            ]
        );

        $employees = $this->fetch($combined);
        $order_ids = array_filter(array_map(fn($employee) => $employee["order_id"], $employees));
        $orders = $this->fetch_orders([
            "ids" => $order_ids
        ]);

        $orders = array_column($orders,null, "id");

        foreach ($employees as &$employee) {
            if ($employee['order_id']) {
                $employee['order'] = $orders[$employee['order_id']];
                unset($employee['order_id']);
            } else {
                $employee['order'] = [];
                unset($employee['order_id']);
            }
            $employee['working'] = (bool)$employee['working'];
        }
        unset($employee);

        return $employees;
    }

    /**
     * fetch the customers detailed using its ids, if want get all customers, use query=customers or get=customers api instead.
     * @param array $data:
     *      - ids: the customers's id you want get.
     * @return array the customers (detailed):
     *      - id: string,
     *      - name: string,
     *      - contact_number: string,
     *      - email: string,
     *      - messenger_id?: string,
     *      - description?: string,
     *      - update_at: string,
     *      - create_at: string,
     *      - orders: <Order Data>[],
     *      - items: <Item Data>[]
     */
    public function fetch_customers(array $data) {
        $data = parameter([
            "ids" => "string[]"
        ], $data);

        $ids = $data["ids"];
        if (empty($ids)) return [];

        $combined = make_combined_using_ids(
            $ids,
            fn($placeholders) => [
                "sql" => <<<SQL
                    SELECT
                        c.id AS customer_id,
                        i.*
                    FROM customers c
                    LEFT JOIN items i ON c.id = i.belonged_customer_id
                    WHERE c.id IN ($placeholders)
                    ORDER BY FIELD(c.id, $placeholders)
                SQL,
                "placeholder_count" => 2
            ]
        );

        $customer_item_map = $this->fetch($combined);
        $map = [];
        foreach ($customer_item_map as $row) {
            $customerId = $row['customer_id'];
            if (!isset($map[$customerId])) $map[$customerId] = [];
            if ($row['belonged_customer_id']) {
                unset($row['customer_id']);
                unset($row['belonged_customer_id']);
                $map[$customerId][] = $row;
            }
        }

        unset($combined);
        $combined = make_combined_using_ids(
            $ids,
            fn($placeholders) => [
                "sql" => <<<SQL
                    SELECT 
                        c.*,
                        GROUP_CONCAT(DISTINCT o.id ORDER BY o.id SEPARATOR ',') AS order_ids
                    FROM customers c
                    LEFT JOIN orders o ON c.id = o.customer_id
                    WHERE c.id IN ($placeholders)
                    GROUP BY c.id
                    ORDER BY FIELD(c.id, $placeholders)
                SQL,
                "placeholder_count" => 2
            ]
        );
        
        $customers = $this->fetch($combined);

        foreach ($customers as &$customer) {
            $customer['orders'] = match ($customer['order_ids']) {
                null => [],
                default => $this->fetch_orders([
                    "ids" => explode(",", $customer['order_ids'])
                ], false)
            };

            $customer['items'] = match ($map[$customer['id']]) {
                null => [],
                default => $map[$customer['id']]
            };

            unset($customer['order_ids']);
        }
        unset($customer);

        return $customers;
    }

    /**
     * fetch the employees detailed using its ids, if want get all items, use query=items or get=items api instead.
     * @param array $data:
     *      - ids: the items's id you want get.
     * @return array the items (detailed):
     *      - id: string,
     *      - brand?: string,
     *      - model?: string,
     *      - name?: string,
     *      - serial: string,
     *      - update_at: string,
     *      - create_at: string,
     *      - orders: <Order Data>[],
     *      - customer: <Customer Data>
     */
    public function fetch_items(array $data) {
        $data = parameter([
            "ids" => "string[]"
        ], $data);

        $ids = $data["ids"];
        if (empty($ids)) return [];

        $combined = make_combined_using_ids(
            $ids,
            fn($placeholders) => $sql = [
                "sql" => <<<SQL
                    SELECT
                        i.*,
                        c.id AS customer_id,
                        c.name AS customer_name,
                        c.contact_number AS customer_contact_number,
                        c.email AS customer_email,
                        c.messenger_id AS customer_messenger_id,
                        c.description AS customer_description,
                        c.update_at AS customer_update_at,
                        c.create_at AS customer_create_at,
                        GROUP_CONCAT(DISTINCT o.id ORDER BY o.id SEPARATOR ',') AS order_ids
                    FROM items i
                    LEFT JOIN order_item_map oim ON i.id = oim.item_id
                    LEFT JOIN orders o ON oim.order_id = o.id
                    JOIN customers c ON c.id = i.belonged_customer_id
                    WHERE i.id IN ($placeholders)
                    GROUP BY i.id
                    ORDER BY FIELD(i.id, $placeholders)
                SQL,
                "placeholder_count"=> 2
            ]
        );

        $items = $this->fetch($combined);

        foreach ($items as &$item) {
            $item['orders'] = match ($item['order_ids']) {
                null => [],
                default => $this->fetch_orders([
                    "ids" => explode(",", $item['order_ids'])
                ])
            };

            $item['customer'] = [
                "id" => $item['customer_id'],
                "name" => $item['customer_name'],
                "contact_number" => $item['customer_contact_number'],
                "email" => $item['customer_email'],
                "messenger_id" => $item['customer_messenger_id'],
                "description" => $item['customer_description'],
                "update_at" => $item['customer_update_at'],
                "create_at" => $item['customer_create_at']
            ];

            unset($item['customer_id']);
            unset($item['customer_name']);
            unset($item['customer_contact_number']);
            unset($item['customer_email']);
            unset($item['customer_messenger_id']);
            unset($item['customer_description']);
            unset($item['customer_update_at']);
            unset($item['customer_create_at']);
            unset($item['belonged_customer_id']);
            unset($item['order_ids']);
        }
        unset($item);

        return $items;
    }

    /**
     * fetch the states detailed using its ids, if want get all states or the states of a item, use query=states or get=states api instead.
     * @param array $data: 
     *      - ids: the ids used for filter states
     * @return array the states (detailed):
     *      - id: string
     *      - order_id: string
     *      - state_code: string
     *      - update_at: timestamp
     *      - ...state_data: (parameters depends on state type, see `append_state_labels`)[]
     */
    public function fetch_states(array $data, bool $with_order_id = true) {
        $data = parameter([
            "ids" => "string[]"
        ], $data);

        $ids = $data["ids"];
        if (empty($ids)) return [];

        return $this->get_states($data, $with_order_id);
    }
    
    /**
     * fetch the orders detailed using its ids, if want get all orders, use query=orders or get=orders api instead.
     * @param array $data:
     *      - "ids": the orders's id you want get.
     * @return array the orders (detailed):
     *      - id: string,
     *      - rms_code: string,
     *      - description?: string,
     *      - state_code: smallint,
     *      - state: <State Data>,
     *      - update_at: string,
     *      - create_at: string,
     *      - customer: <Customer Data>,
     *      - items: <Item Data>[]
     */
    public function fetch_orders(array $data, bool $with_customer_detail = true) {
        $paras = parameter([
            "ids" => "string[]"
        ], $data);
        
        $ids = $paras["ids"];
        if (empty($ids)) return [];

        $customer_select = "";
        $customer_join = "";

        if ($with_customer_detail) {
            $customer_select = <<<SELECT
                c.id AS customer_id,
                c.name AS customer_name,
                c.contact_number AS customer_contact_number,
                c.email AS customer_email,
                c.messenger_id AS customer_messenger_id,
                c.description AS customer_description,
                c.update_at AS customer_update_at,
                c.create_at AS customer_create_at,
            SELECT;

            $customer_join = "JOIN customers c ON o.customer_id = c.id";
        }

        $combined = make_combined_using_ids(
            $ids, 
            fn($placeholders) => [
                "sql" => <<<SQL
                    SELECT 
                        o.*,
                        $customer_select
                        i.id AS item_id,
                        i.brand,
                        i.model,
                        i.serial AS item_serial,
                        i.name AS item_name,
                        i.update_at AS item_update_at,
                        i.create_at AS item_create_at,
                        p.id AS state_id,
                        p.state_code,
                        p.update_at
                    FROM orders o
                    JOIN order_item_map oim ON o.id = oim.order_id
                    JOIN items i ON oim.item_id = i.id
                    $customer_join
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
                    ORDER BY FIELD(o.id, $placeholders)
                SQL,
                "placeholder_count" => 2
            ]
        );

        $res = $this->fetch($combined);

        $out = [];
        foreach ($res as $row) {
            $order_id = $row["id"];

            if (!isset($out[$order_id])) {

                $state_data = $this->fetch_states([
                    "ids" => [$row['state_id']]
                ],false)[0];

                $out[$order_id] = [];

                $out[$order_id]["id"] = $order_id;
                $out[$order_id]["rms_code"] = $row["rms_code"];
                $out[$order_id]["description"] = $row["description"];
                $out[$order_id]["state_code"] = $row["state_code"];

                if ($with_customer_detail) {
                    $out[$order_id]['customer'] = [
                        "id" => $row['customer_id'],
                        "name" => $row['customer_name'],
                        "contact_number" => $row['customer_contact_number'],
                        "email" => $row['customer_email'],
                        "messenger_id" => $row['customer_messenger_id'],
                        "description" => $row['customer_description'],
                        "update_at" => $row['customer_update_at'],
                        "create_at" => $row['customer_create_at']
                    ];
                }

                $out[$order_id]["state"] = $state_data;
                $out[$order_id]["update_at"] = $row["update_at"];
                $out[$order_id]["create_at"] = $row["create_at"];
                $out[$order_id]["items"] = [];
            }
            
            $out[$order_id]["items"][] = [
                "id" => $row["item_id"],
                "brand" => $row["brand"],
                "model" => $row["model"],
                "serial" => $row["item_serial"],
                "name" => $row["item_name"],
                "update_at" => $row["item_update_at"],
                "create_at" => $row["item_create_at"]
            ];
        }

        return array_values($out);
    }

    /// -------------------------------------Gets-------------------------------------------
    ///
    /// The Get API retrieves [Raw Data] directly from the database by using the data ID.
    /// It performs no additional processing on the data. 
    /// The result reflects exactly how the data is stored in the database.

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
            e.*
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

        required(count($res) == 1, 25, "Session Failed", "login session not valid.");

        $res = $res[0];
        $res['working'] = (bool)$res['working'];
        return $res;
    }

    /**
     * get the states [Raw Data] using its ids.
     * @param array $data: 
     *      - ids?: the ids used for filter states, if null, get all states
     * @return array the states (detailed):
     *      - id: string
     *      - order_id: string
     *      - state_code: string
     *      - update_at: timestamp
     *      - ...state_data: (parameters depends on state type, see `append_state_labels`)[]
     */
    public function get_states(array $data, bool $with_order_id = true) {
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
            if (!$with_order_id) unset($state["order_id"]);
        }

        return $this->append_state_labels($states);
    }

    /**
     * get the employees [Raw Data] using its ids.
     * @param array $data:
     *      - ids?: the employees's id you want get, if null, get all employees.
     * @return array the employees (detailed):
     *      - id: string,
     *      - name: string,
     *      - contact_number: string,
     *      - messenger_id?: string,
     *      - avatar?: string,
     *      - description?: string,
     *      - working: bool,
     *      - update_at: string,
     *      - create_at: string
     */
    public function get_employees(array $data) {
        $res = $this->get($data, "employees");
        foreach ($res as &$row) {
            $row['working'] = (bool)$row['working'];
        }
        unset($row);
        return $res;
    }

    /**
     * get the users [Raw Data] using its ids.
     * @param array $data:
     *      - ids?: the user's id you want get, if null, get all users.
     * @return array the users (detailed):
     *      - id: string,
     *      - email: string,
     *      - update_at: string,
     *      - create_at: string
     */
    public function get_users(array $data) {
        $res = $this->get($data, "users");
        foreach ($res as &$row) {
            unset($row['password_hashed']);
        }
        unset($row);
        return $res;
    }

    /**
     * get the [Raw Data] direct from database using its ids.
     * @param array $data: 
     *      - ids?: the data's you want get, if null, get all employees.
     * @param string $table: table name
     * @param bool $with_order: fetch with provided ids' order?
     *      if ids is null, will use update date sort.
     * @return array the data got from database. data structure depends on table.
     *      - customers:
     *          - id: string,
     *          - name: string,
     *          - contact_number: string,
     *          - email: string,
     *          - messenger_id?: string,
     *          - description?: string,
     *          - update_at: string,
     *          - create_at: string
     *
     *      - items:
     *          - id: string,
     *          - belonged_customer_id: string,
     *          - brand?: string,
     *          - model?: string,
     *          - name?: string,
     *          - serial?: string,
     *          - update_at: string,
     *          - create_at: string
     * 
     *      - employees:
     *          - id: string,
     *          - name: string,
     *          - contact_number: string,
     *          - messenger_id?: string,
     *          - avatar?: string,
     *          - description?: string,
     *          - working: string,
     *          - update_at: string,
     *          - create_at: string
     * 
     *      - users:
     *          - id: string,
     *          - email: string,
     *          - update_at: string,
     *          - create_at: string
     */
    public function get(
        array $data, 
        string $table, 
        string $id_field = "id",
        bool $with_order = true
    ) {
        $paras = parameter([
            "ids?" => "string[]",
            "page?" => "int",
            "count_per_page?" => "int"
        ], $data);

        $ids = $paras['ids'] ?? [];
        
        $combined = make_combined_using_ids(
            $ids, 
            function ($placeholders) use($id_field, $table, $paras, $with_order) {
                $tail_sql = 
                    $with_order ?
                    match (empty($placeholders)) {
                        true => build_tail_sql($paras),
                        false => build_tail_sql($paras, "ORDER BY FIELD($id_field, $placeholders)")
                    } : 
                    "";

                $where_sql = match (empty($placeholders)) {
                    true => "",
                    false => "WHERE $id_field IN ($placeholders)"
                };

                return [
                    "sql" => "SELECT * FROM $table $where_sql $tail_sql",
                    "placeholder_count" => empty($placeholders) ? 0 : ($with_order ? 2 : 1)
                ];
            }
        );

        $res = $this->fetch($combined);

        return $res;
    }

    /**
     * get the all records count from database, if you want conditional get count, use query=XXXX api instead.
     * @param string $table: table name.
     * @return array:
     *      - count: int
     */
    public function get_record_count(string $table) {
        $combined = [
            "sql" => "SELECT COUNT(*) AS count FROM $table",
            "values" => [],
            "types" => ""
        ];

        return $this->fetch($combined)[0];
    }

    /// -------------------------------------Others-------------------------------------------

    private function append_state_labels(array $states) {
        global $state_appearences;

        foreach ($states as &$state) {
            $state["label"] = $state_appearences[$state['state_code']][0];
            $state["color"] = $state_appearences[$state['state_code']][1];
        }
        unset($state);
        return $states;
    }

    public function email_verification_code(array $data) {
        $paras = parameter([
            "emails?" => "string[]"
        ], $data);

        $queries = $paras['emails'] ?? [];

        $sql_combined = build_fetch_sql(
            $queries, 
            "SELECT * FROM email_validations",
            ["(email = ?)", 1],
            true,
            null,
            fn() => null
        );

        return $this->fetch($sql_combined);
    }
}