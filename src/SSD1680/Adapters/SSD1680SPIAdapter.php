<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Adapters;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680OpCode;
use Waveforms\Carriers\GPIO\GPIOBus;
use Waveforms\Carriers\SPI\SPIDevice;

class SSD1680SPIAdapter extends SSD1680DataCarrier
{
    public function __construct(
        SPIDevice $carrier,
        protected GPIOBus $gpio,
        protected int $max_packet_size,
        protected bool $has_busy = false,
    ) {
        parent::__construct($carrier);
    }

    public function reset(): void
    {
        $this->gpio->rst()->high();
        usleep(20000);

        $this->gpio->rst()->low();
        usleep(2000);

        $this->gpio->rst()->high();
        usleep(20000);
    }

    public function data(array $data): void
    {
        foreach (array_chunk($data, $this->max_packet_size) as $chunk) {
            $this->gpio->dc()->high();
            $this->carrier->write($chunk);
        }
    }

    public function command(SSD1680OpCode $register_hex, array $command_data = []): void
    {
        $this->gpio->dc()->low();
        $this->carrier->write([$register_hex->value]);

        if (count($command_data) > 0) {
            $this->data($command_data);
        }
    }

    /**
     * Block while the panel asserts its BUSY line.
     *
     * The SSD1680 drives BUSY high while it digests a command (software reset,
     * temperature read, display update). When a BUSY pin was wired up we poll
     * the GPIO input until it falls; otherwise we fall back to a conservative
     * fixed delay so the boot sequence still paces itself.
     */
    public function waitUntilIdle(int $timeout_us = 10000000): void
    {
        if (! $this->has_busy) {
            usleep(100000);

            return;
        }

        $interval_us = 10000;
        $waited_us = 0;

        while ($this->gpio->busy()->read() === 1) {
            usleep($interval_us);
            $waited_us += $interval_us;

            if ($waited_us >= $timeout_us) {
                break;
            }
        }
    }
}
