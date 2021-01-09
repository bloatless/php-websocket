<?php

require __DIR__ . '/../src/Connection.php';
require __DIR__ . '/../src/IPCPayload.php';
require __DIR__ . '/../src/IPCPayloadFactory.php';
require __DIR__ . '/../src/Server.php';
require __DIR__ . '/../src/Timer.php';
require __DIR__ . '/../src/TimerCollection.php';

require __DIR__ . '/../src/Application/ApplicationInterface.php';
require __DIR__ . '/../src/Application/Application.php';
require __DIR__ . '/../src/Application/DemoApplication.php';
require __DIR__ . '/../src/Application/StatusApplication.php';

$server = new \Bloatless\WebSocket\Server('127.0.0.1', 8000, '/tmp/phpwss.sock');

// server settings:
$server->setMaxClients(100);
$server->setCheckOrigin(false);
$server->setAllowedOrigin('foo.lh');
$server->setMaxConnectionsPerIp(100);

// Hint: Status application should not be removed as it displays usefull server informations:
$server->registerApplication('status', \Bloatless\WebSocket\Application\StatusApplication::getInstance());
$server->registerApplication('demo', \Bloatless\WebSocket\Application\DemoApplication::getInstance());

$server->run();
