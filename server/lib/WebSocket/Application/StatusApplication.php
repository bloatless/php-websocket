<?php

declare(strict_types=1);

namespace WebSocket\Application;

use WebSocket\Connection;

/**
 * Shiny WSS Status Application
 * Provides live server infos/messages to client/browser.
 *
 * @author Simon Samtleben <web@lemmingzshadow.net>
 */
class StatusApplication extends Application
{
    private $clients = [];
    private $serverClients = [];
    private $serverInfo = [];
    private $serverClientCount = 0;


    public function onConnect(Connection $client)
    {
        $id = $client->getClientId();
        $this->clients[$id] = $client;
        $this->sendServerinfo($client);
    }

    public function onDisconnect(Connection $client)
    {
        $id = $client->getClientId();
        unset($this->clients[$id]);
    }

    public function onData(string $data, Connection $client)
    {
        // currently not in use...
    }

    public function setServerInfo(array $serverInfo)
    {
        if (is_array($serverInfo)) {
            $this->serverInfo = $serverInfo;
            return true;
        }
        return false;
    }


    public function clientConnected(string $ip, int $port)
    {
        $this->serverClients[$port] = $ip;
        $this->serverClientCount++;
        $this->statusMsg('Client connected: ' . $ip . ':' . $port);
        $data = [
            'ip' => $ip,
            'port' => $port,
            'clientCount' => $this->serverClientCount,
        ];
        $encodedData = $this->encodeData('clientConnected', $data);
        $this->sendAll($encodedData);
    }

    public function clientDisconnected(string $ip, int $port)
    {
        if (!isset($this->serverClients[$port])) {
            return false;
        }
        unset($this->serverClients[$port]);
        $this->serverClientCount--;
        $this->statusMsg('Client disconnected: ' . $ip . ':' . $port);
        $data = [
            'port' => $port,
            'clientCount' => $this->serverClientCount,
        ];
        $encodedData = $this->encodeData('clientDisconnected', $data);
        $this->sendAll($encodedData);
    }

    public function clientActivity(int $port)
    {
        $encodedData = $this->encodeData('clientActivity', $port);
        $this->sendAll($encodedData);
    }

    public function statusMsg($text, $type = 'info')
    {
        $data = [
            'type' => $type,
            'text' => '[' . strftime('%m-%d %H:%M', time()) . '] ' . $text,
        ];
        $encodedData = $this->encodeData('statusMsg', $data);
        $this->sendAll($encodedData);
    }

    private function sendServerinfo(Connection $client)
    {
        if (count($this->clients) < 1) {
            return false;
        }
        $currentServerInfo = $this->serverInfo;
        $currentServerInfo['clientCount'] = count($this->serverClients);
        $currentServerInfo['clients'] = $this->serverClients;
        $encodedData = $this->encodeData('serverInfo', $currentServerInfo);
        $client->send($encodedData);
    }

    private function sendAll(string $encodedData)
    {
        if (count($this->clients) < 1) {
            return false;
        }
        foreach ($this->clients as $sendto) {
            $sendto->send($encodedData);
        }
    }
}
