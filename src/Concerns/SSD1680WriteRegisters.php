<?php

namespace ScrapyardIO\Libraries\Displays\Drivers\SSD1680\Concerns;

use ScrapyardIO\Libraries\Displays\Drivers\SSD1680\Enums\SSD1680CommandRegister;

trait SSD1680WriteRegisters
{
    protected function writeSoftwareReset(): bool
    {
        return $this->command(SSD1680CommandRegister::SOFTWARE_RESET->value);
    }

    protected function writeDriverControl(int $mux, int $gate_scans): bool
    {
        return $this->command(SSD1680CommandRegister::DRIVER_OUTPUT_CONTROL->value, [$mux, 0x00, $gate_scans]);
    }

    protected function writeDataMode(int $byte): bool
    {
        return $this->command(SSD1680CommandRegister::DATA_ENTRY_MODE_SETTING->value, [$byte]);
    }

    protected function writeRamXPos(array $bytes): bool
    {
        return $this->command(SSD1680CommandRegister::SET_RAM_X_ADDRESS->value, $bytes);
    }

    protected function writeRamYPos(array $bytes): bool
    {
        return $this->command(SSD1680CommandRegister::SET_RAM_Y_ADDRESS->value, $bytes);
    }

    protected function writeWriteBorder(int $byte): bool
    {
        return $this->command(SSD1680CommandRegister::BORDER_WAVEFORM_CONTROL->value, [$byte]);
    }

    protected function writeDisplayControl(array $bytes): bool
    {
        return $this->command(SSD1680CommandRegister::DISPLAY_UPDATE_CTRL1->value, $bytes);
    }

    protected function writeReadBuiltInTempSensor(int $byte): bool
    {
        return $this->command(SSD1680CommandRegister::TEMP_SENSOR_CONTROL->value, $byte);
    }

    protected function writeGateScanStartPosition(int $byte): bool
    {
        return $this->command(SSD1680CommandRegister::GATE_SCAN_START_POSITION->value, $byte);
    }

    protected function writeRamXCounter(int $byte): bool
    {
        return $this->command(SSD1680CommandRegister::SET_RAM_X_ADDRESS_COUNTER->value, [$byte]);
    }

    protected function writeRamYCounter(array $bytes): bool
    {

        return $this->command(SSD1680CommandRegister::SET_RAM_Y_ADDRESS_COUNTER->value, $bytes);
    }

    protected function writeRam1(): bool
    {
        return $this->command(SSD1680CommandRegister::WRITE_BLACK_RAM->value);
    }

    public function writeRam2(): bool
    {
        return $this->command(SSD1680CommandRegister::WRITE_RED_RAM->value);
    }

    protected function writeDispControl2(int $byte): bool
    {
        return $this->command(SSD1680CommandRegister::DISPLAY_UPDATE_CTRL2->value, [$byte]);
    }

    protected function writeMasterActivation(): bool
    {
        return $this->command(SSD1680CommandRegister::MASTER_ACTIVATION->value);
    }

    protected function writeLut(array $bytes): bool
    {
        return $this->command(SSD1680CommandRegister::WRITE_TO_LUT_REGISTER->value, $bytes);
    }
}
