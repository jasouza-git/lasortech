<?php
require_once "basic.php";
require_once "db.php";
class DB_DELETE extends DB {

    public function delete(array $data, string $table, string $custom_id = null) {
        required(
            isset($data['ids']) && count($data['ids']) > 0, 
            15, 
            "provide at least one id of $table to delete"
        );

        $combined = build_delete_sql($data['ids'], $table, $custom_id);
        return $this->execute($combined);
    }
}