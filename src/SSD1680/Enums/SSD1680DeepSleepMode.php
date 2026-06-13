<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums;

/**
 * Deep Sleep Mode (0x10) — single parameter byte.
 *
 *   0x00 — normal mode (leave deep sleep)
 *   0x01 — deep sleep mode 1 (RAM retained)
 *   0x03 — deep sleep mode 2 (RAM not retained)
 *
 * A hardware reset is required to wake the controller from either deep
 * sleep mode.
 */
enum SSD1680DeepSleepMode: int
{
    case NORMAL = 0x00;
    case DEEP_SLEEP_1 = 0x01;
    case DEEP_SLEEP_2 = 0x03;
}
