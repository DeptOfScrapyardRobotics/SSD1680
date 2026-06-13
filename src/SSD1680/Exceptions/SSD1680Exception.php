<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Exceptions;

use RuntimeException;

class SSD1680Exception extends RuntimeException
{
    public static function invalidProperty(string $name): static
    {
        return new static("Invalid property $name");
    }

    public static function invalidRegisterValue(string $field, int $value, int $min, int $max): static
    {
        return new static("Valid $field values are between $min and $max, you input $value.");
    }
}
