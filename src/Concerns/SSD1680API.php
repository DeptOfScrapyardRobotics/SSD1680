<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Concerns;

use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680BorderWaveform;
use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680DisplayUpdateControl1;
use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680DriverOutputControl;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680DeepSleepMode;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680OpCode;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680TemperatureSensor;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680UpdateSequence;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Configuration;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Exception;
use DeptOfScrapyardRobotics\Displays\SSD1680\Transports\SSD1680DataTransport;

/** Register setters: each writes the chip, then the configuration. */
trait SSD1680API
{
    abstract public function config(): SSD1680Configuration;

    abstract public function transport(): SSD1680DataTransport;

    protected function sendCommand(SSD1680OpCode $register, array $command_data = []): int
    {
        return $this->transport()->command($register->value, $command_data);
    }

    /** @throws SSD1680Exception when BUSY stays high past busy_timeout_ms */
    public function waitUntilIdle(): void
    {
        $this->transport()->waitUntilIdle($this->config()->get('busy_timeout_ms'));
    }

    /** 0x12: back to power-on register defaults; RAM is kept. */
    public function softwareReset(): void
    {
        $this->sendCommand(SSD1680OpCode::SOFTWARE_RESET);
        $this->waitUntilIdle();
    }

    public function setDriverOutputControl(SSD1680DriverOutputControl $control): void
    {
        $this->sendCommand(SSD1680OpCode::DRIVER_OUTPUT_CONTROL, $control->toBytes());
        $this->config()->set('gate_setting', $control->gate_setting);
    }

    /** RAM window in pixels: X runs in bytes of 8, so $x is a multiple of 8. */
    public function setRAMWindow(int $x, int $y, int $width, int $height): void
    {
        $y_end = $y + $height - 1;

        $this->sendCommand(SSD1680OpCode::SET_RAM_X_ADDRESS, [$x >> 3, ($x + $width - 1) >> 3]);
        $this->sendCommand(SSD1680OpCode::SET_RAM_Y_ADDRESS, [$y & 0xFF, ($y >> 8) & 0x01, $y_end & 0xFF, ($y_end >> 8) & 0x01]);
    }

    /** RAM address counter in pixels: X in bytes of 8. */
    public function setRAMCursor(int $x, int $y): void
    {
        $this->sendCommand(SSD1680OpCode::SET_RAM_X_ADDRESS_COUNTER, [$x >> 3]);
        $this->sendCommand(SSD1680OpCode::SET_RAM_Y_ADDRESS_COUNTER, [$y & 0xFF, ($y >> 8) & 0x01]);
    }

    public function setBorderWaveform(SSD1680BorderWaveform $border): void
    {
        $this->sendCommand(SSD1680OpCode::BORDER_WAVEFORM_CONTROL, [$border->toByte()]);
        $this->config()->set('border_waveform', $border);
    }

    public function setDisplayUpdateControl1(SSD1680DisplayUpdateControl1 $control): void
    {
        $this->sendCommand(SSD1680OpCode::DISPLAY_UPDATE_CTRL1, $control->toBytes());
        $this->config()->set('display_update_control', $control);
    }

    public function setTemperatureSensor(SSD1680TemperatureSensor $sensor): void
    {
        $this->sendCommand(SSD1680OpCode::TEMP_SENSOR_CONTROL, [$sensor->value]);
        $this->config()->set('temperature_sensor', $sensor);
    }

    /** 0x22 with the sequence, 0x20 to run it, then wait for BUSY to fall. */
    public function runUpdate(SSD1680UpdateSequence $sequence): void
    {
        $this->sendCommand(SSD1680OpCode::DISPLAY_UPDATE_CTRL2, [$sequence->value]);
        $this->sendCommand(SSD1680OpCode::MASTER_ACTIVATION);
        $this->waitUntilIdle();
    }

    /**
     * Deep sleep. The panel keeps showing its image; only a hardware reset wakes the controller, so the next boot()
     * runs the whole sequence again.
     */
    public function sleep(SSD1680DeepSleepMode $mode = SSD1680DeepSleepMode::DEEP_SLEEP_1): void
    {
        $this->sendCommand(SSD1680OpCode::DEEP_SLEEP_MODE, [$mode->value]);

        if ($mode !== SSD1680DeepSleepMode::NORMAL) {
            $this->booted = false;
        }
    }
}
