<?php

declare(strict_types=1);

namespace Bloatless\WebSocket\Application;

use Bloatless\WebSocket\Connection;

class StatusApplication extends Application
{
    /**
     * Holds client connected to the status application.
     *
     * @var array $clients
     */
    private array $clients = [];

    /**
     * Holds IP/Port information of all clients connected to the server.
     *
     * @var array $serverClients
     */
    private array $serverClients = [];

    /**
     * Basic server infos (like max. clients e.g.)
     *
     * @var array $serverInfo
     */
    private array $serverInfo = [];

    /**
     * Total number of connected clients.
     *
     * @var int $serverClientCount
     */
    private int $serverClientCount = 0;

    /**
     * Handles new connections to the application.
     *
     * @param Connection $connection
     * @return void
     */
    public function onConnect(Connection $connection): void
    {
        $id = $connection->getClientId();
        $this->clients[$id] = $connection;
        $this->sendServerInfo($connection);
    }

    /**
     * Handles client disconnects from the application.
     *
     * @param Connection $connection
     * @return void
     */
    public function onDisconnect(Connection $connection): void
    {
        $id = $connection->getClientId();
        unset($this->clients[$id]);
    }

    /**
     * This application does not expect any incoming client data.
     *
     * @param string $data
     * @param Connection $client
     * @return void
     */
    public function onData(string $data, Connection $client): void
    {
        // currently not in use...
    }

    public function onIPCData(array $data): void
    {
        // TODO: Implement onIPCData() method.
    }

    /**
     * Sets basic server data.
     *
     * @param array $serverInfo
     * @return bool
     */
    public function setServerInfo(array $serverInfo): bool
    {
        $this->serverInfo = $serverInfo;
        return true;
    }

    /**
     * This method is called by the server whenever a client connects (to server).
     *
     * @param string $ip
     * @param int $port
     * @return void
     */
    public function clientConnected(string $ip, int $port): void
    {
        $client = $ip . ':' . $port;
        $this->serverClients[$client] = true;
        $this->serverClientCount++;
        $this->statusMsg('Client connected: ' . $client);
        $data = [
            'client' => $client,
            'clientCount' => $this->serverClientCount,
        ];
        $encodedData = $this->encodeData('clientConnected', $data);
        $this->sendAll($encodedData);
    }

    /**
     * This method is called by the server whenever a client disconnects (from server).
     *
     * @param string $ip
     * @param int $port
     * @return void
     */
    public function clientDisconnected(string $ip, int $port): void
    {
        $client = $ip . ':' . $port;
        if (!isset($this->serverClients[$client])) {
            return;
        }
        unset($this->serverClients[$client]);
        $this->serverClientCount--;
        $this->statusMsg('Client disconnected: ' . $client);
        $data = [
            'client' => $client,
            'clientCount' => $this->serverClientCount,
        ];
        $encodedData = $this->encodeData('clientDisconnected', $data);
        $this->sendAll($encodedData);
    }

    /**
     * This method will be called by server whenever there is activity on a port.
     *
     * @param string $client
     * @return void
     */
    public function clientActivity(string $client): void
    {
        $encodedData = $this->encodeData('clientActivity', $client);
        $this->sendAll($encodedData);
    }

    /**
     * Sends a status message to all clients connected to the application.
     *
     * @param string $text
     * @param string $type
     */
    public function statusMsg(string $text, string $type = 'info'): void
    {
        $data = [
            'type' => $type,
            'text' => '[' . date('m-d H:i') . '] ' . $text,
        ];
        $encodedData = $this->encodeData('statusMsg', $data);
        $this->sendAll($encodedData);
    }

    /**
     * Sends server information to a client.
     *
     * @param Connection $client
     * @return void
     */
    private function sendServerInfo(Connection $client): void
    {
        if (count($this->clients) < 1) {
            return;
        }
        $currentServerInfo = $this->serverInfo;
        $currentServerInfo['clientCount'] = count($this->serverClients);
        $currentServerInfo['clients'] = $this->serverClients;
        $encodedData = $this->encodeData('serverInfo', $currentServerInfo);
        $client->send($encodedData);
    }

    /**
     * Sends data to all clients connected to the application.
     *
     * @param string $encodedData
     * @return void
     */
    private function sendAll(string $encodedData): void
    {
        if (count($this->clients) < 1) {
            return;
        }
        foreach ($this->clients as $client) {
            $client->send($encodedData);
        }
    }
}
