<?php

declare(strict_types=1);

namespace WebSocket\Application;

use WebSocket\Connection;

interface ApplicationInterface
{
    public function onConnect(Connection $connection);

    public function onDisconnect(Connection $connection);

    public function onData(string $data, Connection $client);

    public static function getInstance(): ApplicationInterface;
}
