<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support;

use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Digital\DigitalIOConnectionDriver;
use GeneralPurposeIO\Digital\DigitalIOConnectionFactory;

/** Hands out one FakeOutputPin or FakeBusyPin per device and pin. */
final class FakeDigitalIOConnectionDriver extends DigitalIOConnectionDriver
{
    /** @var list<string|int> every device connectTo() opened */
    public array $opened = [];

    /** @var array<string, FakeOutputPin> */
    public array $outputs = [];

    /** @var array<string, FakeBusyPin> */
    public array $inputs = [];

    protected function newConnection(int|string $device): DigitalIOConnectionFactory
    {
        $this->opened[] = $device;

        return new FakeDigitalIOConnectionFactory($device, $this);
    }

    protected function getInputTransport(string|int $device, int $pin, LineBias $bias = LineBias::AS_IS, bool $active_low = false): FakeBusyPin
    {
        return $this->inputs["{$device}:{$pin}"] ??= new FakeBusyPin($pin);
    }

    protected function getOutputTransport(string|int $device, int $pin): FakeOutputPin
    {
        return $this->outputs["{$device}:{$pin}"] ??= new FakeOutputPin($pin);
    }

    protected function closeConnection(mixed $handle): void {}
}
