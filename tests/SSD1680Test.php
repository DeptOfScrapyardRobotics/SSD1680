<?php

use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680BorderWaveform;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680DeepSleepMode;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680TemperatureSensor;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680BW;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680BWR;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Configuration;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Exception;
use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;
use GeneralPurposeIO\Contracts\IntegratedCircuits\DisplayPanel;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshesOnCommand;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshMode;
use GeneralPurposeIO\Contracts\IntegratedCircuits\Switchable;
use GeneralPurposeIO\Contracts\IntegratedCircuits\WindowAddressable;
use Surface\Contracts\Framebuffers\BitDepth;
use Surface\Contracts\Framebuffers\BitOrder;
use Surface\Contracts\Framebuffers\EInkColor;
use Surface\Contracts\Framebuffers\PixelFormat;

// --- shape and boot ---------------------------------------------------------------

it('is a window-addressable panel that refreshes on command and does not switch', function (string $class): void {
    expect(is_subclass_of($class, DisplayPanel::class))->toBeTrue()
        ->and(is_subclass_of($class, WindowAddressable::class))->toBeTrue()
        ->and(is_subclass_of($class, RefreshesOnCommand::class))->toBeTrue()
        ->and(is_subclass_of($class, Switchable::class))->toBeFalse();
})->with([SSD1680BW::class, SSD1680BWR::class]);

it('boots with the vendor init: power, reset, software reset, geometry and registers', function (string $class): void {
    [$panel, $spi, $pins] = epd($class);

    expect($panel->hasBooted())->toBeTrue()
        ->and($pins['pwr']->levels)->toBe([true])
        ->and($pins['rst']->levels)->toBe([true, false, true])
        ->and($spi->commands())->toBe(epdBootCommands())
        ->and([$panel->width(), $panel->height()])->toBe([122, 250]);
})->with([SSD1680BW::class, SSD1680BWR::class]);

it('sizes the gates and the RAM window from the configured panel', function (): void {
    [, $spi] = epd(SSD1680BW::class, new SSD1680Configuration(width: 128, height: 296));

    expect($spi->commands()[1])->toBe([0x01, [0x27, 0x01, 0x00]])
        ->and($spi->commands()[3])->toBe([0x44, [0x00, 0x0F]])
        ->and($spi->commands()[4])->toBe([0x45, [0x00, 0x00, 0x27, 0x01]]);
});

it('waits out BUSY after the reset, the software reset and the setup', function (): void {
    [$panel, , $pins] = epd(SSD1680BW::class, boot: false);
    $pins['busy']->script = [true, true, false, true, false, false];

    $panel->boot();

    expect($pins['busy']->reads)->toBe(6);
});

it('throws when BUSY never falls', function (): void {
    [$panel, , $pins] = epd(SSD1680BW::class, new SSD1680Configuration(busy_timeout_ms: 15), boot: false);
    $pins['busy']->level = true;

    expect(fn () => $panel->boot())->toThrow(SSD1680Exception::class, 'BUSY stayed high for 15 ms')
        ->and($panel->hasBooted())->toBeFalse();
});

// --- format -----------------------------------------------------------------------

it('describes the black/white panel as one MSB-first mono plane', function (): void {
    [$panel] = epd(SSD1680BW::class, boot: false);
    $spec = $panel->formatSpec();

    expect($spec->pixel_format)->toBe(PixelFormat::MONO_HORIZONTAL)
        ->and($spec->bit_depth)->toBe(BitDepth::B1)
        ->and($spec->bit_order)->toBe(BitOrder::MSB_FIRST)
        ->and($spec->palette)->toBeNull();
});

it('describes the black/white/red panel as an inverted black plane then a red plane', function (): void {
    [$panel] = epd(SSD1680BWR::class, boot: false);
    $spec = $panel->formatSpec();

    expect($spec->pixel_format)->toBe(PixelFormat::PLANAR)
        ->and($spec->palette->colors())->toBe([EInkColor::BLACK->value, EInkColor::RED->value])
        ->and($spec->palette->channels[0]->inverted)->toBeTrue()
        ->and($spec->palette->channels[1]->inverted)->toBeFalse();
});

// --- transmit ---------------------------------------------------------------------

it('writes a whole frame into the black RAM: 16 bytes a row, 250 rows', function (): void {
    [$panel, $spi] = epd(SSD1680BW::class);
    $before = count($spi->writes);

    $panel->transmit(0, 0, array_fill(0, 16 * 250, 0xFF));
    $commands = $spi->commands($before);

    expect(array_slice($commands, 0, 4))->toBe([[0x44, [0x00, 0x0F]], [0x45, [0x00, 0x00, 0xF9, 0x00]], [0x4E, [0x00]], [0x4F, [0x00, 0x00]]])
        ->and($commands[4][0])->toBe(0x24)
        ->and(count($commands[4][1]))->toBe(4000)
        ->and($commands)->toHaveCount(5);
});

