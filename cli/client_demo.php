<?php
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../src/Client.php';

$clients = [];
$testClients = 30;
$testMessages = 500;
for ($i = 0; $i < $testClients; $i++) {
    $clients[$i] = new \Bloatless\WebSocket\Client;
    $clients[$i]->connect('127.0.0.1', 8000, '/demo', 'foo.lh');
}
usleep(5000);

$payload = json_encode([
    'action' => 'echo',
    'data' => 'dos',
]);

for ($i = 0; $i < $testMessages; $i++) {
    $clientId = rand(0, $testClients-1);
    $clients[$clientId]->sendData($payload);
    usleep(5000);
}
usleep(5000);
    */


require __DIR__ . '/../src/IPCPayloadFactory.php';
require __DIR__ . '/../src/IPCPayload.php';

$payload = \Bloatless\WebSocket\IPCPayloadFactory::makeApplicationPayload('demo', [
    'action' => 'echo',
    'data' => 'Hello from the IPC Socket!',
]);

$dataToSend = $payload->asJson();
$dataLength = strlen($dataToSend);
$socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
socket_sendto($socket, $dataToSend, $dataLength, MSG_EOF, '/tmp/phpwss.sock', 0);
socket_close($socket);
