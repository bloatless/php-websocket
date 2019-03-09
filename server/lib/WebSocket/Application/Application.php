<?php

declare(strict_types=1);

namespace WebSocket\Application;

/**
 * WebSocket Server Application
 *
 * @author Nico Kaiser <nico@kaiser.me>
 */
abstract class Application implements ApplicationInterface
{
    protected static $instances = [];

    /**
     * Singleton
     */
    protected function __construct()
    {
    }

    final private function __clone()
    {
    }

    final public static function getInstance(): ApplicationInterface
    {
        $calledClassName = get_called_class();
        if (!isset(self::$instances[$calledClassName])) {
            self::$instances[$calledClassName] = new $calledClassName();
        }

        return self::$instances[$calledClassName];
    }

    protected function decodeData($data)
    {
        $decodedData = json_decode($data, true);
        if ($decodedData === null) {
            return false;
        }

        if (isset($decodedData['action'], $decodedData['data']) === false) {
            return false;
        }

        return $decodedData;
    }

    protected function encodeData($action, $data)
    {
        if (empty($action)) {
            return false;
        }

        $payload = [
            'action' => $action,
            'data' => $data
        ];

        return json_encode($payload);
    }
}
