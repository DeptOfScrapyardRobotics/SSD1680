<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support;

use GeneralPurposeIO\SPI\SPITransport;

/** Records every write, tagged with the DC level it went out under once a DC pin is attached; 'raw' before. */
final class FakeSPITransport extends SPITransport
{
    /** @var list<array{0: string, 1: list<int>}> ['cmd'|'data'|'raw', bytes] */
    public array $writes = [];

    /** Every write answers this when set, as a failed spidev ioctl answers -1. */
    public ?int $answer = null;

    public bool $released = false;

    public function __construct(public ?FakeOutputPin $dc = null, int $chip_select = 0)
    {
        parent::__construct($chip_select);
    }

    public function handle(): string
    {
        return 'fake';
    }

    public function read(int $len): array|false
    {
        return false;
    }

    public function transfer(array|string $data): array|false
    {
        return false;
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        return false;
    }

    public function write(array|string $data): int
    {
        $bytes = is_array($data) ? array_values($data) : array_values(unpack('C*', $data));
        $this->writes[] = [is_null($this->dc) ? 'raw' : ($this->dc->state ? 'data' : 'cmd'), $bytes];

        return $this->answer ?? count($bytes);
    }

    public function speed(int $hz): static
    {
        $this->hz = $hz;

        return $this;
    }

    public function clock(): ?int
    {
        return $this->hz;
    }

    /**
     * Commands with their parameter bytes joined: [[register, [params...]], ...].
     * Data writes that follow a command attach to it, the way the panel reads them.
     *
     * @return list<array{0: int, 1: list<int>}>
     */
    public function commands(int $from = 0): array
    {
        $out = [];

        foreach (array_slice($this->writes, $from) as [$kind, $bytes]) {
            if ($kind === 'cmd') {
                foreach ($bytes as $byte) {
                    $out[] = [$byte, []];
                }

                continue;
            }

            $out[array_key_last($out)][1] = [...$out[array_key_last($out)][1], ...$bytes];
        }

        return $out;
    }

    protected function beginSelection(): void {}

    protected function endSelection(): void {}

    protected function release(): void
    {
        $this->released = true;
    }
}
