<?php
require_once "basic.php";
require_once "db.php";
require_once "insert.php";
require_once "query.php";
require_once "delete.php";
class Auth extends DB {
    public function register(array $data) {
        $register_para = parameter([
            "email" => "string",
            "password_hashed" => "string",
            "name" => "string",
            "contact_number" => "string",
            "messenger_id?"=> "string",
            "description?" => "string"
        ], $data);

        $register_para['working'] = true;
        $insert = new DB_INSERT($this->conn);
        $query = new DB_QUERY($this->conn);

        $users_existed = $query->get([
            "ids" => $register_para['email']
        ], "users", "email");
        required(count($users_existed) == 0, 25, "user existed");

        $this->conn->begin_transaction();
        $user = $insert->user($register_para);
        $register_para['id'] = $user['id'];
        $insert->employee($register_para);
        $this->conn->commit();

        return true;
    }

    public function login(array $data) {
        $login_para = parameter([
            "email" => "string",
            "password_hashed" => "string"
        ], $data);

        $query = new DB_QUERY($this->conn);
        $insert = new DB_INSERT($this->conn);
        $delete = new DB_DELETE($this->conn);

        $users = $query->get([
            "ids" => $login_para['email']
        ], "users", "email");
        required(count($users) && $users[0], 20, "user not exist");
        $user = $users[0];
        required($login_para['password_hashed'] == $user['password_hashed'], 21, "password incorrect");

        $this->conn->begin_transaction();
        $delete->delete([
            "ids" => [$user['id']]
        ], "sessions", "user_id");
        $session = $insert->session([
            "user_id" => $user["id"]
        ]);

        $this->conn->commit();

        return $session;
    }

    public function logout(array $data) {
        $this->auth($data);

        $login_out_data = parameter([
            "session_id" => "string"
        ], $data);

        $delete = new DB_DELETE($this->conn);

        $delete->delete([
            "ids" => [$login_out_data["session_id"]]
        ], "sessions");

        return true;
    }

    public function auth(array $data) {
        required(isset($data['session_id']), 26, "not login");

        $query = new DB_QUERY($this->conn);
        $delete = new DB_DELETE($this->conn);

        $sessions = $query->get([
            "ids" => $data['session_id']
        ], "sessions");
        required(count($sessions) && $sessions[0], 23,"session invalid");
        $session = $sessions[0];

        $lastUpdate = strtotime($session['create_at']);
        $expired = $lastUpdate + 7 * 24 * 60 * 60 < time();

        if ($expired) {
            $delete->delete([
                "ids" => [$data["session_id"]]
            ], "sessions");

            required(false, 24, "user session expired");
        }

        return true;
    }
}