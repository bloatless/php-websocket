<?php

declare(strict_types=1);

namespace WebSocket\Application;

use WebSocket\Connection;

/**
 * Websocket-Server demo and test application.
 *
 * @author Simon Samtleben <web@lemmingzshadow.net>
 */
class DemoApplication extends Application
{
    private $clients = [];

    public function onConnect(Connection $client)
    {
        $id = $client->getClientId();
        $this->clients[$id] = $client;
    }

    public function onDisconnect(Connection $client)
    {
        $id = $client->getClientId();
        unset($this->clients[$id]);
    }

    public function onData(string $data, Connection $client)
    {
        $decodedData = $this->decodeData($data);
        if ($decodedData === false) {
            // @todo: invalid request trigger error...
        }

        $actionName = 'action' . ucfirst($decodedData['action']);
        if (method_exists($this, $actionName)) {
            call_user_func([$this, $actionName], $decodedData['data']);
        }
    }

    private function actionEcho(string $text)
    {
        $encodedData = $this->encodeData('echo', $text);
        foreach ($this->clients as $sendto) {
            $sendto->send($encodedData);
        }
    }
}
