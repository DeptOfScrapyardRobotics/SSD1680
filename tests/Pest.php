<?php

/*
| Proven against recording fakes: every command and data byte the panel would
| see, tagged by the DC level it went out under, every RST and PWR level, and
| a scripted BUSY line. Nothing here touches a bus. The live check is a 250 x
| 122 black/white SSD1680 on a LAFVIN 2.13 inch HAT on a Raspberry Pi.
*/

use DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\ConfigPathVessel;
use DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeDigitalIOConnectionDriver;
use DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeSPIConnectionDriver;
use GeneralPurposeIO\Digital\DigitalOConnectionManager;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;
use GeneralPurposeIO\SPI\SPIConnectionManager;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

/*
| A Venusian app's core defines config() over the container's config repository;
| CircuitRegistry::conjure() calls it. A package suite has no core, so this
| stands in for it the same way.
*/
if (! function_exists('config')) {
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        $config = ControlPanel::getInstance()->make('config');

        return match (true) {
            is_null($key) => $config,
            is_array($key) => $config->set($key),
            default => $config->get($key, $default),
        };
    }
}

/**
 * The shared container as an app sets it up: config, the circuit catalog, and the two protocol managers, each
 * with a 'fake' driver.
 *
 * @return array{spi: FakeSPIConnectionDriver, digital: FakeDigitalIOConnectionDriver, app: ConfigPathVessel}
 */
function fakeBench(array $config = []): array
{
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository($config));
    $app->registerInstance('circuit', new CircuitRegistry);
    ControlPanel::setInstance($app);

    $bench = [
        'spi' => new FakeSPIConnectionDriver,
        'digital' => new FakeDigitalIOConnectionDriver,
        'app' => $app,
    ];

    $app->registerInstance('gpio.spi', (new SPIConnectionManager($app))->extend('fake', fn () => $bench['spi']));
    $app->registerInstance('gpio.digital', (new DigitalOConnectionManager($app))->extend('fake', fn () => $bench['digital']));

    return $bench;
}

pest()->afterEach(function (): void {
    ControlPanel::setInstance(null);
})->in(__DIR__);

/**
 * @template T of \DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680
 *
 * @param  class-string<T>  $class
 * @return array{0: T, 1: \DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeSPITransport, 2: array{dc: \DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeOutputPin, rst: \DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeOutputPin, busy: \DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeBusyPin, pwr: \DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeOutputPin}}
 */
function epd(string $class, ?\DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Configuration $config = null, bool $boot = true): array
{
    $pins = [
        'dc' => new \DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeOutputPin(25),
        'rst' => new \DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeOutputPin(17),
        'busy' => new \DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeBusyPin(24),
        'pwr' => new \DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeOutputPin(18),
    ];
    $spi = new \DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\FakeSPITransport($pins['dc']);
    $transport = new \DeptOfScrapyardRobotics\Displays\SSD1680\Transports\SSD1680SPITransport($spi, $pins['dc'], $pins['rst'], $pins['busy'], $pins['pwr']);

    return [new $class($transport, $config ?? new \DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680Configuration, boot_now: $boot), $spi, $pins];
}

/** The vendor init for a 122 x 250 panel, one entry per command with its parameters. */
function epdBootCommands(): array
{
    return [
        [0x12, []],
        [0x01, [0xF9, 0x00, 0x00]],
        [0x11, [0x03]],
        [0x44, [0x00, 0x0F]],
        [0x45, [0x00, 0x00, 0xF9, 0x00]],
        [0x3C, [0x05]],
        [0x21, [0x00, 0x80]],
        [0x18, [0x80]],
        [0x4E, [0x00]],
        [0x4F, [0x00, 0x00]],
    ];
}

