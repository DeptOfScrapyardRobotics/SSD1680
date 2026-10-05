<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Exception;

/**
 * Display Update Control 1 (0x21), two bytes:
 *   byte 0 — RAM content option: bits 7:4 red RAM, bits 3:0 black RAM (0 normal, 4 bypass as 0, 8 inverse)
 *   byte 1 — source output mode: 0x80 = sources S8 to S167
 * The defaults (0x00, 0x80) are the panel vendor's.
 */
readonly class SSD1680DisplayUpdateControl1
{
    public function __construct(
        public int $ram_content_option = 0x00,
        public int $source_output_mode = 0x80,
    ) {
        foreach (['ram_content_option' => $ram_content_option, 'source_output_mode' => $source_output_mode] as $field => $value) {
            if ($value < 0 || $value > 0xFF) {
                throw SSD1680Exception::invalidRegisterValue($field, $value, 0, 0xFF);
            }
        }
    }

    /** @return list<int> */
    public function toBytes(): array
    {
        return [$this->ram_content_option, $this->source_output_mode];
    }

    /** @param  list<int>  $bytes */
    public static function fromBytes(array $bytes): static
    {
        return new static(...array_values($bytes));
    }
}
