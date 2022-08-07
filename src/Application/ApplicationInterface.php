<?php

declare(strict_types=1);

namespace Bloatless\WebSocket\Application;

use Bloatless\WebSocket\Connection;

interface ApplicationInterface
{
    /**
     * This method is triggered when a new client connects to server/application.
     *
     * @param Connection $connection
     */
    public function onConnect(Connection $connection): void;

    /**
     * This method is triggered when a client disconnects from server/application.
     *
     * @param Connection $connection
     */
    public function onDisconnect(Connection $connection): void;

    /**
     * This method is triggered when the server receives new data from a client.
     *
     * @param string $data
     * @param Connection $client
     */
    public function onData(string $data, Connection $client): void;

    /**
     * This method is called when server receives to for an application on the IPC socket.
     *
     * @param array $data
     */
    public function onIPCData(array $data): void;

    /**
     * Creates and returns a new instance of the application.
     *
     * @return ApplicationInterface
     */
    public static function getInstance(): ApplicationInterface;
}
