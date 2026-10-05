# ssd1680

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dept-of-scrapyard-robotics/ssd1680.svg)](https://packagist.org/packages/dept-of-scrapyard-robotics/ssd1680)
[![License](https://img.shields.io/packagist/l/dept-of-scrapyard-robotics/ssd1680.svg)](LICENSE)

Drive SSD1680 ePaper panels from PHP over SPI, black/white and black/white/red, using the ScrapyardIO GPIO framework.

`dept-of-scrapyard-robotics/ssd1680` powers and boots the controller, writes your frames into its RAM a window at a time, and refreshes the panel: a full refresh on either kind, and a fast partial refresh on black/white panels. You describe the wiring in a config file, ask the circuit catalog for the panel, and send it bytes packed the way its `formatSpec()` describes, which is what a Surface ePaper framebuffer produces.

```
ext-posi / ext-ftdi            1:1 system and libftdi calls
  → microscrap/*               libgpiod, spidev, libmpsse in PHP
    → microscrap/scrapyard-*   adapters: the `native` and `usb` drivers
      → scrapyard-io/framework protocol managers, transports, the circuit catalog
        → dept-of-scrapyard-robotics/ssd1680   ← this package
```

| Panel | Class | Catalog slug | Refresh |
|---|---|---|---|
| black/white | `SSD1680BW` | `ssd1680-bw` | full, partial |
| black/white/red | `SSD1680BWR` | `ssd1680-bwr` | full (about 18 s) |

## Requirements

- PHP 8.4 or newer
- A Venusian 0.10 application with the `scrapyard-io/framework` 0.10 components (`gpio/spi`, `gpio/digital`, `gpio/integrated-circuits`)
- `venusian-surface/contracts` 0.10, for the `FormatSpec` the panel describes its bytes with
- An adapter for your hardware:
  - `microscrap/scrapyard-linux` (driver `native`) for a Raspberry Pi or other Linux board: `spidev` and `libgpiod`, needs `ext-posi`
  - `microscrap/scrapyard-usb` (driver `usb`) for FTDI MPSSE boards such as the FT232H, needs `ext-ftdi`
- `venusian-surface/framebuffers` 0.10 if you want Surface to pack your frames

## Installation

```bash
composer require dept-of-scrapyard-robotics/ssd1680
```

The service provider is discovered automatically. It merges the package's wiring configs under `circuits.ssd1680-bw` and `circuits.ssd1680-bwr`, and registers both panel kinds with the circuit catalog. To publish the configs into your app, run:

```bash
php computer vendor:publish --tag=ssd1680-config
```

That writes `config/circuits/ssd1680-bw.php` and `config/circuits/ssd1680-bwr.php`, each with `driver => 'none'` until you fill in your bench.

## Quick start

The LAFVIN 2.13 inch e-Paper HAT (250 × 122, black/white) on a Raspberry Pi. Its pins, shared by Waveshare's 2.13 inch HATs including the black/white/red (B) V4: DC on GPIO25, RST on GPIO17, BUSY on GPIO24, chip select on CE0, and a power switch on GPIO18 that must be driven high.

```php
// config/circuits/ssd1680-bw.php
return [
    'default_config' => 'spi',
    'configs' => [
        'spi' => [
            'driver' => 'native',
            'device' => 0,
            'chip_select' => 0,
            'dc' => ['driver' => 'native', 'device' => 0, 'pin' => 25],
            'rst' => ['driver' => 'native', 'device' => 0, 'pin' => 17],
            'busy' => ['driver' => 'native', 'device' => 0, 'pin' => 24],
            'pwr' => ['enabled' => true, 'driver' => 'native', 'device' => 0, 'pin' => 18],
        ],
    ],
];
```

```php
use Surface\Framebuffers\Native\NativeFramebufferDriver;

$panel = app('circuit')->conjure('ssd1680-bw');   // powered, reset and booted

$spec = $panel->formatSpec();
$fb = (new NativeFramebufferDriver)->epaper($spec, $panel->width(), $panel->height());   // starts white
$fb->writeRgba8($rgba, $panel->width(), $panel->height());                              // any RGBA8 image

$panel->transmit(0, 0, $fb->flush($spec, true));
$panel->refresh();
```

The panel is 122 pixels along its sources and 250 along its gates, so it's driven as 122 wide and 250 tall; rotate your image for landscape. Booting takes about 60 ms, and a full refresh about 2.3 seconds.

## Connecting

`conjure('ssd1680-bw')` reads `circuits.ssd1680-bw`, picks `default_config`, and calls `SSD1680BW::spi()` with that entry's keys. You can call the factory directly too:

```php
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680BW;

$panel = SSD1680BW::spi(
    'native', 0,
    dc: ['driver' => 'native', 'device' => 0, 'pin' => 25],
    rst: ['driver' => 'native', 'device' => 0, 'pin' => 17],
    busy: ['driver' => 'native', 'device' => 0, 'pin' => 24],
    pwr: ['enabled' => true, 'driver' => 'native', 'device' => 0, 'pin' => 18],
);
```

The factory opens an unconnected bus in `mode` (0 by default) and refuses a bus your app already opened in a different mode. It clocks this chip select at `speed`, 10 MHz by default and at most 20 MHz, the controller's limit. DC, RST, BUSY and PWR are opened after the bus, so on an FT232H they ride the same USB context as its SPI engine. Leave `pwr` disabled when the panel has no power switch. Pass `width` and `height` for a panel other than 122 × 250 (up to 176 × 296), and `boot_now: false` to build without booting.

### Building the transport yourself

```php
use DeptOfScrapyardRobotics\Displays\SSD1680\Transports\SSD1680SPITransport;

$spi = app('gpio.spi')->driver('native')->connectTo(0)->mode(0)->speed(10_000_000)->register()->device(0, 0);
$pins = app('gpio.digital')->driver('native')->connectTo(0)->register();

$panel = new SSD1680BW(
    new SSD1680SPITransport($spi, $pins->output(0, 25), $pins->output(0, 17), $pins->input(0, 24), $pins->output(0, 18)),
    boot_now: true,
);
```

## Drawing

Each ink is a one-bit plane, eight pixels to a byte with the leftmost in bit 7, rows padded to a whole byte. On the black/white panel `1` is white and `0` is black. The black/white/red panel takes two planes back to back: black (1 white, 0 black) then red (1 red).

`transmit($x, $y, $bytes, $width, $height)` writes a window into the controller's RAM. The panel doesn't change until you call `refresh()`. RAM columns are bytes, so `$x` is a multiple of 8, and the width is a multiple of 8 unless the window ends at the panel's right edge:

```php
use Surface\Contracts\Framebuffers\Region;

$fb->writeRgba8($square_rgba, 24, 24, 48, 100);
$region = new Region(48, 100, 24, 24);

$panel->transmit($region->x, $region->y, $fb->flushRegion($region, $spec, true), $region->width, $region->height);
```

## Refreshing

```php
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshMode;

$panel->refresh();                       // full: about 2.3 s, the whole panel flashes
$panel->refresh(RefreshMode::PARTIAL);   // black/white only: about 0.6 s, only changed pixels move
```

A partial refresh moves only the pixels that differ from the frame the panel is showing. The controller keeps that frame in its red RAM, so after every refresh the black/white panel copies the windows you sent into it. Run a full refresh after boot before the first partial one, and send the whole panel for that first full refresh, so the red RAM starts out matching the screen; a partial refresh before any full one throws. A full refresh that follows partial ones re-runs the controller's reset and register setup first (RAM is kept), as the panel vendor does; without it the panel ignores the full refresh.

ePaper panels last longer if you don't refresh partially forever; run a full refresh now and then.

The black/white/red panel has no partial waveform: `refresh(RefreshMode::PARTIAL)` throws there.

Every refresh blocks until the panel lowers BUSY. If BUSY stays high past `busy_timeout_ms` (30 s by default; a black/white/red full refresh takes about 15 s), it throws.

## Sleep

```php
$panel->sleep();     // deep sleep; the image stays on the panel
$panel->boot();      // wakes it with a hardware reset and sets it up again
```

## Configuration object

`SSD1680Configuration` holds the geometry and the values boot writes. Every argument is optional.

| Argument | Default | Meaning |
|---|---|---|
| `width`, `height` | `122`, `250` | panel size along sources and gates, up to 176 × 296 |
| `gate_setting` | `0` | gate scan setting: bit 2 bottom to top, bit 1 interlaced, bit 0 first gate |
| `border_waveform` | `new SSD1680BorderWaveform` | border during full refreshes (`0x05`) |
| `display_update_control` | `new SSD1680DisplayUpdateControl1` | RAM content option and source output (`0x00 0x80`) |
| `temperature_sensor` | `SSD1680TemperatureSensor::INTERNAL` | where the waveform's temperature comes from |
| `max_packet_size` | `4096` | largest data write, in bytes |
| `busy_timeout_ms` | `30000` | longest a command or refresh may hold BUSY |

Every register breakout checks its fields and throws on a value out of range.

## Settings

```php
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680TemperatureSensor;

$panel->temperature_sensor = SSD1680TemperatureSensor::EXTERNAL;
$panel->border_waveform = new SSD1680BorderWaveform(vbd_option: 0b01, fixed_level: 0b01, gs_transition_control: false, gs_transition_setting: 0);
```

Any configuration key reads as a property. `border_waveform`, `display_update_control` and `temperature_sensor` can be written, and the panel gets them straight away. The same setters are methods, as are `setRAMWindow()`, `setRAMCursor()`, `runUpdate()`, `softwareReset()` and `waitUntilIdle()`.

## Errors

Everything throws `SSD1680Exception`, which descends from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException`.

| Factory | When |
|---|---|
| `busyTimeout` | BUSY stays high past `busy_timeout_ms` |
| `spiWriteFailed` | the bus wrote fewer bytes than asked |
| `windowOutsidePanel`, `unalignedWindow`, `wrongByteCount` | a `transmit()` window that doesn't fit the RAM |
| `partialBeforeFull` | a partial refresh before the first full one after boot |
| `unsupportedRefreshMode` | a partial refresh on the black/white/red panel |
| `invalidGeometry` | a panel larger than 176 × 296 |
| `invalidRegisterValue` | a register field out of range |
| `notConnected`, `incompletePin` | no bus or pin from the driver, or a pin config missing a key |
| `invalidSpiMode`, `spiClockOutOfRange`, `wrongSpiMode` | SPI settings the panel can't use |
| `invalidProperty` | an unknown property or configuration key |

SPI has no acknowledge, so a missing panel shows up as a BUSY timeout rather than a write error.

## Closing

```php
$panel->close();
```

`close()` releases DC, RST, BUSY and PWR. The SPI connection belongs to its driver and stays open for other chips. The panel keeps showing its last image.

## Configuration files

`config/circuits/ssd1680-bw.php` and `ssd1680-bwr.php` share this shape:

| Key | Default | Meaning |
|---|---|---|
| `default_config` | `'spi'` | which entry under `configs` `conjure()` uses |
| `configs.spi.driver` | `'none'` | SPI adapter: `native` or `usb` |
| `configs.spi.device` | `''` | SPI bus number, or `ft232h` |
| `configs.spi.chip_select` | `0` | chip select |
| `configs.spi.mode` | `0` | SPI mode |
| `configs.spi.speed` | `10_000_000` | this chip select's clock in Hz, up to 20 MHz |
| `configs.spi.width` / `height` | `null` | panel size; null keeps 122 × 250 |
| `configs.spi.dc` / `rst` / `busy` | pins 0 / 1 / 2 | `driver`, `device`, `pin` |
| `configs.spi.pwr` | disabled, pin 3 | `enabled`, `driver`, `device`, `pin` |
| `configs.*.boot_now` | `true` | boot during `conjure()` |

## Testing

```bash
composer install
vendor/bin/pest
```

The suite runs against recording fakes of the SPI bus, the pins and a scripted BUSY line, so it needs no hardware. Both variants were also exercised on a Raspberry Pi 5 for this release, with someone watching. A LAFVIN 2.13 inch black/white HAT ran full refreshes in about 2.3 seconds, partial refreshes adding squares in about 0.6 seconds without a flash, and a full refresh clearing the panel after them. A Waveshare 2.13 inch e-Paper HAT (B) V4, black/white/red on the same pins, showed a black border and X with two red squares after an 18-second full refresh.

## Security

The driver writes commands and image data to hardware the PHP process can open. See [SECURITY.md](SECURITY.md) for the support policy and how to report a vulnerability.

## License

MIT. See [LICENSE](LICENSE).
