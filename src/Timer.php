<?php

declare(strict_types=1);

namespace Bloatless\WebSocket;

final class Timer
{
    private $interval;
    private $task;
    private $lastRun;

    public function __construct(int $interval, callable $task)
    {
        $this->interval = $interval;
        $this->task = $task;
        $this->lastRun = 0;
    }

    public function run(): void
    {
        $now = round(microtime(true) * 1000);
        if ($now - $this->lastRun < $this->interval) {
            return;
        }

        $this->lastRun = $now;

        $task = $this->task;
        $task();
    }
}
