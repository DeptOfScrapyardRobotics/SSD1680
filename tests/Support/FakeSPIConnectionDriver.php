<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support;

use GeneralPurposeIO\SPI\SPIConnectionDriver;
use GeneralPurposeIO\SPI\SPIConnectionFactory;

/** Hands out FakeSPITransports and keeps each one, keyed device:chip_select. */
final class FakeSPIConnectionDriver extends SPIConnectionDriver
{
    /** @var list<string|int> every bus connectTo() opened */
    public array $opened = [];

    /** @var array<string, FakeSPITransport> */
    public array $slaves = [];

    protected function newConnection(int|string $device): SPIConnectionFactory
    {
        $this->opened[] = $device;

        return new FakeSPIConnectionFactory($device, $this);
    }

    protected function getTransport(int|string $device, int $chip_select): FakeSPITransport
    {
        return $this->slaves["{$device}:{$chip_select}"] = new FakeSPITransport(null, $chip_select);
    }

    protected function closeConnection(mixed $handle): void {}
}
