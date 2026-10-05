<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Enums;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680BW;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680BWR;

/** The catalog slugs, one per panel kind, and the class each conjures. */
enum SSD1680CatalogIc: string
{
    case BW = 'ssd1680-bw';
    case BWR = 'ssd1680-bwr';

    /** @return class-string<\DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680> */
    public function panelClass(): string
    {
        return match ($this) {
            self::BW => SSD1680BW::class,
            self::BWR => SSD1680BWR::class,
        };
    }
}