it('writes a window at its byte column and gate row', function (): void {
    [$panel, $spi] = epd(SSD1680BW::class);
    $before = count($spi->writes);

    $panel->transmit(16, 240, array_fill(0, 2 * 10, 0x00), 16, 10);

    expect($spi->commands($before))->toBe([
        [0x44, [0x02, 0x03]],
        [0x45, [0xF0, 0x00, 0xF9, 0x00]],
        [0x4E, [0x02]],
        [0x4F, [0xF0, 0x00]],
        [0x24, array_fill(0, 20, 0x00)],
    ]);
});

it('writes a window that ends at the right edge with a ragged width', function (): void {
    [$panel, $spi] = epd(SSD1680BW::class);
    $before = count($spi->writes);

    $panel->transmit(112, 8, array_fill(0, 2 * 4, 0x0F), 10, 4);

    expect($spi->commands($before))->toBe([
        [0x44, [0x0E, 0x0F]],
        [0x45, [0x08, 0x00, 0x0B, 0x00]],
        [0x4E, [0x0E]],
        [0x4F, [0x08, 0x00]],
        [0x24, array_fill(0, 8, 0x0F)],
    ]);
});

it('splits the black/white/red bytes into the black RAM then the red RAM', function (): void {
    [$panel, $spi] = epd(SSD1680BWR::class);
    $before = count($spi->writes);

    $panel->transmit(0, 0, [...array_fill(0, 2, 0xAA), ...array_fill(0, 2, 0x55)], 8, 2);

    expect($spi->commands($before))->toBe([
        [0x44, [0x00, 0x00]],
        [0x45, [0x00, 0x00, 0x01, 0x00]],
        [0x4E, [0x00]],
        [0x4F, [0x00, 0x00]],
        [0x24, [0xAA, 0xAA]],
        [0x4E, [0x00]],
        [0x4F, [0x00, 0x00]],
        [0x26, [0x55, 0x55]],
    ]);
});

it('refuses a window off the byte grid, outside the panel, or with the wrong byte count', function (array $args, string $message): void {
    [$panel, $spi] = epd(SSD1680BW::class);
    $before = count($spi->writes);

    expect(fn () => $panel->transmit(...$args))->toThrow(SSD1680Exception::class, $message)
        ->and(count($spi->writes))->toBe($before);
})->with([
    'x off the grid' => [[4, 0, array_fill(0, 2, 0), 8, 2], 'must start on a multiple of 8'],
    'ragged width inside' => [[0, 0, array_fill(0, 2, 0), 10, 1], 'multiple of 8 wide'],
    'past the bottom' => [[0, 249, array_fill(0, 4, 0), 8, 2], 'falls outside'],
    'past the right' => [[120, 0, array_fill(0, 2, 0), 8, 1], 'falls outside'],
    'byte count' => [[0, 0, array_fill(0, 3, 0), 8, 2], 'needs 2 bytes; got 3'],
]);

// --- refresh ----------------------------------------------------------------------

it('runs a full refresh, then copies the windows sent since into the red RAM', function (): void {
    [$panel, $spi] = epd(SSD1680BW::class);
    $panel->transmit(8, 4, [0x12, 0x34], 8, 2);
    $before = count($spi->writes);

    $panel->refresh();

    expect($spi->commands($before))->toBe([
        [0x3C, [0x05]],
        [0x22, [0xF7]],
        [0x20, []],
        [0x44, [0x01, 0x01]],
        [0x45, [0x04, 0x00, 0x05, 0x00]],
        [0x4E, [0x01]],
        [0x4F, [0x04, 0x00]],
        [0x26, [0x12, 0x34]],
    ]);
});

it('refuses a partial refresh before the first full one', function (): void {
    [$panel, $spi] = epd(SSD1680BW::class);
    $before = count($spi->writes);

    expect(fn () => $panel->refresh(RefreshMode::PARTIAL))->toThrow(SSD1680Exception::class, 'run a full refresh after boot first')
        ->and(count($spi->writes))->toBe($before);
});

it('runs a partial refresh with the border held at VCOM, and only syncs what changed since', function (): void {
    [$panel, $spi] = epd(SSD1680BW::class);
    $panel->refresh();
    $panel->transmit(0, 0, [0xF0], 8, 1);
    $before = count($spi->writes);

    $panel->refresh(RefreshMode::PARTIAL);
    $after_partial = count($spi->writes);
    $panel->refresh(RefreshMode::PARTIAL);

    expect(array_slice($spi->commands($before), 0, 3))->toBe([[0x3C, [0x80]], [0x22, [0xFF]], [0x20, []]])
        ->and($spi->commands($before)[7])->toBe([0x26, [0xF0]])
        ->and($spi->commands($after_partial))->toBe([[0x3C, [0x80]], [0x22, [0xFF]], [0x20, []]]);
});

