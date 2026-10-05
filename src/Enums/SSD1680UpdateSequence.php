<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Enums;

/**
 * Display Update Control 2 (0x22): the steps Master Activation (0x20) runs. Both enable the clock and analog, load
 * the temperature and the OTP waveform, drive the panel, then power down again.
 */
enum SSD1680UpdateSequence: int
{
    /** Display mode 1: the full refresh waveform. */
    case FULL = 0xF7;
    /** Display mode 2: the partial refresh waveform, driving only the pixels that differ from the red RAM. */
    case PARTIAL = 0xFF;
}
