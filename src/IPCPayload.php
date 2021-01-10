<?php

declare(strict_types=1);

namespace Bloatless\WebSocket;

class IPCPayload
{
    public const TYPE_SERVER = 1;

    public const TYPE_APPLICATION = 2;

    /**
     * Defines if message is a server command or application data
     *
     * @var int $type
     */
    public int $type;

    /**
     * Server command to execute or application name to pass data to.
     *
     * @var string $action
     */
    public string $action;

    /**
     * Actual payload data.
     *
     * @var array $data
     */
    public array $data;

    public function __construct(int $type, string $action, array $data = [])
    {
        $this->type = $type;
        $this->action = $action;
        $this->data = $data;
    }

    /**
     * Returns payload as json encoded string.
     *
     * @return string
     */
    public function asJson(): string
    {
        return json_encode([
            'type' => $this->type,
            'action' => $this->action,
            'data' => $this->data,
        ]);
    }
}
