<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Transports;

use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;
use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport;
use GeneralPurposeIO\Contracts\SPI\SPITransport;

/** 4-wire SPI: DC low for the command byte, high for its parameters and RAM data; RST, BUSY and an optional PWR line. */
class SSD1680SPITransport extends SSD1680DataTransport
{
    public function __construct(
        protected SPITransport $transport,
        protected DigitalOutTransport $dc,
        protected DigitalOutTransport $rst,
        protected DigitalInTransport $busy,
        protected ?DigitalOutTransport $pwr = null,
        int $max_packet_size = 4096,
    ) {
        parent::__construct($max_packet_size);
    }

    protected function sendCommand(int $register, array $command_data = []): int
    {
        $this->dc->low();
        $written = $this->checked(sprintf('command 0x%02X', $register), 1, $this->transport->write([$register]));

        if (count($command_data) > 0) {
            $this->sendData($command_data);
        }

        return $written;
    }

    protected function sendData(array|string $data = []): void
    {
        $this->dc->high();

        if (is_string($data)) {
            foreach (str_split($data, $this->max_packet_size) as $chunk) {
                $this->checked('data', strlen($chunk), $this->transport->write($chunk));
            }

            return;
        }

        foreach (array_chunk($data, $this->max_packet_size) as $chunk) {
            $this->checked('data', count($chunk), $this->transport->write($chunk));
        }
    }

    /** RES# high 20 ms, low 2 ms, high 20 ms, as the panel vendor drives it. */
    public function reset(): void
    {
        $this->rst->high();
        usleep(20_000);

        $this->rst->low();
        usleep(2_000);

        $this->rst->high();
        usleep(20_000);
    }

    public function busy(): bool
    {
        return $this->busy->read();
    }

    public function power(bool $on): void
    {
        $this->pwr?->write($on);
    }

    /** Release DC, RST, BUSY and PWR. The bus connection belongs to its driver and stays open. */
    public function close(): void
    {
        $this->dc->close();
        $this->rst->close();
        $this->busy->close();
        $this->pwr?->close();
    }
}
