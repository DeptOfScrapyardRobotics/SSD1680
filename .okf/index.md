---
okf_version: "0.2"
---

# dept-of-scrapyard-robotics/ssd1680

SSD1680 ePaper driver for `scrapyard-io/framework` 0.10: black/white (`SSD1680BW`) and black/white/red (`SSD1680BWR`) panels over SPI with DC, RST, BUSY and an optional PWR line. Conjured from config, byte-aligned window writes, full refresh, partial refresh on black/white.

Read this index first, open only concepts task needs. Every concept `status: draft` until human verifies.

# Concepts

* [Package](overview.md) - SSD1680 ePaper driver for scrapyard-io/framework 0.10 — identity, requires, classes, boot, errors.
* [Connecting](connecting.md) - conjure() and the spi() factory, DC / RST / BUSY / PWR, the LAFVIN HAT pinout, building the transport by hand.
* [Drawing and refreshing](drawing.md) - FormatSpec per variant, transmit() windows, full and partial refresh, the red-RAM mirror, timings.
* [Configuration](configuration.md) - SSD1680Configuration fields, register breakouts, properties, circuits.ssd1680-bw / -bwr keys.

# Runbooks

* [Hardware smoke](runbooks/hardware-smoke.md) - LAFVIN 2.13 inch HAT on a Pi 5, scratch script booted through the real providers.

# Log

* [log.md](log.md)
