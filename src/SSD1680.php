<?php

namespace ScrapyardIO\Libraries\Displays\Drivers\SSD1680;

use ScrapyardIO\Libraries\Displays\Contracts\AddressWIndowSetting;
use ScrapyardIO\Libraries\Displays\Displays\MonochromeEInkDisplay;
use ScrapyardIO\Libraries\Displays\Drivers\SSD1680\Concerns\SSD1680API;
use ScrapyardIO\Libraries\Displays\Drivers\SSD1680\Enums\Properties\SSD1680MemoryMode;
use ScrapyardIO\Libraries\Displays\Drivers\SSD1680\Enums\Properties\SSD1680MemoryDirection;

class SSD1680 extends MonochromeEInkDisplay implements AddressWindowSetting
{
    use SSD1680API;

    protected int $pages = 0;
    protected int $width = 122;
    protected int $height = 250;

    protected SSD1680MemoryMode $scan_direction = SSD1680MemoryMode::HORIZONTAL;
    protected SSD1680MemoryDirection $memory_direction = SSD1680MemoryDirection::SOUTH_EAST;

    protected array $gate_scan_control = [
        '-6' => false,
        '-5' => false,
        '-4' => false,
        '-3' => false,
        '-2' => false,
        '-1' => false,
        'SEQUENTIAL_SCANNING' => false,
        'SCAN_FROM_BOTTOM_ROW' => false,
    ];

    public function normal(): void
    {
        $this->width = 122;
        $this->height = 250;
    }

    public function setScanFromBottomRow(bool $enabled = true): static
    {
        $this->gate_scan_control['SCAN_FROM_BOTTOM_ROW'] = $enabled;
        return $this;
    }

    public function start(): static
    {
        $this->pages = (int) (($this->width + 7) / 8);

        $this->resetDisplay();

        $this->turnDisplayOff();
        $this->setDriverControl();
        $this->setGateScanStartPosition(0);
        $this->setDataMode();
        $this->setViewport();
        $this->setWriteBorder();
        $this->setDisplayControl();
        $this->setTempControl();
        $this->setAddressWindow();

        $this->busyWait(5);

        return $this;
    }

    protected function resetDisplay(): void
    {
        $this->rstHigh();
        $this->wait(20);

        $this->rstLow();
        $this->wait(2);

        $this->rstHigh();
        $this->wait(20);
    }
}
