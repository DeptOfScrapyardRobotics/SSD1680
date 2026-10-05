<?php

use DeptOfScrapyardRobotics\Displays\SSD1680\Providers\SSD1680ServiceProvider;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680BW;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680BWR;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Exception;
use GeneralPurposeIO\Contracts\SPI\SPIMode;

/** The LAFVIN 2.13 inch HAT's wiring, with overrides. */
function hatWiring(array $overrides = []): array
{
    return array_replace_recursive([
        'default_config' => 'spi',
        'configs' => ['spi' => [
            'driver' => 'fake',
            'device' => 0,
            'chip_select' => 0,
            'dc' => ['driver' => 'fake', 'device' => 0, 'pin' => 25],
            'rst' => ['driver' => 'fake', 'device' => 0, 'pin' => 17],
            'busy' => ['driver' => 'fake', 'device' => 0, 'pin' => 24],
            'pwr' => ['enabled' => true, 'driver' => 'fake', 'device' => 0, 'pin' => 18],
        ]],
    ], $overrides);
}

function epdBench(string $slug, array $wiring): array
{
    $bench = fakeBench(['circuits' => [$slug => $wiring]]);
    $provider = new SSD1680ServiceProvider($bench['app']);
    $provider->register();
    $provider->boot();

    return $bench;
}

it('conjures a booted panel: bus in mode 0 at 10 MHz, then DC, RST, BUSY and PWR', function (string $slug, string $class): void {
    $bench = epdBench($slug, hatWiring());

    $panel = $bench['app']->make('circuit')->conjure($slug);

    expect($panel)->toBeInstanceOf($class)
        ->and($panel->hasBooted())->toBeTrue()
        ->and($bench['spi']->settingsOf(0)->mode)->toBe(SPIMode::MODE_0)
        ->and($bench['spi']->slaves['0:0']->clock())->toBe(10_000_000)
        ->and($bench['digital']->outputs['0:18']->levels)->toBe([true])
        ->and($bench['digital']->outputs['0:17']->levels)->toBe([true, false, true])
        ->and($bench['digital']->inputs)->toHaveKey('0:24')
        ->and($bench['digital']->outputs)->toHaveKey('0:25');
})->with([
    'black/white' => ['ssd1680-bw', SSD1680BW::class],
    'black/white/red' => ['ssd1680-bwr', SSD1680BWR::class],
]);

it('leaves PWR alone when it is not enabled', function (): void {
    $bench = epdBench('ssd1680-bw', hatWiring(['configs' => ['spi' => ['pwr' => ['enabled' => false]]]]));

    $bench['app']->make('circuit')->conjure('ssd1680-bw');

    expect($bench['digital']->outputs)->not->toHaveKey('0:18');
});

it('takes the panel size and clock from config', function (): void {
    $bench = epdBench('ssd1680-bw', hatWiring(['configs' => ['spi' => ['width' => 128, 'height' => 296, 'speed' => 20_000_000]]]));

    $panel = $bench['app']->make('circuit')->conjure('ssd1680-bw');

    expect([$panel->width(), $panel->height()])->toBe([128, 296])
        ->and($bench['spi']->slaves['0:0']->clock())->toBe(20_000_000);
});

it('refuses a clock past 20 MHz or a mode that does not exist before touching the bus', function (array $spi, string $message): void {
    $bench = epdBench('ssd1680-bw', hatWiring(['configs' => ['spi' => $spi]]));

    expect(fn () => $bench['app']->make('circuit')->conjure('ssd1680-bw'))->toThrow(SSD1680Exception::class, $message)
        ->and($bench['spi']->opened)->toBe([]);
})->with([
    'clock' => [['speed' => 20_000_001], 'SPI clock 20000001 Hz'],
    'mode' => [['mode' => 5], 'SPI mode 5 does not exist'],
]);

it('shares a bus in the configured mode and refuses one in another', function (int $bus_mode, bool $shared): void {
    $bench = epdBench('ssd1680-bw', hatWiring());
    $bench['app']->make('gpio.spi')->driver('fake')->connectTo(0)->mode($bus_mode)->register();

    $conjure = fn () => $bench['app']->make('circuit')->conjure('ssd1680-bw');

    $shared
        ? expect($conjure())->toBeInstanceOf(SSD1680BW::class)
        : expect($conjure)->toThrow(SSD1680Exception::class, 'runs in mode 3; this panel is configured for mode 0');
})->with([
    'mode 0' => [0, true],
    'mode 3' => [3, false],
]);

it('names a DC, RST or BUSY pin config missing its driver, device or pin', function (string $line): void {
    $wiring = hatWiring();
    unset($wiring['configs']['spi'][$line]['pin']);
    $bench = epdBench('ssd1680-bw', $wiring);

    expect(fn () => $bench['app']->make('circuit')->conjure('ssd1680-bw'))->toThrow(SSD1680Exception::class, "needs its {$line} pin");
})->with(['dc', 'rst', 'busy']);

it('builds without booting when boot_now is false', function (): void {
    $bench = epdBench('ssd1680-bw', hatWiring(['configs' => ['spi' => ['boot_now' => false]]]));

    $panel = $bench['app']->make('circuit')->conjure('ssd1680-bw');

    expect($panel->hasBooted())->toBeFalse()
        ->and($bench['spi']->slaves['0:0']->writes)->toBe([]);
});
