<?php

function parameter(array $required, array $values) {
    $res = [];
    foreach ($required as $key => $type) {

        if (str_ends_with($key, "?")) {
            $key = substr($key, 0, -1);
            if (!isset($values[$key])) {
                continue;
            }
        } else {
            if (!isset($values[$key])) {
                guard( "Missing parameter: $key", 2, "Bad Request");
            }
        }

        $res[$key] = match ($type) {
            'string'    => $values[$key],
            'string[]'  => (array)$values[$key],
            'int'       => (int)$values[$key],
            'bool'      => $values[$key] == "true",
            'real'      => (float)$values[$key],
            default     => $values[$key],
        };
    }

    return $res;
}

function required($value, int $code, string $errorname, string|null $reason = null) {
    if (!$value) { 
        global $out;
        $out->errno = $code;

        $out->errorname = $errorname;
        $out->error = match($reason) {
            null => "failed, because null value detect, contact your admin pls.",
            default => $reason
        };

        header("Content-Type: application/json");
        die(json_encode($out));
    }
}

function guard($error, int $code, string $errorname) {
    if ($error) { 
        global $out;
        $out->errno = $code;
        $out->error = $error;
        $out->errorname = $errorname;
        header("Content-Type: application/json");
        die(json_encode($out));
    }
}

function handleException(callable $action, string $err_reason) {
    try {
        $res = $action();
        return [
            "error" => false,
            "result" => $res
        ];
    } catch (\Exception $e) {
        $reason = str_to_html($e->getTraceAsString());
        $err_message = str_to_html($e->getMessage());

        $trace = str_to_html(<<<REASON
        $err_reason

        $err_message
        
        full stack trace:
        REASON);
        $trace .= "<p style=\"margin-left: 12px\">$reason</p>";
        return [
            "error" => true,
            "trace" => $trace
        ];
    }
}

function combine_filters(
    array $queries, 
    bool $specified_keywords,
    callable $make_filter
) {
    $likeClauses = [];
    $values = [];
    $types = "";
    foreach ($queries as $q) {
        $filter = $make_filter($q);
        $likeClauses[] = $filter[0];
        $a = array_fill(0, $filter[1], $specified_keywords ? $q : "%{$q}%");
        $values = array_merge($values, $a);
        $types .= str_repeat("s", $filter[1]);
    }
    return [
        "sql" => implode(" AND\n", $likeClauses),
        "values" => $values,
        "types" => $types
    ];
}

/**
 * Summary of build_insert_sql
 * @param array $fields
 * @param string $table
 * @return array{sql: string, types: string, values: array}
 */
function build_insert_sql(
    array $fields, 
    string $table,
    string $custom_id = null
) {
    if (!isset($fields['id']) && !$custom_id) {
        $fields['id'] = bin2hex(random_bytes(32));
    }

    $columns = array_keys($fields);
    $placeholders = array_fill(0, count($columns), '?');
    $sql = "INSERT INTO " . $table . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $types = '';
    foreach ($fields as $v) {
        $types .= match (gettype($v)) {
            'integer', 'boolean'    => 'i',
            'double'                => 'd',
            'NULL'                  => 's',
            default                 => 's',
        };
    }

    $values = array_values($fields);

    $res = [
        "sql" => $sql,
        "values" => $values,
        "types" => $types,
        "returning_table" => $table
    ];
    
    if ($custom_id) {
        $res["returning_id_field"] = $custom_id;
        $res["returning_new_id"] = $fields[$custom_id];
    } else {
        $res["returning_id_field"] = 'id';
        $res["returning_new_id"] = $fields['id'];
    }

    return $res;
}

/**
 * build SELECT SQL statement using such parameters
 * @param array $keywords: the keywords want search
 * @param string $select_statement: select statement in the very begin of sql statement
 * @param array $search_filter: the filter for keywords search:
 *      - 0(string): sql prepare string
 *      - 1(int): binding values count
 * @param bool $specified_keywords: make specified fit, if no, means like '%keyword%', yes means 'keyword'
 * @param bool|null $order_statement: order by XXX
 * @param callable $make_filter: the function tells how to make the extra filters
 *      - makeFilter returns array:
 *          - sql(string): sql prepare string
 *          - values(array<string>): values binding values
 *          - types(string): binding type string
 *          - next_op(string): next operation
 *      - null means no more filter
 * @return array{sql: string, types: string, values: array}: return combined sql statements:
 *      - sql(string): sql prepare string
 *      - values(array<string>): values binding values
 *      - types(string): binding type string
 */
