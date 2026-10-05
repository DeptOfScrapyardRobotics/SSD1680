<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Exception;
use GeneralPurposeIO\IntegratedCircuits\DataRegister;

/**
 * Border Waveform Control (0x3C), one byte:
 *   bits 7:6 VBD option — 00 GS transition, 01 fixed level, 10 VCOM, 11 HiZ
 *   bits 5:4 fixed level for VBD — 00 VSS, 01 VSH1, 10 VSL, 11 VSH2
 *   bit 2 GS transition control — follow the LUT
 *   bits 1:0 GS transition setting
 * The default 0x05 (GS transition, follow LUT1) is the panel vendor's full-refresh border; partial refreshes use
 * 0x80 (VCOM) so the border does not flash.
 */
readonly class SSD1680BorderWaveform extends DataRegister
{
    public function __construct(
        public int $vbd_option = 0b00,
        public int $fixed_level = 0b00,
        public bool $gs_transition_control = true,
        public int $gs_transition_setting = 0b01,
    ) {
        foreach (['vbd_option' => $vbd_option, 'fixed_level' => $fixed_level, 'gs_transition_setting' => $gs_transition_setting] as $field => $value) {
            if ($value < 0 || $value > 0b11) {
                throw SSD1680Exception::invalidRegisterValue($field, $value, 0, 0b11);
            }
        }
    }

    public function toBits(): string
    {
        return sprintf('%02b%02b0%d%02b', $this->vbd_option, $this->fixed_level, (int) $this->gs_transition_control, $this->gs_transition_setting);
    }

    public static function fromByte(int $byte): static
    {
        return new static(($byte >> 6) & 0b11, ($byte >> 4) & 0b11, (bool) (($byte >> 2) & 0b1), $byte & 0b11);
    }

    public static function none(): static
    {
        return new static(0b00, 0b00, false, 0b00);
    }

    /** VBD driven at VCOM: the border holds still through a partial refresh. */
    public static function partial(): static
    {
        return new static(0b10, 0b00, false, 0b00);
    }
}
