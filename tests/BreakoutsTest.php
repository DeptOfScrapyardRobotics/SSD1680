<?php

use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680BorderWaveform;
use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680DisplayUpdateControl1;
use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680DriverOutputControl;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Exception;

it('packs the border waveform, 0x05 by default and 0x80 for partial refreshes', function (): void {
    expect((new SSD1680BorderWaveform)->toByte())->toBe(0x05)
        ->and(SSD1680BorderWaveform::partial()->toByte())->toBe(0x80)
        ->and(SSD1680BorderWaveform::none()->toByte())->toBe(0x00)
        ->and(SSD1680BorderWaveform::fromByte(0xC7))->toEqual(new SSD1680BorderWaveform(0b11, 0b00, true, 0b11));
});

it('packs driver output control as 9-bit gate lines minus one, then the scan setting', function (): void {
    expect((new SSD1680DriverOutputControl(250))->toBytes())->toBe([0xF9, 0x00, 0x00])
        ->and((new SSD1680DriverOutputControl(296, 0b100))->toBytes())->toBe([0x27, 0x01, 0x04]);
});

it('packs display update control 1 as two bytes', function (): void {
    expect((new SSD1680DisplayUpdateControl1)->toBytes())->toBe([0x00, 0x80])
        ->and(SSD1680DisplayUpdateControl1::fromBytes([0x08, 0x00])->toBytes())->toBe([0x08, 0x00]);
});

it('refuses register values out of range instead of masking them', function (callable $build, string $message): void {
    expect($build)->toThrow(SSD1680Exception::class, $message);
})->with([
    'vbd' => [fn () => new SSD1680BorderWaveform(vbd_option: 4), 'vbd_option takes 0 to 3; got 4'],
    'gs' => [fn () => new SSD1680BorderWaveform(gs_transition_setting: -1), 'gs_transition_setting takes 0 to 3'],
    'gates' => [fn () => new SSD1680DriverOutputControl(297), 'gate_lines takes 1 to 296; got 297'],
    'scan' => [fn () => new SSD1680DriverOutputControl(250, 8), 'gate_setting takes 0 to 7; got 8'],
    'ram option' => [fn () => new SSD1680DisplayUpdateControl1(256), 'ram_content_option takes 0 to 255'],
]);
