<?php

declare(strict_types=1);

namespace Bloatless\WebSocket;

final class TimerCollection
{
    /**
     * @var array $timers
     */
    private array $timers;

    public function __construct(array $timers = [])
    {
        $this->timers = $timers;
    }

    /**
     * Adds a timer.
     *
     * @param Timer $timer
     */
    public function addTimer(Timer $timer): void
    {
        $this->timers[] = $timer;
    }

    /**
     * Executes/runs all timers.
     */
    public function runAll(): void
    {
        foreach ($this->timers as $timer) {
            $timer->run();
        }
    }
}
