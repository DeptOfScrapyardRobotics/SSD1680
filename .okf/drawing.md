---
type: Guide
title: Drawing and refreshing
description: FormatSpec per variant, transmit() windows, full and partial refresh, the red-RAM mirror, timings.
tags: [drawing, formatspec, transmit, refresh, partial, epaper]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T03:40:00Z }
sources:
  - id: panel
    resource: src/SSD1680.php
    title: SSD1680::transmit()
  - id: bw
    resource: src/SSD1680BW.php
    title: SSD1680BW
  - id: bwr
    resource: src/SSD1680BWR.php
    title: SSD1680BWR
  - id: vendor
    resource: https://github.com/lafvintech/LAFVIN-2.13inch-ePaper-HAT/blob/main/RaspberryPi/python/lib/lafvin_epd/epd2in13_V4.py
    title: LAFVIN epd2in13_V4.py
---

# FormatSpec

| Variant | Spec | Bytes |
|---|---|---|
| BW | `MONO_HORIZONTAL`, `B1`, `MSB_FIRST`, no palette | white 1, black 0, x0 in bit 7, rows padded to a byte |
| BWR | `PLANAR`, `B1`, `MSB_FIRST`, palette `BLACK` (inverted) + `RED` | black plane (1 white) then red plane (1 red) |

Matches Surface's native packing (checked: BW white `FF`, black at x0 `7F`; BWR planes black then red). A Surface `epaper()` framebuffer in the panel's spec starts as paper; flush in the same spec copies.

# transmit()

`transmit(x, y, bytes, w, h)`[^panel] → `44` window (x >> 3 .. (x+w-1) >> 3), `45` window, per plane `4E`/`4F` cursor + `24` / `26`. x a multiple of 8; w a multiple of 8 unless the window ends at the right edge; byte count = ceil(w/8) × h × planes. Nothing shows until `refresh()`.

# refresh()

| Variant | FULL | PARTIAL |
|---|---|---|
| BW[^bw] | `3C` configured border, `22 F7`, `20`, BUSY | needs a FULL since boot; `3C 80` (VCOM), `22 FF`, `20`, BUSY |
| BWR[^bwr] | same as BW FULL | `unsupportedRefreshMode` |

Partial (display mode 2) drives only pixels differing from the red RAM. So after every BW refresh the windows sent since the last one are written to `26`: the red RAM mirrors what the panel shows. First full refresh after boot: send the whole panel.

A full refresh straight after partial ones ran its waveform but left the image (bench); the vendor re-inits before going back to full.[^vendor] BW therefore runs the reset + register sequence again (RAM kept) before a FULL that follows a PARTIAL.

# Live reference

LAFVIN 2.13 inch HAT, Pi 5, 10 MHz: whole frame transmit 4–5 ms; full refresh 2.28 s (2.33 s with the re-init); partial 24 × 24 0.6 s. Partial squares appeared without a flash; full after partial cleared. Waveshare 2.13 inch (B) V4 BWR, same pins: two-plane frame 8000 bytes, full refresh 17.9 s, black border and X with red squares as drawn (update control `00 80` + straight red plane; Waveshare's own `80 80` inverts the red RAM for a 0-is-ink buffer, same result).

# Related

* [connecting](/connecting.md) · [configuration](/configuration.md) · [hardware smoke](/runbooks/hardware-smoke.md)

[^panel]: SSD1680::transmit()
[^bw]: SSD1680BW
[^bwr]: SSD1680BWR
[^vendor]: LAFVIN epd2in13_V4.py
