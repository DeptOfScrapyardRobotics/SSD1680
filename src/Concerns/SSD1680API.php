<?php

namespace ScrapyardIO\Libraries\Displays\Drivers\SSD1680\Concerns;

use ScrapyardIO\Libraries\Displays\Drivers\SSD1680\Concerns\SSD1680WriteRegisters;

trait SSD1680API
{
    use SSD1680WriteRegisters;

    protected function turnDisplayOff(): void
    {
        $this->writeSoftwareReset();
        $this->wait(200);
        $this->busyWait(5000);
    }

    protected function setDriverControl(): void
    {
        $mux = $this->height - 1;
        $gate_scans = bitsbyte(array_reverse(array_values($this->gate_scan_control)));
        //dd($gate_scans);
        $this->writeDriverControl($mux, $gate_scans);
    }

    protected function setGateScanStartPosition(int $position = 0): void
    {
        $this->writeGateScanStartPosition($position);
    }

    protected function setDataMode(): void
    {
        $this->writeDataMode($this->scan_direction->value | $this->memory_direction->value);
    }


    protected function setViewport(): void
    {
        $yEnd = $this->height - 1;
        $this->writeRamXPos([0x00, $this->pages - 1]);
        $this->writeRamYPos([0x00, 0x00, $yEnd & 0xFF, ($yEnd >> 8) & 0xFF]);
    }

    protected function setWriteBorder(): void
    {
        $this->writeWriteBorder(0x05);
    }

    protected function setDisplayControl(): void
    {
        $this->writeDisplayControl([0x80, 0x80]);
    }

    protected function setTempControl(): void
    {
        $this->writeReadBuiltInTempSensor(0x80);
    }

    public function setLut(): void
    {
        $this->writeLut($this->getLutFullUpdate());
    }

    protected function getLutFullUpdate(): array
    {
        return [
            0x02, 0x02, 0x01, 0x11, 0x12, 0x12, 0x22, 0x22,
            0x66, 0x69, 0x69, 0x59, 0x58, 0x99, 0x99, 0x88,
            0x00, 0x00, 0x00, 0x00, 0xF8, 0xB4, 0x13, 0x51,
            0x35, 0x51, 0x51, 0x19, 0x01, 0x00,
        ];
    }

    public function setAddressWindow(int $x_min = 0, int $x_max = 0, int $y_min = 0, int $y_max = 0): void
    {
        $this->writeRamXCounter($x_min);
        $this->writeRamYCounter([$y_min, $y_max]);
    }

    public function setRamPointers(int $x, int $y): void
    {
        $this->writeRamXCounter($x >> 3);
        $this->writeRamYCounter([$y & 0xFF, ($y >> 8) & 0xFF]);
    }

    public function setDisplayUpdateAndActivate(int $ctrl2 = 0xF7): void
    {
        $this->writeDispControl2($ctrl2);
        $this->writeMasterActivation();
    }

    public function startWrite(): void
    {
        $this->writeRam1();
    }

    public function endWrite(): void
    {
        $this->setDisplayUpdateAndActivate();
    }
}
