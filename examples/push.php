<?php

require __DIR__ . '/../src/IPCPayload.php';
require __DIR__ . '/../src/IPCPayloadFactory.php';
require __DIR__ . '/../src/PushClient.php';

$pushClient = new \Bloatless\WebSocket\PushClient('//tmp/phpwss.sock');
$pushClient->sendToApplication('chat', [
    'action' => 'echo',
    'data' => 'Hello from the PushClient!',
]);
