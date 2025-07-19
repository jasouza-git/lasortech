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
<!DOCTYPE html>
<html>
    <head>
        <title>LasorTech RMA</title>
        <link rel="stylesheet" type="text/css" href="/style.css">
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
            <button id="new"></button>
        </div>
        <div id="body">
            <table>
                <tr><th>Name</th><th>Brand</th><th>Mode</th><th>Date</th><th class="edit">Options</th></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><div><button>&#xe2b4;</button><button>&#xf304;</button><button>&#xf0c7;</button></div></td></tr>
                <tr class="edit"><td colspan="5"><div><button>+</button></div></td></tr>
            </table>
            <!--
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
            
            <div class="card edit">
                <div>
                    <h1>Order ID</h1>
                    <h2>Update Date / Create Date</h2>
                    <div>
                        <table>
                            <tr><th>Name</th><th>Brand</th><th>Mode</th><th>Date</th><th class="edit">Delete</th></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr><td>Acer Nitro 5</td><td>Acer</td><td>Laptop</td><td>July 16, 2025</td><td class="edit"><button>-</button></td></tr>
                            <tr class="edit"><td colspan="5"><button>+</button></td></tr>
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
            </div>-->
        </div>
        <?php echo file_get_contents('logo2.xml') ?>
        <div id="tabs">
            <?php
            /*
                echo '<button class="on">1</button>';
                for ($n = 2; $n < 5; $n++) {
                    echo '<button>' . $n . '</button>';
                }
                echo '<button class="more"></button>';
                for ($n = 24; $n < 29; $n++) {
                    echo '<button>' . $n . '</button>';
                }
                echo '<div contenteditable="true">10</div>'*/
            ?>
        </div>
        <div id="login">
            <div id="login_forgot">
                <h1><icon>&#xf0e0;</icon>E-Mail</h1><input id="login_forgot_email"></input>
                <button onclick="action['verification']()">Send Verification Code</button>
            </div>
            <div class="signup">
                <h1><icon>&#xf507;</icon>Name</h1><input name="name"></input>
                <h1><icon>&#xf2bb;</icon>Contact Number</h1><input name="contact"></input>
                <h1><icon>&#xf0e0;</icon>E-Mail</h1><input name="email"></input>
                <h1><icon>&#xf084;</icon>Password</h1><input name="pass" type="password"></input>
                <h1><icon>&#xf084;</icon>Password (Repeat)</h1><input name="pass2" type="password"></input>
                <button onclick="action['signup']()">Signup</button>
                <p></p>
            </div>
            <div class="login on">
                <h1><icon>&#xf0e0;</icon>E-Mail</h1><input name="email"></input>
                <h1><icon>&#xf084;</icon>Password</h1><input name="pass" type="password"></input>
                <button onclick="action['login']()">Login</button>
                <p></p>
            </div>
            <div>
                <button><icon>&#xe243;</icon>Forgot Password</button>
                <button><icon>&#xf234;</icon>Signup</button>
                <button class="on"><icon>&#xf2f6;</icon>Login</button>
            </div>
        </div>
        <div id="popup">
            <div class="warning">
                <h1>Are you sure you want to delete?</h1>
                <p>You will be deleting "ABDJFNKJF"</p>
                <button>No</button><button>Yes</button>
            </div>
        </div>
        <script src="/script.js"></script>
    </body>
</html>