function build_fetch_sql(
    array $keywords, 
    string $select_statement,
    array $search_filter,
    bool $specified_keywords,
    string|null $order_statement,
    callable $make_filter
) {
    $sql = $select_statement;
    $values = [];
    $types = "";

    $searchs = null;

    if (count($keywords) > 0) {
        $searchs = combine_filters(
            $keywords, 
            $specified_keywords,
            fn ($query) => [$search_filter[0], $search_filter[1]]
        );
    }

    $filters = $make_filter();

    if ($filters) {
        if ($searchs) {
            $sql .= " " .
                $filters['sql'] . 
                $filters['next_op'] .
                $searchs['sql'];
            $values = array_merge(
                $values, 
                $searchs['values'],
                $filters['values']
            );
            $types .= $searchs['types'] . 
                        $filters['types'];
        } else {
            $sql .= " " . $filters['sql'];
            $values = array_merge(
                $values, 
                $filters['values']
            );
            $types .= $filters['types'];
        }
    } else {
        if ($searchs) {
            $sql .= " WHERE " . $searchs["sql"];
            $values = array_merge($values, $searchs['values']);
            $types .= $searchs['types'];
        }
    }

    if ($order_statement) {
        $sql .= ' '. $order_statement;
    }

    return [
        "sql" => $sql,
        "values" => $values, 
        "types" => $types
    ];
}

function build_update_sql(
    string $id,
    array $values,
    string $table,
    string $custom_id = null
) {
    required(count($values) > 0,10, "Bad Request", "provide at least 1 data for update operation.");

    $sql = "UPDATE $table\n";

    $sql .= "SET\n";
    $placeholders = [];
    $vs = [];
    $types = "";

    foreach ($values as $key => $value) {
        $placeholders[] = "$key = ?";
        $vs[] = $value;
        $types .= match (gettype($value)) {
            'string'  => 's',
            'integer' => 'i',
            'double'  => 'd',
            'boolean' => 'i',
            'NULL'    => 's',
            default   => 's'
        };
    }

    $sql .= implode(",\n", $placeholders) . "\n";

    $id_field = $custom_id ?? "id";

    $sql .= "WHERE $id_field = ?";
    $vs[] = $id;
    $types .= "s";

    return [
        "sql" => $sql,
        "values" => $vs,
        "types" => $types
    ];
}

function build_delete_sql(
    array $ids,
    string $table,
    string $custom_id = null
) {
    required(count($ids) > 0,10, "Bad Request", "provide at least 1 id for delete operation.");

    $sql = "DELETE FROM $table WHERE ";

    $placeholders = implode(", ", array_fill(0, count($ids), "?"));
    $values = $ids;
    $types = str_repeat("s", count($ids));

    $id_field = $custom_id ?? "id";

    $sql .= "$id_field IN ($placeholders)";

    return [
        "sql" => $sql,
        "values" => $values,
        "types" => $types
    ];
}

function build_tail_sql(
    array $page_data,
    string $order_sql = "ORDER BY update_at",
) {
    $sql = $order_sql;

    $page = $page_data['page'] ?? null;
    $count = $page_data['count'] ?? null;

    if ($page !== null && $count !== null) {
        $offset = $count * $page;
        $sql .= " LIMIT $count OFFSET $offset";
    }

    return $sql;
}

function str_to_html(string $str) {
    $str = htmlspecialchars($str);
    $str = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $str);
    $str = str_replace("\r", '&nbsp;&nbsp;&nbsp;&nbsp;', $str);
    $str = str_replace("\n", '<br/>', $str);
    $str = str_replace("    ", '&nbsp;&nbsp;&nbsp;&nbsp;', $str);
    return $str;
}