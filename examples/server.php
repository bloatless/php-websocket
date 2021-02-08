<?php

require __DIR__ . '/../vendor/autoload.php';

$server = new \Bloatless\WebSocket\Server('127.0.0.1', 8000, '/tmp/phpwss.sock');

// add a PSR-3 compatible logger (optional)
$server->setLogger(new \Bloatless\WebSocket\Logger\StdOutLogger());

// server settings
$server->setMaxClients(100);
$server->setCheckOrigin(false);
$server->setAllowedOrigin('foo.lh');
$server->setMaxConnectionsPerIp(100);

// add your applications
$server->registerApplication('status', \Bloatless\WebSocket\Application\StatusApplication::getInstance());
$server->registerApplication('chat', \Bloatless\WebSocketExamples\Application\Chat::getInstance());

$server->run();
