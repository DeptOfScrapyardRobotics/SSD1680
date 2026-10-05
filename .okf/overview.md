---
type: Package
title: dept-of-scrapyard-robotics/ssd1680
description: SSD1680 ePaper driver for scrapyard-io/framework 0.10 — identity, requires, classes, boot, errors.
resource: composer.json
tags: [ssd1680, epaper, e-ink, display, spi, package]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T03:00:00Z }
sources:
  - id: composer
    resource: composer.json
    title: Package manifest
  - id: panel
    resource: src/SSD1680.php
    title: SSD1680
  - id: bootstrap
    resource: src/Concerns/SSD1680Bootstrap.php
    title: SSD1680Bootstrap
  - id: exception
    resource: src/SSD1680Exception.php
    title: SSD1680Exception
  - id: old
    resource: PSS/Extensions/Embedded/SSD1680 (0.4.3)
    title: 0.4.3 driver
  - id: vendor
    resource: https://github.com/lafvintech/LAFVIN-2.13inch-ePaper-HAT
    title: LAFVIN epd2in13_V4 + epdconfig
---

# Identity

| Field | Value |
|---|---|
| Composer | `dept-of-scrapyard-robotics/ssd1680` **0.10.0**, alias `dev-main` → `0.10.x-dev` |
| PHP | `^8.4\|^8.5\|^8.6` |
| Namespace | `DeptOfScrapyardRobotics\Displays\SSD1680\` → `src/` |
| Provider | `Providers\SSD1680ServiceProvider` |
| Catalog slugs | `ssd1680-bw` → `SSD1680BW`, `ssd1680-bwr` → `SSD1680BWR` (`SSD1680CatalogIc`) |

New in 0.10; register map and boot drawn from the 0.4.3 driver[^old] and the LAFVIN vendor driver.[^vendor]

# Requires

Split components only:[^composer] `gpio/contracts`, `gpio/integrated-circuits` (`Bootable`, `DataRegister`), `venusian-surface/contracts` (`FormatSpec`), `venusian-voyager/nuts-and-bolts`, `venusian-voyager/vessel`. Suggests `gpio/spi`, `gpio/digital`, `microscrap/scrapyard-linux`, `microscrap/scrapyard-usb`.

# Classes

| Class | Role |
|---|---|
| `SSD1680` | abstract; `Bootable` + `DisplayPanel` + `WindowAddressable` + `RefreshesOnCommand`; `transmit()`, `spi()`[^panel] |
| `SSD1680BW` | black RAM only; full + partial refresh; red RAM mirror |
| `SSD1680BWR` | black + red RAM planes; full refresh only |
| `SSD1680Configuration` | geometry + boot registers + timeouts |
| `Transports\SSD1680SPITransport` | SPI + DC, RST, BUSY, optional PWR |
| `Breakouts\{BorderWaveform, DisplayUpdateControl1, DriverOutputControl}` | range-checked registers |
| `Enums\{OpCode, UpdateSequence, DeepSleepMode, TemperatureSensor, DataEntryMode, SPIClock, Geometry, CatalogIc}` | |

Not `Switchable`: ePaper keeps its image unpowered.

# Boot

Once:[^bootstrap] PWR on → RST high 20 ms / low 2 ms / high 20 ms → BUSY → `12` + BUSY → `01 (h-1)lo (h-1)hi gate` → `11 03` → `44 00 (w-1)>>3` → `45 00 00 (h-1)lo (h-1)hi` → `3C 05` → `21 00 80` → `18 80` → `4E 00` → `4F 00 00` → BUSY. Data entry fixed at 03 (transmit depends on it). LAFVIN 2.13 inch HAT: 56 ms.

`sleep()` → `10 01`; only a hardware reset wakes it, so the next `boot()` runs everything again.

# Errors

`SSD1680Exception` → `CircuitException` → `GPIOLevelException`.[^exception] `busyTimeout`, `spiWriteFailed`, `windowOutsidePanel`, `unalignedWindow`, `wrongByteCount`, `partialBeforeFull`, `unsupportedRefreshMode`, `invalidGeometry` (> 176 × 296), `invalidRegisterValue`, `invalidProperty`, `notConnected`, `incompletePin`, `invalidSpiMode`, `spiClockOutOfRange` (> 20 MHz), `wrongSpiMode`, `invalidPacketSize`.

# Related

* [connecting](/connecting.md) · [drawing](/drawing.md) · [configuration](/configuration.md)

[^composer]: Package manifest
[^panel]: SSD1680
[^bootstrap]: SSD1680Bootstrap
[^exception]: SSD1680Exception
[^old]: 0.4.3 driver
[^vendor]: LAFVIN epd2in13_V4 + epdconfig
