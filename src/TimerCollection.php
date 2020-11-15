<?php

declare(strict_types=1);

namespace Bloatless\WebSocket;

final class TimerCollection
{
    /**
     * @var Timer[]
     */
    private $timers;

    public function __construct(array $timers = [])
    {
        $this->timers = $timers;
    }

    public function addTimer(Timer $timer)
    {
        $this->timers[] = $timer;
    }

    public function runAll(): void
    {
        foreach ($this->timers as $timer) {
            $timer->run();
        }
    }
}
