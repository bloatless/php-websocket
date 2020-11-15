<?php

declare(strict_types=1);

namespace Bloatless\WebSocket;

final class Timer
{
    /**
     * @var int $interval
     */
    private $interval;

    /**
     * @var callable $task
     */
    private $task;

    /**
     * @var int $lastRun
     */
    private $lastRun;

    public function __construct(int $interval, callable $task)
    {
        $this->interval = $interval;
        $this->task = $task;
        $this->lastRun = 0;
    }

    /**
     * Executes the timer if intervall has passed.
     *
     * @return void
     */
    public function run(): void
    {
        $now = round(microtime(true) * 1000);
        if ($now - $this->lastRun < $this->interval) {
            return;
        }

        $this->lastRun = $now;
        call_user_func($this->task);
    }
}
