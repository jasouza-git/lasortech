<?php
require_once __DIR__ . "/basic.php";
require_once "db.php";
class DB_DELETE extends DB {

    public function delete(array $data, string $table) {
        required(
            isset($data['ids']) && count($data['ids']) > 0, 
            15, 
            "provide at least one id of $table to delete"
        );
        
        $combined = build_delete_sql($data['ids'], $table);
        return $this->execute($combined);
    }
}