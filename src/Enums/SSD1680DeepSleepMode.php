<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Enums;

/** Deep Sleep Mode (0x10). Only a hardware reset wakes the controller from either sleep. */
enum SSD1680DeepSleepMode: int
{
    case NORMAL = 0x00;
    /** RAM kept. */
    case DEEP_SLEEP_1 = 0x01;
    /** RAM lost. */
    case DEEP_SLEEP_2 = 0x03;
}
