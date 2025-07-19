<?php
require_once "basic.php";
require_once "db.php";
require_once "insert.php";
require_once "query.php";
require_once "delete.php";
require_once "mailer.php";
require_once "update.php";
class Auth extends DB {
    public function register(array $data) {
        $register_para = parameter([
            "email" => "string",
            "password_hashed" => "string",
            "name" => "string",
            "contact_number" => "string",
            "email_verification_code" => "string",
            "messenger_id?"=> "string",
            "description?" => "string"
        ], $data);

        $register_para['working'] = true;
        $insert = new DB_INSERT($this->conn);
        $query = new DB_QUERY($this->conn);

        $users_existed = $query->get([
            "ids" => $register_para['email']
        ], "users", "email");
        required(count($users_existed) == 0, 25, "SignUp Failed", "user existed.");

        $this->verify_email([
            "email" => $register_para['email'],
            "code" => $register_para["email_verification_code"],
        ]);

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
        required(count($users) && $users[0], 20, "Login Failed", "user not exist.");
        $user = $users[0];
        required($login_para['password_hashed'] == $user['password_hashed'], 21, "Login Failed", "password incorrect.");

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
        $error_title = "Authorization Failed";

        required(isset($data['session_id']), 26, $error_title, "you are not login.");

        $query = new DB_QUERY($this->conn);
        $delete = new DB_DELETE($this->conn);

        $sessions = $query->get([
            "ids" => $data['session_id']
        ], "sessions");
        required(count($sessions) && $sessions[0], 23, $error_title,"login session invalid.");
        $session = $sessions[0];

        $last_update = strtotime($session['update_at']);
        $expired = ($last_update + 7 * 24 * 60 * 60) < time();

        if ($expired) {
            $delete->delete([
                "ids" => [$data["session_id"]]
            ], "sessions");

            required(false, 24, $error_title, "login session expired.");
        }

        return true;
    }

    public function forget_password(array $data) {
        $reset_para = parameter([
            "email" => "string",
            "password_hashed" => "string",
            "email_verification_code" => "string"
        ], $data);

        $error_title = "Password Reset Failed";

        $query = new DB_QUERY($this->conn);
        
        $users = $query->get([
            "ids" => [$reset_para['email']]
        ], "users", "email");
        required(count($users) && $users[0], 45, $error_title,"user not exist.");

        $this->verify_email([
            "email" => $reset_para['email'],
            "code" => $reset_para["email_verification_code"]
        ]);

        $update = new DB_UPDATE($this->conn);

        return $update->user([
            "email" => $reset_para["email"],
            "password_hashed" => $reset_para['password_hashed']
        ]);
    }

    public function reset_password(array $data) {

        $error_title = "Password Reset Failed";

        $this->auth($data);

        $reset_para = parameter([
            "old_password_hashed" => "string",
            "new_password_hashed" => "string"
        ], $data);

        $query = new DB_QUERY($this->conn);
        $update = new DB_UPDATE($this->conn);

        $user_current = $query->get_current([
            "session_id" => $data['session_id']
        ]);

        $users = $query->get([
            "ids" => [$user_current['email']]
        ], "users", "email");

        required(count($users) && $users[0], 45, $error_title,"user not exist.");

        $user = $users[0];
        required($user['password_hashed'] == $reset_para['old_password_hashed'], 46, $error_title, "old password incorrect.");

        return $update->user([
            "email" => $user_current["email"],
            "password_hashed" => $reset_para['new_password_hashed']
        ]);
    }

    public function verify_email(array $data) {
        $paras = parameter([
            "email" => "string",
            "code" => "string"
        ], $data);

        $error_title = "Email Verfication Failed";

        $query = new DB_QUERY($this->conn);
        $delete = new DB_DELETE($this->conn);

        $verifications = $query->email_verification_code([
            "emails" => [$paras['email']]
        ]);
        required(count($verifications) && $verifications[0] , 40, $error_title, "email verification code not sent.");

        $verification = $verifications[0];

        required($verification['code'] == $paras['code'], 41, $error_title, "verification code incorrect.");

        $last_update = strtotime($verification['update_at']);
        $expired = ($last_update + 5 * 60) < time();

        $delete->delete([
            "ids" => [$paras['email']],
        ], "email_validations", "email");
        
        required(!$expired, 42, $error_title, "email verification code expired.");

        return true;
    }
}