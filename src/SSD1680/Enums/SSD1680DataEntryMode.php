<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums;

/**
 * Data Entry Mode Setting (0x11) — single parameter byte.
 *
 * Controls how the RAM address counter advances after each write:
 *   bit 2 (AM)  — address counter direction (0 = X, 1 = Y)
 *   bit 1 (ID1) — Y increment (1) / decrement (0)
 *   bit 0 (ID0) — X increment (1) / decrement (0)
 *
 * The reference panel bring-up uses Y increment, X increment, counter
 * updated in the X direction (0x03).
 */
enum SSD1680DataEntryMode: int
{
    case Y_DECREMENT_X_DECREMENT = 0x00;
    case Y_DECREMENT_X_INCREMENT = 0x01;
    case Y_INCREMENT_X_DECREMENT = 0x02;
    case Y_INCREMENT_X_INCREMENT = 0x03;
    case Y_DECREMENT_X_DECREMENT_Y_DIRECTION = 0x04;
    case Y_DECREMENT_X_INCREMENT_Y_DIRECTION = 0x05;
    case Y_INCREMENT_X_DECREMENT_Y_DIRECTION = 0x06;
    case Y_INCREMENT_X_INCREMENT_Y_DIRECTION = 0x07;
}
