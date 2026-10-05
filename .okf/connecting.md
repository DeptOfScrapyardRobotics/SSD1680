---
type: Guide
title: Connecting an SSD1680
description: conjure() and the spi() factory, DC / RST / BUSY / PWR, the LAFVIN HAT pinout, building the transport by hand.
tags: [spi, transport, dc, rst, busy, pwr, conjure]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T03:40:00Z }
sources:
  - id: factory
    resource: src/Concerns/ConjuresSSD1680.php
    title: ConjuresSSD1680
  - id: transport
    resource: src/Transports/SSD1680SPITransport.php
    title: SSD1680SPITransport
  - id: vendor
    resource: https://github.com/lafvintech/LAFVIN-2.13inch-ePaper-HAT/blob/main/RaspberryPi/python/lib/lafvin_epd/epdconfig.py
    title: LAFVIN epdconfig.py
---

# conjure

`app('circuit')->conjure('ssd1680-bw')` (or `ssd1680-bwr`) → `circuits.<slug>` → `{Class}::spi()` with config keys as named args.

# spi()

`spi(driver, device, dc, rst, busy, pwr = [], chip_select = 0, mode = 0, speed = 10_000_000, width = null, height = null, boot_now = true)`[^factory]

- `speed` outside 1 Hz – 20 MHz (datasheet 50 ns write cycle) or `mode` not 0–3 → refused before the bus.
- Unconnected bus → opened in `mode` at `speed`; shared bus in another mode refused; chip select clocked at `speed`.
- `dc`, `rst` outputs, `busy` input, `pwr` output only when `enabled`; opened after the bus.

Transport:[^transport] command = DC low + register byte, parameters DC high; data in `max_packet_size` packets; every write checked; BUSY high = busy, read every 1 ms up to `busy_timeout_ms`; `power()` drives PWR.

# LAFVIN / Waveshare 2.13 inch HATs

From the vendor's config:[^vendor] RST GPIO17, DC GPIO25, CS CE0 (GPIO8 → spidev0.0), BUSY GPIO24, PWR GPIO18 (must be high), SPI mode 0. Panel 250 × 122, driven as width 122 (sources) × height 250 (gates). Waveshare's 2.13 inch (B) V4 BWR HAT uses the same pins (waveshareteam/e-Paper epdconfig.py).

# By hand

```php
$spi = app('gpio.spi')->driver('native')->connectTo(0)->mode(0)->speed(10_000_000)->register()->device(0, 0);
$pins = app('gpio.digital')->driver('native')->connectTo(0)->register();
$panel = new SSD1680BW(new SSD1680SPITransport($spi, $pins->output(0, 25), $pins->output(0, 17), $pins->input(0, 24), $pins->output(0, 18)), boot_now: true);
```

# Related

* [configuration](/configuration.md) · [overview](/overview.md)

[^factory]: ConjuresSSD1680
[^transport]: SSD1680SPITransport
[^vendor]: LAFVIN epdconfig.py
