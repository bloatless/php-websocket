<?php

declare(strict_types=1);

namespace Bloatless\WebSocket;

class IPCPayload
{
    public const TYPE_SERVER = 1;

    public const TYPE_APPLICATION = 2;

    public int $type;

    public string $action;

    public array $data;

    public function __construct(int $type, string $action, array $data = [])
    {
        $this->type = $type;
        $this->action = $action;
        $this->data = $data;
    }

    public function asJson(): string
    {
        return json_encode([
            'type' => $this->type,
            'action' => $this->action,
            'data' => $this->data,
        ]);
    }
}
