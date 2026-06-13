<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums;

/**
 * Temperature Sensor Control (0x18) — selects the source used to look up the
 * waveform LUT temperature compensation.
 *
 *   0x80 — built-in temperature sensor (default)
 *   0x48 — external I2C temperature sensor
 */
enum SSD1680TemperatureSensor: int
{
    case INTERNAL = 0x80;
    case EXTERNAL = 0x48;
}
