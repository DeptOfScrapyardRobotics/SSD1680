<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Exceptions\SSD1680Exception;

/**
 * Driver Output Control (0x01) — 3 parameter bytes.
 *
 * Programs how many gate lines (MUX) the panel drives and the gate scanning
 * arrangement:
 *   bytes 0..1 — (gate_lines - 1) as a little-endian 9-bit value
 *   byte 2     — bit 2 TB (gate scan top->bottom), bit 1 SM (interlaced),
 *                bit 0 GD (gate output selection)
 *
 * The gate line count is normally the panel height. The default gate setting
 * 0x00 matches the reference SSD1680 bring-up.
 */
readonly class SSD1680DriverOutputControl
{
    public function __construct(
        public int $gate_lines,
        public int $gate_setting = 0x00,
    ) {
        if (($this->gate_lines < 1) || ($this->gate_lines > 296)) {
            throw SSD1680Exception::invalidRegisterValue('gate_lines', $this->gate_lines, 1, 296);
        }

        if (($this->gate_setting < 0) || ($this->gate_setting > 0xFF)) {
            throw SSD1680Exception::invalidRegisterValue('gate_setting', $this->gate_setting, 0, 0xFF);
        }
    }

    /**
     * @return list<int>
     */
    public function toBytes(): array
    {
        $mux = $this->gate_lines - 1;

        return [
            $mux & 0xFF,
            ($mux >> 8) & 0x01,
            $this->gate_setting & 0xFF,
        ];
    }

    public static function forHeight(int $height, int $gate_setting = 0x00): static
    {
        return new static($height, $gate_setting);
    }
}
