<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680;

use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680BorderWaveform;
use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680DisplayUpdateControl1;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680Geometry;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680TemperatureSensor;

/**
 * The panel's geometry and the values boot writes. width runs along the sources (RAM X, bytes of 8 pixels), height
 * along the gates (RAM Y). The defaults are a 122 × 250 panel with the vendor's settings.
 */
class SSD1680Configuration
{
    protected SSD1680BorderWaveform $border_waveform;

    protected SSD1680DisplayUpdateControl1 $display_update_control;

    /**
     * @param  int  $busy_timeout_ms  longest BUSY may stay high before a command or refresh fails; a tri-colour full refresh takes about 15 s
     */
    public function __construct(
        protected int $width = 122,
        protected int $height = 250,
        protected int $gate_setting = 0x00,
        ?SSD1680BorderWaveform $border_waveform = null,
        ?SSD1680DisplayUpdateControl1 $display_update_control = null,
        protected SSD1680TemperatureSensor $temperature_sensor = SSD1680TemperatureSensor::INTERNAL,
        protected int $max_packet_size = 4096,
        protected int $busy_timeout_ms = 30_000,
    ) {
        if ($width < 1 || $width > SSD1680Geometry::MAX_SOURCES->value || $height < 1 || $height > SSD1680Geometry::MAX_GATES->value) {
            throw SSD1680Exception::invalidGeometry($width, $height);
        }

        if ($busy_timeout_ms < 1) {
            throw SSD1680Exception::invalidRegisterValue('busy_timeout_ms', $busy_timeout_ms, 1, PHP_INT_MAX);
        }

        $this->border_waveform = $border_waveform ?? new SSD1680BorderWaveform;
        $this->display_update_control = $display_update_control ?? new SSD1680DisplayUpdateControl1;
    }

    public function get(string $var): mixed
    {
        if (isset($this->$var)) {
            return $this->$var;
        }

        throw SSD1680Exception::invalidProperty($var, static::class);
    }

    public function set(string $var, mixed $value): void
    {
        if (isset($this->$var)) {
            $this->$var = $value;

            return;
        }

        throw SSD1680Exception::invalidProperty($var, static::class);
    }
}
