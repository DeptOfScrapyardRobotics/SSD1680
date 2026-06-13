<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Concerns;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects\SSD1680BorderWaveform;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects\SSD1680DisplayUpdateControl1;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects\SSD1680DriverOutputControl;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680DataEntryMode;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680DeepSleepMode;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680OpCode;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680TemperatureSensor;

trait SSD1680API
{
    use SSD1680InternalAPI;

    public function softwareReset(): void
    {
        $this->command(SSD1680OpCode::SOFTWARE_RESET);
        $this->waitUntilIdle();
    }

    public function setDriverOutputControl(SSD1680DriverOutputControl $control): void
    {
        $this->command(SSD1680OpCode::DRIVER_OUTPUT_CONTROL, $control->toBytes());
    }

    public function setDataEntryMode(SSD1680DataEntryMode $mode): void
    {
        $this->command(SSD1680OpCode::DATA_ENTRY_MODE_SETTING, [$mode->value]);
    }

    public function setRAMXAddressRange(int $start, int $end): void
    {
        $this->command(SSD1680OpCode::SET_RAM_X_ADDRESS, [
            $start & 0xFF,
            $end & 0xFF,
        ]);
    }

    public function setRAMYAddressRange(int $start, int $end): void
    {
        $this->command(SSD1680OpCode::SET_RAM_Y_ADDRESS, [
            $start & 0xFF,
            ($start >> 8) & 0xFF,
            $end & 0xFF,
            ($end >> 8) & 0xFF,
        ]);
    }

    public function setBorderWaveform(SSD1680BorderWaveform $border): void
    {
        $this->command(SSD1680OpCode::BORDER_WAVEFORM_CONTROL, [$border->toByte()]);
    }

    public function setDisplayUpdateControl1(SSD1680DisplayUpdateControl1 $control): void
    {
        $this->command(SSD1680OpCode::DISPLAY_UPDATE_CTRL1, $control->toBytes());
    }

    public function setTemperatureSensor(SSD1680TemperatureSensor $sensor): void
    {
        $this->command(SSD1680OpCode::TEMP_SENSOR_CONTROL, [$sensor->value]);
    }

    public function setRAMXAddressCounter(int $position): void
    {
        $this->command(SSD1680OpCode::SET_RAM_X_ADDRESS_COUNTER, [$position & 0xFF]);
    }

    public function setRAMYAddressCounter(int $position): void
    {
        $this->command(SSD1680OpCode::SET_RAM_Y_ADDRESS_COUNTER, [
            $position & 0xFF,
            ($position >> 8) & 0xFF,
        ]);
    }

    public function deepSleep(SSD1680DeepSleepMode $mode): void
    {
        $this->command(SSD1680OpCode::DEEP_SLEEP_MODE, [$mode->value]);
    }

    /**
     * @param  array<int, int>  $bytes
     */
    public function writeBlackRAM(array $bytes): void
    {
        $this->command(SSD1680OpCode::WRITE_BLACK_RAM, $bytes);
    }

    /**
     * @param  array<int, int>  $bytes
     */
    public function writeRedRAM(array $bytes): void
    {
        $this->command(SSD1680OpCode::WRITE_RED_RAM, $bytes);
    }

    public function setDisplayUpdateSequence(int $mode): void
    {
        $this->command(SSD1680OpCode::DISPLAY_UPDATE_CTRL2, [$mode & 0xFF]);
    }

    public function masterActivation(): void
    {
        $this->command(SSD1680OpCode::MASTER_ACTIVATION);
    }

    /**
     * Run the configured update sequence and block until the panel finishes.
     *
     * 0xF7 is the canonical full-refresh sequence (enable clock + analog, load
     * the temperature value and OTP LUT, drive the display, then power the
     * analog/clock back down).
     */
    public function refresh(): void
    {
        $this->setDisplayUpdateSequence(0xF7);
        $this->masterActivation();
        $this->waitUntilIdle();
    }
}
