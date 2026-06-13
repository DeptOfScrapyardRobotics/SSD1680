<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Adapters;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680OpCode;
use Waveforms\Carriers\SPI\SPIDevice;

abstract class SSD1680DataCarrier
{
    public function __construct(
        protected SPIDevice $carrier
    ) {}

    abstract public function data(array $data): void;

    abstract public function command(SSD1680OpCode $register_hex, array $command_data = []): void;

    public function reset(): void {}

    public function waitUntilIdle(): void {}
}
