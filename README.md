Introduction
============

PHP Package for the SSD1680 ePaper (electronic ink) display controller. The
SSD1680 drives both black/white panels and black/white/red tri-color panels
from the same driver, writing each color to its own RAM plane.

Compatible SPI Interfaces
===============
The SSD1680 display communicates with your device over SPI, the Serial Peripheral Interface.

You can interface with displays such as the SSD1680 with this package the following ways:
* A Linux Single-Board Computer's exposed GPIO pins using the dedicated SPI MOSI/SCK and CS pins as well as GPIO pins for DC, RST and BUSY.
* An MPSSE-enabled USB-to-Serial device such as an FT232H generally using D0 and SCK, D1 for MOSI, D2 for MISO and D3 for CS, plus GPIO for RST/DC/BUSY and connected to nearly any Linux or MacOS USB port.

A BUSY input is required, the same as DC and RST. ePaper refreshes take seconds,
and the driver blocks on the BUSY line to know when the panel has finished.

Dependencies
=============
This package makes use of modules within:
* [The ScrapyardIO Framework](https://github.com/ScrapyardIO/framework)

This package also requires one of the following extensions in order to interface with SPI
* [POSI Extension v^0.4.0 or newer](https://github.com/php-io-extensions/posi)
* [FTDI Extension v^0.4.0 or newer](https://github.com/php-io-extensions/ftdi)

In addition, an extension wrapper package is needed

For ext-posi
* [Microscrap POSIX Package v0.4.0 or newer](https://github.com/microscrap/posix)
* [Microscrap Native SPI Package v0.4.0 or newer](https://github.com/microscrap/spi)
* [Microscrap Native GPIO Package v0.4.0 or newer](https://github.com/microscrap/gpio)

For ext-ftdi
* [Microscrap FTDI Package v0.4.0 or newer](https://github.com/microscrap/ftdi)
* [Microscrap MPSSE Package v0.4.0 or newer](https://github.com/microscrap/mpsse)

Installing from Composer
====================
Inside the root of your PHP Project, simply require the SSD1680 package from composer
```shell
composer require dept-of-scrapyard-robotics/ssd1680
```
Framework Configuration
====================
If you would like to use the ScrapyardIO Framework to bootstrap your display without
wasting lines configuring your display right in the script you can add your desired
configuration to scrapyard-io.php, such as in this example:

### SPI
```php

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\SSD1680;

return [
    'displays' => [
        // For Native Configurations 
        'ssd1680-native' => [
            'class_name' => SSD1680::class,
            'connection' => ['driver' => 'native'],
            'startup' => [
                'spi' => [0, 0],
                'gpiochip' => [0],
                'rst' => [24],
                'dc' => [22],
                'busy' => [17],
            ],
        ],
        // For USB Configurations
        'ssd1680-usb' => [
            'class_name' => SSD1680::class,
            'connection' => ['driver' => 'usb'],
            'startup' => [
                'spi' => ['ft232h', 0],
                'gpiochip' => ['ft232h'],
                'rst' => [0],
                'dc' => [1],
                'busy' => [2],
            ],
        ],        
    ]
];
```

Basic Usage
============

### Native (POSIX) SPI driver. (Single Board Computers)
```php

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\SSD1680;

$native_spi_display = SSD1680::connection('native')
    ->spi(0, 0)
    ->gpiochip(0)
    ->rst(24)
    ->dc(22)
    ->busy(17)
    ->create()
```

### USB (MPSSE) driver using SPI. (Linux and MacOS)
```php

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\SSD1680;

$usb_spi_display = SSD1680::connection('usb')
    ->spi('ft232h', 0)
    ->gpiochip('ft232h')
    ->rst(0)
    ->dc(1)
    ->busy(2)
    ->create()
```

## Alternative Usage

### Using Through the Display Library (as a black/white panel)
```php
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\SSD1680;
use RealityInterface\Displays\Applied\ePaper\BWePaperDisplay;

$ssd1680 = SSD1680::connection('usb')
    ->spi('ft232h', 0)
    ->gpiochip('ft232h')
    ->rst(0)
    ->dc(1)
    ->busy(2)
    ->create()
    
$display = BWePaperDisplay::as($ssd1680);

```

### Using Through the Display Library (as a black/white/red panel)
```php
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\SSD1680;
use RealityInterface\Displays\Applied\ePaper\TriColorePaperDisplay;

$ssd1680 = SSD1680::connection('usb')
    ->spi('ft232h', 0)
    ->gpiochip('ft232h')
    ->rst(0)
    ->dc(1)
    ->busy(2)
    ->create()
    
$display = TriColorePaperDisplay::as($ssd1680);

```

### Using Through the Display Framework (with an autoloaded config)
```php

use RealityInterface\Displays\Applied\ePaper\BWePaperDisplay;
use RealityInterface\Displays\Applied\ePaper\TriColorePaperDisplay;

$bw = BWePaperDisplay::using('ssd1680-usb');
$bwr = TriColorePaperDisplay::using('ssd1680-usb');

```

Display API
==========
The setters in this API interface with the device directly (register writes), so
you can use property access while still working against the panel itself.

Readable Properties (Getters)
-----------------------------
There are no readable magic properties exposed for the SSD1680 in this package.

Writable Properties (Setters)
-----------------------------
* `$display->data_entry_mode = SSD1680DataEntryMode::Y_INCREMENT_X_INCREMENT;`
  Sets the RAM data entry (address auto-increment) direction.

* `$display->border_waveform = new SSD1680BorderWaveform(...);`
  Sets the border waveform control.

* `$display->display_update_control = new SSD1680DisplayUpdateControl1(...);`
  Sets the display update control (RAM-to-panel mapping for each plane).

* `$display->temperature_sensor = SSD1680TemperatureSensor::INTERNAL;`
  Selects the temperature sensor source used for the refresh waveform.

* `$display->deep_sleep = true;`
  Drops the controller into deep sleep. A hardware reset is required to wake it.

Drawing on the Display
============
Draw with a `Screen`, which wraps a `GFXRenderer` over a `ChannelSortedFrameBuffer`
matched to the panel's `FormatSpec`, then ships the bytes on `render()`. ePaper is
a single slow full refresh, so paint one complete composition and render once.

Colors are `EInkColor` cases. On a black/white panel use `WHITE` and `BLACK`; on a
black/white/red panel `RED` is also available and lands on its own RAM plane.

```php
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\SSD1680;
use Microscrap\GFX\PhpdaFruit\GFXRenderer;
use RealityInterface\Displays\Applied\ePaper\Buffers\ChannelSortedFrameBuffer;
use RealityInterface\Displays\Applied\ePaper\Enums\EInkColor;
use RealityInterface\Displays\Applied\ePaper\TriColorePaperDisplay;
use RealityInterface\Displays\Screen;

$ssd1680 = SSD1680::connection('usb')
    ->spi('ft232h', 0)
    ->gpiochip('ft232h')
    ->rst(0)
    ->dc(1)
    ->busy(2)
    ->create();

$display = TriColorePaperDisplay::as($ssd1680);

$buffer = new ChannelSortedFrameBuffer($display->width(), $display->height(), $display->getFormatSpec());
$screen = new Screen($display, new GFXRenderer($buffer));

$screen
    ->fill(EInkColor::WHITE->value)
    ->drawRect(0, 0, $display->width(), $display->height(), EInkColor::BLACK->value)
    ->setTextColor(EInkColor::BLACK->value)
    ->setCursor(8, 20)
    ->print('SSD1680')
    ->setTextColor(EInkColor::RED->value)
    ->setCursor(8, 40)
    ->print('B/W/R')
    ->render();
```
