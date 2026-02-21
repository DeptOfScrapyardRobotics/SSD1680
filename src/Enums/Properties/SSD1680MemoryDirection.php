<?php

namespace ScrapyardIO\Libraries\Displays\Drivers\SSD1680\Enums\Properties;

enum SSD1680MemoryDirection: int
{
    case NORTH_WEST = 0x00;
    case NORTH_EAST = 0x01;
    case SOUTH_WEST = 0x02;
    case SOUTH_EAST = 0x03;
}
