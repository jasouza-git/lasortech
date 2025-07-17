<?php

class DB {
    protected $conn;

    /**
     * connect to the database, if $connection is provided, then use it instead
     * @param mysqli|null $connection
     */
    public function __construct(mysqli $connection = null) {
        if (!$connection) {
            global $out;
            $servername = "127.0.0.1";
            $username = "root";
            $password = "";
            $dbname = "lasortech";
            $port = 3306; 

            $conn = new mysqli($servername, $username, $password, $dbname, $port);
            
            guard( $conn->connect_error, 1);

            $tables = $conn->query("SHOW tables")->fetch_all();

            $res = [];

            foreach ($tables as $table) {
                $res[] = $table[0];
            }

            $need_init = false;
            foreach ([
                "employees",
                "customers",
                "items",
                "order_item_map",
                "orders",
                "procedures",
                "sessions",
                "state_incompletes",
                "state_payments",
                "state_processings",
                "state_user_cancels"
            ] as $table) {
                if (!in_array($table, $res)) {
                    $need_init = true;
                    break;
                }
            }

            if ($need_init) {
                $sql = file_get_contents(__DIR__ . "/struct.sql");
                $res = $conn->multi_query($sql);
                required($res, 21, "Init SQL failed: " . $conn->error);
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
            }

            $this->conn = $conn;
        } else {
            $this->conn = $connection;
        }
    }

    public function close() {
        $this->conn->close();
    }

    /**
     * execute SELECT statement in database
     * @param array $combined: the sql combined statement:
     *      - sql(string): sql prepare string
     *      - values(array<string>): values binding values
     *      - types(string): binding type string
     * @return array: fetch result from database
     */
    protected function fetch(array $combined) {
        return $this->execute($combined)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * execute INSERT statement in database
     * @param array $combined: the sql insert statment:
     *      - sql(string): sql prepare string
     *      - values(array<string>): values binding values
     *      - types(string): binding type string
     *      - returning_table(string): table using for returning
     *      - returning_new_id(string): the id using for returning
     */
    protected function insert(array $combined, bool $no_returning = false) {
        $this->execute($combined);

        if ($no_returning) {
            return true;
        }
        
        $res = $this->fetch([
            "sql" => "SELECT * FROM " . $combined['returning_table'] . " WHERE " . $combined['returning_id_field'] . " = ?",
            "values" => [$combined['returning_new_id']],
            "types" => "s"
        ]);

        required(count($res) == 1, 4, "database insertion failed - query failed");
        return $res[0];
    }

    /**
     * execute SELECT statement in database
     * @param array $combined: the sql combined statement:
     *      - sql(string): sql prepare string
     *      - values(array<string>): values binding values
     *      - types(string): binding type string
     * @return bool|mysqli_stmt
     */
    protected function execute(array $combined) {
        $prepare = $this->conn->prepare($combined['sql']);
        if (!empty($combined['values'])) {
            $prepare->bind_param($combined['types'], ...$combined['values']);
        }
        required($prepare->execute(), 7, "database execute falied");
        return $prepare;
    }

    protected $state_map = [
        null,
        [   
            "state_processings",
            [
                "employee_id" => "string",
                "reason?" => "string"
            ]
        ],
        null,
        null,
        [
            "state_incompletes",
            [
                "reason?" => "string"
            ]
        ],
        [   
            "state_user_cancels",
            [
                "reason?" => "string"
            ]
        ],
        null,
        [   
            "state_payments",
            [
                "amount" => "real"
            ]
        ]
    ];

    protected function get_state_map(int $index) {
        required($index >= 0 && $index <= 7, 9, "state code not valid");
        return $this->state_map[$index];
    }
}