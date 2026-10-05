<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support;

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Digital\DigitalInputTransport;

/** BUSY with a scripted level: each read() takes the next of $script, then $level once the script runs out. */
final class FakeBusyPin extends DigitalInputTransport
{
    /** @var list<bool> */
    public array $script = [];

    public bool $level = false;

    public int $reads = 0;

    public bool $released = false;

    public function handle(): string
    {
        return 'fake';
    }

    public function read(): bool
    {
        $this->reads++;

        return array_shift($this->script) ?? $this->level;
    }

    public function pollEdges(bool $rising_events, bool $falling_events): array
    {
        return [];
    }

    public function listen(int $timeout, bool $rising_events, bool $falling_events): ?DigitalEdgeEvent
    {
        return null;
    }

    protected function drainEdges(): array
    {
        return [];
    }

    protected function awaitEdges(int $timeout_ms): void {}

    protected function edgeStreams(): array
    {
        return [];
    }

    protected function samplingInterval(): ?float
    {
        return null;
    }

    protected function release(): void
    {
        $this->released = true;
    }
}
