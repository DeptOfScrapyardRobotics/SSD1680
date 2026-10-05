<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Enums;

/** Temperature Sensor Control (0x18): where the waveform's temperature comes from. */
enum SSD1680TemperatureSensor: int
{
    case INTERNAL = 0x80;
    case EXTERNAL = 0x48;
}
