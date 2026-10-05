<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Transports;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Exception;
use GeneralPurposeIO\Contracts\IntegratedCircuits\DataCommander;

/**
 * Commands and RAM bytes to the panel, its reset line, and its BUSY line. Every write is checked: a short or failed
 * write throws. SPI has no acknowledge, so a missing panel shows up as BUSY never falling, not as a write error.
 */
abstract class SSD1680DataTransport implements DataCommander
{
    public function __construct(
        protected int $max_packet_size
    ) {
        $this->maxPacketSize($max_packet_size);
    }

    abstract protected function sendData(array|string $data = []): void;
    abstract protected function sendCommand(int $register, array $command_data = []): int;

    /** Pulse the hardware reset line. */
    abstract public function reset(): void;

    /** True while the controller holds BUSY high. */
    abstract public function busy(): bool;

    /** Switch the panel's supply, where the board has a power-enable line. */
    abstract public function power(bool $on): void;

    abstract public function close(): void;

    public function command(int $register, array $command_data = []): int
    {
        return $this->sendCommand($register, $command_data);
    }

    public function data(array|string $data = []): void
    {
        $this->sendData($data);
    }

    /** Bytes per data write; SPI adapters split a longer write into the bus's own message size. */
    public function maxPacketSize(int $size): static
    {
        if ($size < 1) {
            throw SSD1680Exception::invalidPacketSize($size);
        }

        $this->max_packet_size = $size;

        return $this;
    }

    /**
     * Block until BUSY falls, reading it every millisecond.
     *
     * @throws SSD1680Exception when BUSY stays high for $timeout_ms
     */
    public function waitUntilIdle(int $timeout_ms): void
    {
        $deadline = hrtime(true) + $timeout_ms * 1_000_000;

        while ($this->busy()) {
            if (hrtime(true) >= $deadline) {
                throw SSD1680Exception::busyTimeout($timeout_ms);
            }

            usleep(1_000);
        }
    }

    /** @throws SSD1680Exception when the bus wrote fewer bytes than asked */
    protected function checked(string $what, int $expected, int $written): int
    {
        if ($written !== $expected) {
            throw SSD1680Exception::spiWriteFailed($what, $expected, $written);
        }

        return $written;
    }
}
