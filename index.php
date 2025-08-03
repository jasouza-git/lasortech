<?php
/* Configurations */
$config = (object)[
    'database' => 'lasortech',
    'hostname' => '127.0.0.1',
    'username' => 'root',
    'password' => '',
    'tables' => (object)[
        /* Items */
        'items' => '
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
            customer    INT UNSIGNED NOT NULL,
            brand       VARCHAR(100),
            model       VARCHAR(100),
            name        VARCHAR(100),
            description VARCHAR(100)
        ',
        /* Customers */
        'customer' => '
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
            name        VARCHAR(100),
            contact     VARCHAR(20),
            email       VARCHAR(20),
            messenger   VARCHAR(20),
            description VARCHAR(100)
        '
    ],
    'navigate' => (object)[
        'Orders' => ['Ongoing', 'Finished'],
        'Customers' => ['Pending', 'Finished'],
        'Items' => ['Handling', 'Finished'],
        'Employees' => ['Retired', 'Working']
    ]
];
?>
<?php /* ---- PRINTING ----- */
if (explode('/', $_SERVER['REQUEST_URI'])[1] == 'print') die(<<<HTML
    <button>Hi</button>
HTML);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>LasorTech RMA</title>
        <link rel="stylesheet" type="text/css" href="/style.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="theme-color" content="#ee0000">
    </head>
    <body class="load">
        <?php echo file_get_contents('logo.xml') ?>
        <div id="side">
            <?php
                foreach ($config->navigate as $name => $options) {
                    echo '<p>' . $name . ':</p>';
                    echo '<button data="' . strtolower($name) . '/all">All</button>';
                    foreach ($options as $index => $value) {
                        echo '<button data="' . strtolower($name) . '/' . strtolower($value) . '">' . $value . '</button>';
                    }
                }
            ?>
            <p>User: <span id="user_name"></span></p>
            <button data="logout">Logout</button>
        </div>
        <div id="head">
            <span id="head_info0">XX Handling</span>
            <span id="head_info1">XX finished</span>
            <h1 id="head_name">LasorTech RMA System</h1>
        </div>
        <div id="find">
            <input id="find_text" placeholder="Find in System:"></input>
            <?php
                foreach ($config->navigate as $name => $options) {
                    $id = preg_replace('/\s+/', '_', strtolower($name));
                    echo '<div id="find_' . $id . '"><div><input id="find_' . $id . '_all" type="checkbox" /><label for="find_' . $id . '_all">' . $name . '</label></div>';
                    foreach ($options as $index => $value) {
                        $id2 = preg_replace('/\s+/', '_', strtolower($value));
                        echo '<div><input id="find_' . $id . '_' . $id2 . '" type="checkbox" value="find_' . $id . '_' . $id2 . '"/><label for="find_' . $id . '_' . $id2 . '">' . $value . '</label></div>';
                    }
                    echo '</div>';
                }
            ?>
            <button id="search"></button>
        </div>
        <div id="body">
        </div>
        <?php echo file_get_contents('logo2.xml') ?>
        <div id="tabs">
        </div>
        <div id="login">
            <div id="login_forgot">
                <h1><icon>&#xf0e0;</icon>E-Mail</h1><input id="login_forgot_email"></input>
                <button id="login_forgot_sendcode" onclick="action['send_code'](this)">Send Verification Code</button>
                <div id="login_forgot_verify" style="display:none">
                    <h1><icon>&#xf00c;</icon>Verification Code</h1><input id="login_forgot_code"></input>
                    <h1><icon>&#xf084;</icon>New Password</h1><input id="login_forgot_pass" type="password"></input>
                    <button onclick="action['change_pass'](this)">Change Password</button>
                </div>
            </div>
            <div class="signup">
                <h1><icon>&#xf507;</icon>Name</h1><input name="name"></input>
                <h1><icon>&#xf2bb;</icon>Contact Number</h1><input name="contact"></input>
                <h1><icon>&#xf0e0;</icon>E-Mail</h1><input name="email"></input>
                <h1><icon>&#xf084;</icon>Password</h1><input name="pass" type="password"></input>
                <h1><icon>&#xf084;</icon>Password (Repeat)</h1><input name="pass2" type="password"></input>
                <button onclick="action['signup_verify'](this)">Get Verification Code</button>
                <div id="signup_verify" style="display:none">
                    <h1><icon>&#xf00c;</icon>Verification Code</h1><input name="code"></input>
                    <button onclick="action['signup']()">Signup</button>
                </div>
            </div>
            <div class="login on">
                <h1><icon>&#xf0e0;</icon>E-Mail</h1><input name="email"></input>
                <h1><icon>&#xf084;</icon>Password</h1><input name="pass" type="password"></input>
                <button onclick="action['login'](this)">Login</button>
                <p></p>
            </div>
            <div>
                <button><icon>&#xe243;</icon>Forgot Password</button>
                <button><icon>&#xf234;</icon>Signup</button>
                <button class="on"><icon>&#xf2f6;</icon>Login</button>
            </div>
        </div>
        <div id="popup">
        </div>
        <script src="/script.js"></script>
    </body>
</html>