---
type: Runbook
title: Hardware smoke
description: Proving a change on the LAFVIN 2.13 inch HAT on a Pi 5, with a scratch script booted through the real providers and a Surface ePaper framebuffer.
tags: [hardware, smoke, raspberry-pi, epaper]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-05T03:40:00Z }
---

# Rule

Pest suite stays hardware-free. Panels are proven by scratch scripts outside the repo, never committed, with someone watching the panel; a refresh that runs its BUSY time can still leave the image unchanged.

# Bench

Pi 5 (`fnk`), driver `native`: spidev0.0, DC 25, RST 17, BUSY 24, PWR 18 (enabled), 122 × 250. LAFVIN BW HAT → slug `ssd1680-bw`; Waveshare (B) V4 BWR HAT → slug `ssd1680-bwr` (one full refresh ≈ 18 s: black border and X, red squares).

# Scratch project

This package by path repo, `venusian-surface/framebuffers` + `contracts` + `nuts-and-bolts` by path repo (until Surface 0.10 is on Packagist), `microscrap/scrapyard-linux`, `gpio/digital`, `gpio/spi`, `gpio/i2c`, `venusian-voyager/io-pools`, `venusian-voyager/config`. Boot the GPIO providers, the adapter's and `SSD1680ServiceProvider` on a stub `FrameworkCore` with a `config()` shim, then `conjure('ssd1680-bw')`. Announce each run with `say`.

# Checks

Pause ~6 s per step; each must look different:

1. Full: all white.
2. Partial: one 24 × 24 square (`flushRegion` → `transmit` window → `refresh(PARTIAL)`).
3. Partial: a second square.
4. Full: all black (full after partial).
5. Full: all white.

Expected numbers: [drawing live reference](/drawing.md#live-reference).
