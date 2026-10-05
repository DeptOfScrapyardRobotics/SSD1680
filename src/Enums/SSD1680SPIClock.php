<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Enums;

enum SSD1680SPIClock: int
{
    /** 4-wire SPI write clock cycle, 50 ns minimum. */
    case MAX_HZ = 20_000_000;
}
