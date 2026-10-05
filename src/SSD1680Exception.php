<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680;

use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshMode;

class SSD1680Exception extends CircuitException
{
    public static function invalidRegisterValue(string $field, int $value, int $min, int $max): static
    {
        return new static("SSD1680 {$field} takes {$min} to {$max}; got {$value}.");
    }

    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property '{$name}' on {$class}.");
    }

    public static function invalidGeometry(int $width, int $height): static
    {
        return new static("SSD1680 drives up to 176 × 296; a {$width} × {$height} panel does not fit.");
    }

    public static function notConnected(string $protocol, string $driver, string|int $device): static
    {
        return new static("SSD1680 could not get a {$protocol} connection from driver [{$driver}] on device [{$device}].");
    }

    public static function incompletePin(string $name): static
    {
        return new static("SSD1680 needs its {$name} pin as driver, device and pin.");
    }

    public static function invalidSpiMode(int $mode): static
    {
        return new static("SPI mode {$mode} does not exist; use 0 to 3.");
    }

    public static function spiClockOutOfRange(int $hz): static
    {
        return new static("SSD1680 SPI clock {$hz} Hz is outside 1 Hz – 20 MHz.");
    }

    public static function wrongSpiMode(string|int $device, int $bus_mode, int $mode): static
    {
        return new static("SPI bus [{$device}] runs in mode {$bus_mode}; this panel is configured for mode {$mode}.");
    }

    public static function spiWriteFailed(string $what, int $expected, int $written): static
    {
        return new static("SSD1680 SPI {$what} write failed: {$written} of {$expected} bytes. Check wiring, SPI bus permissions, and that the panel is powered.");
    }

    public static function invalidPacketSize(int $size): static
    {
        return new static("SSD1680 max_packet_size {$size} must be at least 1.");
    }

    public static function busyTimeout(int $timeout_ms): static
    {
        return new static("SSD1680 BUSY stayed high for {$timeout_ms} ms. Check the BUSY pin and the panel's power.");
    }

    public static function windowOutsidePanel(int $x, int $y, int $width, int $height, int $panel_width, int $panel_height): static
    {
        return new static("Window {$width} × {$height} at ({$x}, {$y}) falls outside the {$panel_width} × {$panel_height} panel.");
    }

    public static function unalignedWindow(int $x, int $width, int $panel_width): static
    {
        return new static("SSD1680 RAM columns are bytes of 8 pixels: a window must start on a multiple of 8 and be a multiple of 8 wide, or end at the panel's edge ({$panel_width}); got x {$x}, width {$width}.");
    }

    public static function wrongByteCount(int $expected, int $got): static
    {
        return new static("SSD1680 window needs {$expected} bytes; got {$got}.");
    }

    public static function unsupportedRefreshMode(RefreshMode $mode, string $class): static
    {
        return new static("{$class} has no {$mode->value} refresh.");
    }

    public static function partialBeforeFull(): static
    {
        return new static('A partial refresh drives only the pixels that changed since the panel last showed a frame; run a full refresh after boot first.');
    }
}
