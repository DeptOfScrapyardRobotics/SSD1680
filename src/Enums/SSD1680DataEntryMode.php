<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Enums;

/**
 * Data Entry Mode Setting (0x11): bit 2 = counter advances along Y (1) or X (0), bit 1 = Y increments, bit 0 = X
 * increments. transmit() writes rows left to right, top to bottom, so boot sets Y_INCREMENT_X_INCREMENT.
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
