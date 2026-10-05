<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Concerns;

use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680DriverOutputControl;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680DataEntryMode;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680OpCode;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Exception;

trait SSD1680Bootstrap
{
    use SSD1680API;

    /**
     * Any configuration key reads as a property.
     *
     * @throws SSD1680Exception
     */
    public function __get(string $name): mixed
    {
        return $this->config()->get($name);
    }

    /**
     * @throws SSD1680Exception
     */
    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            'border_waveform' => $this->setBorderWaveform($value),
            'display_update_control' => $this->setDisplayUpdateControl1($value),
            'temperature_sensor' => $this->setTemperatureSensor($value),
            default => throw SSD1680Exception::invalidProperty($name, static::class),
        };
    }

    /**
     * Power, hardware reset, software reset, then the panel geometry and the configured registers. RAM is written
     * row by row, left to right and top to bottom, so the data entry mode is fixed at X then Y increment.
     */
    protected function _boot(): void
    {
        $config = $this->config();

        $this->transport()->maxPacketSize($config->get('max_packet_size'));
        $this->transport()->power(true);
        $this->initialize();

        $this->afterBoot();
    }

    /**
     * Hardware reset, software reset, then the geometry and the configured registers. RAM keeps its contents.
     */
    protected function initialize(): void
    {
        $config = $this->config();

        $this->transport()->reset();
        $this->waitUntilIdle();
        $this->softwareReset();

        $this->setDriverOutputControl(new SSD1680DriverOutputControl($this->height(), $config->get('gate_setting')));
        $this->sendCommand(SSD1680OpCode::DATA_ENTRY_MODE_SETTING, [SSD1680DataEntryMode::Y_INCREMENT_X_INCREMENT->value]);
        $this->setRAMWindow(0, 0, $this->width(), $this->height());
        $this->setBorderWaveform($config->get('border_waveform'));
        $this->setDisplayUpdateControl1($config->get('display_update_control'));
        $this->setTemperatureSensor($config->get('temperature_sensor'));
        $this->setRAMCursor(0, 0);
        $this->waitUntilIdle();
    }

    /** After a boot: what a variant forgets about the panel. */
    protected function afterBoot(): void {}
}
