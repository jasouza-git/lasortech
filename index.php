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
/* Output */
$out = (object)[ 'errno'=>0 ];
/* Error */
function error($code, $msg) {
    global $out;
    $out->errno = $code;
    $out->error = $msg;
}
/* SQL Runner */
function sql_run($code, $arg='', $val=[], $err='', $pass='', $empty='') {
    global $sql, $out;
    // There is already error before, unsafe to continue
    if ($out->errno != 0) return false;
    // Try executing the Code
    try {
        $qy = $sql->prepare($code);
        if ($arg != '') $qy->bind_param($arg, ...$val);
        if (!$qy->execute()) {
            error(5, $err == '' ? $qy->error : $err);
            return [];
        }
    } catch (Exception $e) {
        error(5, $err == '' ? $e->getMessage() : $err);
        return [];
    }
    // Store result and bind columns dynamically
    $meta = $qy->result_metadata();
    // No result, likely INSERT, UPDATE, or DELETE
    if (!$meta) {
        $qy->close();
        return [];
    }

    // Parse into array of objects
    $fields = [];
    $row = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = &$row[$field->name];
    }
    call_user_func_array([$qy, 'bind_result'], $fields);
    $results = [];
    while ($qy->fetch()) {
        $results[] = (object) array_map(fn($v) => $v, $row); // copy by value
    }
    $qy->close();

    // Reverse error
    if ($pass != '' && count($results)) error(5, $pass);
    // Empty error
    if ($empty != '' && count($results) == 0) error(5, $empty);

    return $results;
}
/* SQL Server */
try {
    $sql = new mysqli($config->hostname, $config->username, $config->password);
} catch (Exception $e) {
    $out->error = $e->getMessage();
}
if (isset($out->error) || $sql->connect_error)
    error(3, 'SQL Server connection failure: ' . (isset($out->error) ? $out->error : $sql->connect_error));
/* Create Table */
else if ($sql->query('CREATE DATABASE IF NOT EXISTS ' . $config->database) !== TRUE)
    error(3, 'Database creation failure: ' . $sql->error);
else {
    $sql->select_db($config->database);
    foreach ($config->tables as $name => $code)
        if ($sql->query('CREATE TABLE IF NOT EXISTS ' . $name . ' ( ' . $code . ' ) ') !== TRUE)
            error(3, 'Table "' . $name . '" creation failure: ' . $sql->error);
}
/* APIs */
if ($_SERVER['REQUEST_URI'] === '/api' && $_SERVER['REQUEST_METHOD'] == 'POST') {

}

?>
<!DOCTYPE html>
<html>
    <head>
        <title>LasorTech RMA</title>
        <link rel="stylesheet" type="text/css" href="style.css">
    </head>
    <body class="load">
        <?php echo file_get_contents('logo.xml') ?>
        <div id="side">
            <?php
                foreach ($config->navigate as $name => $options) {
                    echo '<p>' . $name . ':</p>';
                    foreach ($options as $index => $value) {
                        echo '<button>' . $value . '</button>';
                    }
                    echo '<button>All</button>';
                }
            ?>
        </div>
        <div id="head">
            <span id="head_info0">XX Handling</span>
            <span id="head_info1">XX finished</span>
            <h1 id="head_name">Title</h1>
        </div>
        <div id="body">
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
                <button id="new"></button>
            </div>
            <div id="page">
                <div class="card">
                    <div>
                        <h1>Order ID</h1>
                        <h2>Update Date / Create Date</h2>
                        <div>
                            <table>
                                <tr><th>Name</th><th>Brand</th><th>Mode</th><th>Date</th></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td></tr>
                            </table>
                        </div>
                        <p>Description</p>
                    </div>
                    <div>
                        <h1>Customer Name</h2>
                        <p>Customer Description</p>
                        <a class="fb">jkergre</a>
                        <a class="cn">090230944545</a>
                        <a class="em">erger@hnrh.vof</a>
                    </div>
                    <div>
                        <div data="0"></div>
                        <p>Status description</p>
                        <h1>RMS CODE</h1>
                    </div>
                </div>
            </div>
        </div>
        <div id="tabs">
            <?php
                for ($n = 0; $n < 5; $n++) {
                    echo '<button>' . $n . '</button>';
                }
                echo '<button class="more"></button>';
                for ($n = 24; $n < 29; $n++) {
                    echo '<button>' . $n . '</button>';
                }
                echo '<div>10</div>'
            ?>
        </div>
        <script src="script.js"></script>
    </body>
</html>