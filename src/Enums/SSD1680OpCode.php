<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Enums;

/** SSD1680 command codes, from the datasheet's command table. */
enum SSD1680OpCode: int
{
    case DRIVER_OUTPUT_CONTROL = 0x01;
    case GATE_DRIVING_VOLTAGE_CONTROL = 0x03;
    case SOURCE_DRIVING_VOLTAGE_CONTROL = 0x04;
    case BOOSTER_SOFT_START_CONTROL = 0x0C;
    case GATE_SCAN_START_POSITION = 0x0F;
    case DEEP_SLEEP_MODE = 0x10;
    case DATA_ENTRY_MODE_SETTING = 0x11;
    case SOFTWARE_RESET = 0x12;
    case HV_READY_DETECTION = 0x14;
    case VCI_DETECTION = 0x15;
    case TEMP_SENSOR_CONTROL = 0x18;
    case WRITE_TO_TEMP_SENSOR = 0x1A;
    case READ_FROM_TEMP_SENSOR = 0x1B;
    case MASTER_ACTIVATION = 0x20;
    case DISPLAY_UPDATE_CTRL1 = 0x21;
    case DISPLAY_UPDATE_CTRL2 = 0x22;
    case WRITE_BLACK_RAM = 0x24;
    case WRITE_RED_RAM = 0x26;
    case BORDER_WAVEFORM_CONTROL = 0x3C;
    case SET_RAM_X_ADDRESS = 0x44;
    case SET_RAM_Y_ADDRESS = 0x45;
    case SET_RAM_X_ADDRESS_COUNTER = 0x4E;
    case SET_RAM_Y_ADDRESS_COUNTER = 0x4F;
    case NO_OP = 0x7F;
}
