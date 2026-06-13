<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Exceptions\SSD1680Exception;

/**
 * Display Update Control 1 (0x21) — 2 parameter bytes.
 *
 *   byte 0 — RAM content option for the red/black bypass (inverse, bypass)
 *   byte 1 — source output mode (0x80 = available source from S8 to S167)
 *
 * The defaults (0x00, 0x80) match the reference SSD1680 bring-up for the
 * 122x250 panel.
 */
readonly class SSD1680DisplayUpdateControl1
{
    public function __construct(
        public int $ram_content_option = 0x00,
        public int $source_output_mode = 0x80,
    ) {
        $this->assertByte($this->ram_content_option, 'ram_content_option');
        $this->assertByte($this->source_output_mode, 'source_output_mode');
    }

    private function assertByte(int $value, string $field): void
    {
        if (($value < 0) || ($value > 0xFF)) {
            throw SSD1680Exception::invalidRegisterValue($field, $value, 0, 0xFF);
        }
    }

    /**
     * @return list<int>
     */
    public function toBytes(): array
    {
        return [
            $this->ram_content_option & 0xFF,
            $this->source_output_mode & 0xFF,
        ];
    }

    public static function fromBytes(
        int $ram_content_option = 0x00,
        int $source_output_mode = 0x80,
    ): static {
        return new static($ram_content_option, $source_output_mode);
    }
}
