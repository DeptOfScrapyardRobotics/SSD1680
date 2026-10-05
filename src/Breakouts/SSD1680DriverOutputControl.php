<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts;

use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680Geometry;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Exception;

/**
 * Driver Output Control (0x01), three bytes: the gate lines driven (MUX + 1) as a 9-bit little-endian value, then
 * the scan setting — bit 2 TB (scan bottom to top), bit 1 SM (interlaced), bit 0 GD (first gate output). The gate
 * count is the panel height.
 */
readonly class SSD1680DriverOutputControl
{
    public function __construct(
        public int $gate_lines,
        public int $gate_setting = 0x00,
    ) {
        if ($gate_lines < 1 || $gate_lines > SSD1680Geometry::MAX_GATES->value) {
            throw SSD1680Exception::invalidRegisterValue('gate_lines', $gate_lines, 1, SSD1680Geometry::MAX_GATES->value);
        }

        if ($gate_setting < 0 || $gate_setting > 0b111) {
            throw SSD1680Exception::invalidRegisterValue('gate_setting', $gate_setting, 0, 0b111);
        }
    }

    /** @return list<int> */
    public function toBytes(): array
    {
        $mux = $this->gate_lines - 1;

        return [$mux & 0xFF, ($mux >> 8) & 0x01, $this->gate_setting];
    }
}
