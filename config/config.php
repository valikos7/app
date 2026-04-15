
<?php
// config/config.php

define('BASE_URL', 'http://localhost/app/');
define('APP_NAME', 'Война Племён');

$config = [
    'db' => [
        'host'     => '127.0.0.1',
        'user'     => 'root',
        'password' => '',   // ← поменяй на свой
        'dbname'   => 'voen',
        'charset'  => 'utf8mb4'
    ],
    'game' => [
        'speed'        => 5.0,
        'server_name'  => 'Мир 1',
        'start_time'   => time()
    ]
];
