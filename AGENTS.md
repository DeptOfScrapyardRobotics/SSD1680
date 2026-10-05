# Agent guidelines — dept-of-scrapyard-robotics/ssd1680

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s), bump `generated.at`, and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them. The bundle documents the package, never a session.

Do **not** create `.okf` folders under `src/*` — knowledge for this package lives at the package root only. Catalog, transport and adapter semantics belong to `scrapyard-io/framework`'s bundle, framebuffer packing to Surface's; point there, do not restate them here.

## Where this package sits

`ext-posi` / `ext-ftdi` → `microscrap/*` → `scrapyard-io/framework` (protocol managers, transports, the circuit catalog, `DisplayPanel`) → **`dept-of-scrapyard-robotics/ssd1680`** (panel driver) → apps and Surface.

## Package rules (quick) — 0.10.x

- Composer: `dept-of-scrapyard-robotics/ssd1680` **0.10.0**. PHP `^8.4|^8.5|^8.6`. Namespace `DeptOfScrapyardRobotics\Displays\SSD1680\` → `src/`.
- **Requires split components only**: `gpio/contracts`, `gpio/integrated-circuits`, `venusian-surface/contracts`, `venusian-voyager/nuts-and-bolts`, `venusian-voyager/vessel`. Never `scrapyard-io/framework`, `venusian/surface` or `venusian/framework`. Protocol components and adapters are `suggest`.
- **Panel = `Bootable` + `DisplayPanel` + `WindowAddressable` + `RefreshesOnCommand`.** Not `Switchable`. `SSD1680` is abstract; `SSD1680BW` (black RAM, full + partial) and `SSD1680BWR` (black + red RAM, full only) supply `ramPlanes()`, `generateFormatSpec()` and `refresh()`. Surface 0.10 has no `FormatSpecification` interface; keep the three `formatSpec` methods as plain methods.
- **Boot** = PWR on, hardware reset, BUSY, software reset, driver output (gates = height), data entry fixed at X-then-Y increment (`transmit()` depends on it), full RAM window, border, update control 1, temperature sensor, cursor home. `initialize()` is everything after PWR; BW re-runs it before a FULL that follows a PARTIAL (bench: the controller ignores that FULL otherwise; RAM survives).
- **Red RAM mirror** (BW): partial refresh drives pixels differing from 0x26, so every refresh replays the windows sent since the last into 0x26. PARTIAL before the first FULL after boot throws.
- **Windows** are byte-aligned on X; `transmit()` validates bounds, alignment and byte count before writing.
- **The factory is the config shape.** `ConjuresSSD1680::spi()` parameters are exactly a `circuits.ssd1680-bw|bwr.configs.*` entry's keys; the provider catalogs both slugs from `SSD1680CatalogIc`. A new config key = a new factory parameter, and the reverse. Bus first, then DC, RST, BUSY, PWR.
- **Every write is checked; every BUSY wait has a timeout.** Register breakouts range-check, never mask. Limits live in enums (`SSD1680SPIClock`, `SSD1680Geometry`).
- **Reach the framework through the container.** The factory resolves `gpio.spi` / `gpio.digital` from `ControlPanel::getInstance()`; apps call `app('circuit')->conjure()`.
- **Exceptions** descend from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException` → `GPIOLevelException`.
- Enums int-backed, FULLY UPPERCASE cases. No class constants. `is_null($x)` over `$x === null`.

## Verification

```bash
vendor/bin/pest            # recording fake bus, pins and scripted BUSY; no hardware
```

Run it under NTS and ZTS PHP before every commit. Suites stay hardware-free: panels are for scratch smoke scripts, never committed and never in `tests/`. `.okf/runbooks/hardware-smoke.md` has the script's shape.

Hardware truth: a LAFVIN 2.13 inch black/white HAT and a Waveshare 2.13 inch (B) V4 black/white/red HAT (both 250 × 122, driven 122 × 250) on a Raspberry Pi 5: spidev0.0, DC GPIO25, RST GPIO17, BUSY GPIO24, PWR GPIO18. A refresh is proven only when someone watched the panel change.
