<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/lib/WebSocket/Connection.php';
require __DIR__ . '/lib/WebSocket/Socket.php';
require __DIR__ . '/lib/WebSocket/Server.php';

require __DIR__ . '/lib/WebSocket/Application/ApplicationInterface.php';
require __DIR__ . '/lib/WebSocket/Application/Application.php';
require __DIR__ . '/lib/WebSocket/Application/DemoApplication.php';
require __DIR__ . '/lib/WebSocket/Application/StatusApplication.php';

$server = new \WebSocket\Server('127.0.0.1', 8000);

// server settings:
$server->setMaxClients(100);
$server->setCheckOrigin(false);
$server->setAllowedOrigin('foo.lh');
$server->setMaxConnectionsPerIp(100);
$server->setMaxRequestsPerMinute(2000);

// Hint: Status application should not be removed as it displays usefull server informations:
$server->registerApplication('status', \WebSocket\Application\StatusApplication::getInstance());
$server->registerApplication('demo', \WebSocket\Application\DemoApplication::getInstance());

$server->run();
