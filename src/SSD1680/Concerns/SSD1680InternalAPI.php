<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Concerns;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680OpCode;

trait SSD1680InternalAPI
{
    protected function command(SSD1680OpCode $register_hex, array $command_data = []): void
    {
        $this->carrier->command($register_hex, $command_data);
    }

    protected function data(array $data): void
    {
        $this->carrier->data($data);
    }

    protected function waitUntilIdle(): void
    {
        $this->carrier->waitUntilIdle();
    }
}
