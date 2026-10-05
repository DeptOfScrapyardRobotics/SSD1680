<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Enums;

/** The controller's driver outputs: the largest panel it can address. */
enum SSD1680Geometry: int
{
    case MAX_SOURCES = 176;
    case MAX_GATES = 296;
}
