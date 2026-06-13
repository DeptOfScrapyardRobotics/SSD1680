<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects;

use BareMetal\DataObjects\DataRegister;

/**
 * Border Waveform Control (0x3C) — single parameter byte.
 *
 *   bits 7:6 VBD     — VBD option (00 GS transition, 01 fixed level,
 *                      10 VCOM, 11 HiZ)
 *   bits 5:4 fix     — fixed level setting for VBD
 *   bit  2   gs_ctrl — GS transition control (follow LUT)
 *   bits 1:0 gs      — GS transition setting
 *
 * The default 0x05 (GS transition, follow LUT1) matches the reference panel
 * bring-up.
 */
readonly class SSD1680BorderWaveform extends DataRegister
{
    public function __construct(
        public int $vbd_option = 0b00,
        public int $fixed_level = 0b00,
        public bool $gs_transition_control = true,
        public int $gs_transition_setting = 0b01,
    ) {}

    public function toBits(): string
    {
        $vbd = str_pad(decbin($this->vbd_option & 0b11), 2, '0', STR_PAD_LEFT);
        $fix = str_pad(decbin($this->fixed_level & 0b11), 2, '0', STR_PAD_LEFT);
        $reserved = '0';
        $gs_ctrl = $this->gs_transition_control ? '1' : '0';
        $gs = str_pad(decbin($this->gs_transition_setting & 0b11), 2, '0', STR_PAD_LEFT);

        return "{$vbd}{$fix}{$reserved}{$gs_ctrl}{$gs}";
    }

    public static function fromByte(int $byte): static
    {
        return new static(
            ($byte >> 6) & 0b11,
            ($byte >> 4) & 0b11,
            (bool) (($byte >> 2) & 0b1),
            $byte & 0b11,
        );
    }

    public static function none(): static
    {
        return new static(0b00, 0b00, false, 0b00);
    }
}
