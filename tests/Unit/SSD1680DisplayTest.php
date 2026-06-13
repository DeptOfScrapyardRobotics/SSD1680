<?php

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Adapters\SSD1680DataCarrier;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects\SSD1680BorderWaveform;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects\SSD1680DisplayUpdateControl1;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680DataEntryMode;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680OpCode;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680TemperatureSensor;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\SSD1680;
use RealityInterface\Displays\Applied\ePaper\BWePaperDisplay;
use RealityInterface\Displays\Applied\ePaper\Enums\EInkColor;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\RenderType;

/*
 | The panel is driven through a recording carrier (no SPI/GPIO), so the boot
 | sequence and data path run unchanged on a Mac. We snapshot only the opcodes
 | the data path emits by clearing the recorder after construction.
 */

class RecordingSSD1680Carrier extends SSD1680DataCarrier
{
    /**
     * @var array<int, array{0: SSD1680OpCode, 1: array<int, int>}>
     */
    public array $commands = [];

    public function __construct() {}

    public function data(array $data): void {}

    public function command(SSD1680OpCode $register_hex, array $command_data = []): void
    {
        $this->commands[] = [$register_hex, $command_data];
    }

    public function reset(): void {}

    public function waitUntilIdle(int $timeout_us = 0): void {}
}

function makeSSD1680Panel(RecordingSSD1680Carrier $carrier, int $width = 8, int $height = 1): SSD1680
{
    return new SSD1680(
        $carrier,
        $width,
        $height,
        SSD1680DataEntryMode::Y_INCREMENT_X_INCREMENT,
        new SSD1680BorderWaveform,
        new SSD1680DisplayUpdateControl1,
        SSD1680TemperatureSensor::INTERNAL,
        0x00,
    );
}

/**
 * @return array<int, SSD1680OpCode>
 */
function opcodesOf(RecordingSSD1680Carrier $carrier): array
{
    return array_map(fn (array $entry): SSD1680OpCode => $entry[0], $carrier->commands);
}

/**
 * @return array<int, int>
 */
function payloadFor(RecordingSSD1680Carrier $carrier, SSD1680OpCode $opcode): array
{
    foreach ($carrier->commands as [$op, $data]) {
        if ($op === $opcode) {
            return $data;
        }
    }

    return [];
}

it('exposes a black/white channel-sorted format spec', function () {
    $spec = makeSSD1680Panel(new RecordingSSD1680Carrier)->getFormatSpec();

    expect($spec->pixel_format)->toBe(PixelFormat::MONO_HORIZONTAL)
        ->and($spec->bit_depth)->toBe(BitDepth::B1)
        ->and($spec->palette?->count())->toBe(1)
        ->and($spec->palette?->channels[0]->color)->toBe(EInkColor::BLACK->value)
        ->and($spec->palette?->channels[0]->inverted)->toBeTrue();
});

it('writes the black RAM then runs a full refresh on display', function () {
    $carrier = new RecordingSSD1680Carrier;
    $panel = makeSSD1680Panel($carrier);
    $carrier->commands = [];

    $panel->display(new DumpedBuffer(
        RenderType::FULL,
        $panel->getFormatSpec(),
        [EInkColor::BLACK->value => [0x7F]],
        width: 8,
        height: 1,
    ));

    expect(opcodesOf($carrier))->toBe([
        SSD1680OpCode::SET_RAM_X_ADDRESS_COUNTER,
        SSD1680OpCode::SET_RAM_Y_ADDRESS_COUNTER,
        SSD1680OpCode::WRITE_BLACK_RAM,
        SSD1680OpCode::DISPLAY_UPDATE_CTRL2,
        SSD1680OpCode::MASTER_ACTIVATION,
    ])
        ->and(payloadFor($carrier, SSD1680OpCode::WRITE_BLACK_RAM))->toBe([0x7F])
        ->and(payloadFor($carrier, SSD1680OpCode::DISPLAY_UPDATE_CTRL2))->toBe([0xF7]);
});

it('skips the RAM write but still refreshes when the dump has no black channel', function () {
    $carrier = new RecordingSSD1680Carrier;
    $panel = makeSSD1680Panel($carrier);
    $carrier->commands = [];

    $panel->display(new DumpedBuffer(
        RenderType::FULL,
        $panel->getFormatSpec(),
        [],
        width: 8,
        height: 1,
    ));

    expect(opcodesOf($carrier))->toBe([
        SSD1680OpCode::DISPLAY_UPDATE_CTRL2,
        SSD1680OpCode::MASTER_ACTIVATION,
    ]);
});

it('drives the panel through the BW ePaper wrapper', function () {
    $carrier = new RecordingSSD1680Carrier;
    $panel = makeSSD1680Panel($carrier);
    $screen = BWePaperDisplay::as($panel);
    $carrier->commands = [];

    $screen->transmit(new DumpedBuffer(
        RenderType::FULL,
        $panel->getFormatSpec(),
        [EInkColor::BLACK->value => [0x7F]],
        width: 8,
        height: 1,
    ));

    expect(opcodesOf($carrier))->toContain(SSD1680OpCode::WRITE_BLACK_RAM)
        ->and(opcodesOf($carrier))->toContain(SSD1680OpCode::MASTER_ACTIVATION);
});
