<?php

declare(strict_types=1);

namespace Bloatless\WebSocket;

class IPCPayloadFactory
{
    /**
     * Creates payload to execute a server command.
     *
     * @param string $command
     * @param array $data
     * @return IPCPayload
     */
    public static function makeServerPayload(string $command, array $data = []): IPCPayload
    {
        return new IPCPayload(IPCPayload::TYPE_SERVER, $command, $data);
    }

    /**
     * Creates payload to push data into an application.
     *
     * @param string $applicationName
     * @param array $data
     * @return IPCPayload
     */
    public static function makeApplicationPayload(string $applicationName, array $data): IPCPayload
    {
        return new IPCPayload(IPCPayload::TYPE_APPLICATION, $applicationName, $data);
    }

    /**
     * Creates payload object from json encoded string.
     *
     * @param string $json
     * @return IPCPayload
     */
    public static function fromJson(string $json): IPCPayload
    {
        $data = json_decode($json, true);
        return match ($data['type']) {
            IPCPayload::TYPE_SERVER => new IPCPayload(IPCPayload::TYPE_SERVER, $data['action'], $data['data']),
            IPCPayload::TYPE_APPLICATION => new IPCPayload(IPCPayload::TYPE_APPLICATION, $data['action'], $data['data']),
            default => throw new \RuntimeException('Can not create IPCPayload from invalid data type.'),
        };
    }
}