it('waits for BUSY to fall after a refresh', function (): void {
    [$panel, , $pins] = epd(SSD1680BW::class);
    $pins['busy']->script = [true, true, true, false];
    $reads = $pins['busy']->reads;

    $panel->refresh();

    expect($pins['busy']->reads - $reads)->toBe(4);
});

it('runs a full refresh on the black/white/red panel and refuses a partial one', function (): void {
    [$panel, $spi] = epd(SSD1680BWR::class);
    $before = count($spi->writes);

    $panel->refresh(RefreshMode::FULL);

    expect($spi->commands($before))->toBe([[0x3C, [0x05]], [0x22, [0xF7]], [0x20, []]])
        ->and(fn () => $panel->refresh(RefreshMode::PARTIAL))->toThrow(SSD1680Exception::class, 'has no partial refresh');
});

// --- sleep, settings, errors --------------------------------------------------------

it('sleeps, and boots again from a hardware reset', function (): void {
    [$panel, $spi, $pins] = epd(SSD1680BW::class);
    $panel->refresh();
    $before = count($spi->writes);

    $panel->sleep();

    expect($spi->commands($before))->toBe([[0x10, [0x01]]])
        ->and($panel->hasBooted())->toBeFalse()
        ->and(fn () => $panel->refresh(RefreshMode::PARTIAL))->not->toThrow(SSD1680Exception::class);

    $panel->boot();

    expect($pins['rst']->levels)->toBe([true, false, true, true, false, true])
        ->and(fn () => $panel->refresh(RefreshMode::PARTIAL))->toThrow(SSD1680Exception::class, 'run a full refresh after boot first');
});

it('reads any configuration key and writes the register settings', function (): void {
    [$panel, $spi] = epd(SSD1680BW::class);
    $before = count($spi->writes);

    $panel->border_waveform = new SSD1680BorderWaveform(vbd_option: 0b01, fixed_level: 0b01, gs_transition_control: false, gs_transition_setting: 0);
    $panel->temperature_sensor = SSD1680TemperatureSensor::EXTERNAL;

    expect($spi->commands($before))->toBe([[0x3C, [0x50]], [0x18, [0x48]]])
        ->and($panel->border_waveform->toByte())->toBe(0x50)
        ->and($panel->temperature_sensor)->toBe(SSD1680TemperatureSensor::EXTERNAL)
        ->and($panel->busy_timeout_ms)->toBe(30_000)
        ->and(fn () => $panel->width = 10)->toThrow(SSD1680Exception::class, "Invalid property 'width'")
        ->and(fn () => $panel->nope)->toThrow(SSD1680Exception::class, "Invalid property 'nope'");
});

it('throws when the bus refuses a write', function (): void {
    [$panel, $spi] = epd(SSD1680BW::class);
    $spi->answer = -1;

    expect(fn () => $panel->refresh())->toThrow(SSD1680Exception::class, 'SSD1680 SPI command 0x3C write failed: -1 of 1 bytes');
});

it('refuses a panel larger than the controller and a BUSY timeout under 1 ms', function (): void {
    expect(fn () => new SSD1680Configuration(width: 177))->toThrow(SSD1680Exception::class, 'drives up to 176 × 296')
        ->and(fn () => new SSD1680Configuration(height: 297))->toThrow(SSD1680Exception::class, 'drives up to 176 × 296')
        ->and(fn () => new SSD1680Configuration(busy_timeout_ms: 0))->toThrow(SSD1680Exception::class, 'busy_timeout_ms takes 1');
});

it('releases DC, RST, BUSY and PWR on close, leaving the bus to its driver', function (): void {
    [$panel, $spi, $pins] = epd(SSD1680BW::class);

    $panel->close();

    expect($pins['dc']->closed())->toBeTrue()
        ->and($pins['rst']->closed())->toBeTrue()
        ->and($pins['busy']->closed())->toBeTrue()
        ->and($pins['pwr']->closed())->toBeTrue()
        ->and($spi->closed())->toBeFalse();
});

it('roots its exception at the framework circuit exception', function (): void {
    expect(SSD1680Exception::busyTimeout(1))->toBeInstanceOf(CircuitException::class);
});

it('re-initialises before a full refresh that follows a partial one, keeping RAM', function (): void {
    [$panel, $spi, $pins] = epd(SSD1680BW::class);
    $panel->refresh();
    $panel->refresh(RefreshMode::PARTIAL);
    $before = count($spi->writes);

    $panel->refresh();
    $panel_commands = $spi->commands($before);

    expect(array_slice($panel_commands, 0, 10))->toBe(epdBootCommands())
        ->and(array_slice($panel_commands, 10))->toBe([[0x3C, [0x05]], [0x22, [0xF7]], [0x20, []]])
        ->and($pins['rst']->levels)->toBe([true, false, true, true, false, true]);

    $before = count($spi->writes);
    $panel->refresh();

    expect($spi->commands($before))->toBe([[0x3C, [0x05]], [0x22, [0xF7]], [0x20, []]]);
});
