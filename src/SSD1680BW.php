<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680;

use DeptOfScrapyardRobotics\Displays\SSD1680\Breakouts\SSD1680BorderWaveform;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680OpCode;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680UpdateSequence;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshMode;
use Surface\Contracts\Framebuffers\BitDepth;
use Surface\Contracts\Framebuffers\BitOrder;
use Surface\Contracts\Framebuffers\FormatSpec;
use Surface\Contracts\Framebuffers\PixelFormat;
use Surface\Contracts\Framebuffers\ScanDirection;

/**
 * A black/white SSD1680 panel: one plane in the black RAM (0x24), 1 = white, 0 = black. A partial refresh drives
 * only the pixels that differ from the red RAM (0x26), so after every refresh the panel copies each window it was
 * sent into the red RAM: the red RAM then holds what the panel shows.
 */
class SSD1680BW extends SSD1680
{
    /** @var list<array{0: int, 1: int, 2: int, 3: int, 4: list<int>}> windows sent since the last refresh */
    protected array $pending = [];

    /** A full refresh has shown a frame since boot. */
    protected bool $shown = false;

    /** A partial refresh ran since the last full one. */
    protected bool $partial = false;

    protected function ramPlanes(): array
    {
        return [SSD1680OpCode::WRITE_BLACK_RAM];
    }

    /** One bit per pixel, eight to a byte, leftmost in bit 7, white 1: the black RAM's own layout. */
    public function generateFormatSpec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::MONO_HORIZONTAL, BitDepth::B1, ScanDirection::TOP_TO_BOTTOM, BitOrder::MSB_FIRST);
    }

    /**
     * FULL runs display mode 1 with the configured border; after a partial refresh the controller ignores it until
     * re-initialised, so a FULL that follows a PARTIAL first runs the reset and register sequence again (RAM is kept),
     * as the panel vendor does. PARTIAL runs display mode 2 with the border held at VCOM, and needs a full refresh
     * since boot. Either way BUSY is waited out, then the windows sent since the last refresh
     * are copied into the red RAM.
     *
     * @throws SSD1680Exception
     */
    public function refresh(RefreshMode $mode = RefreshMode::FULL): void
    {
        if ($mode === RefreshMode::PARTIAL && ! $this->shown) {
            throw SSD1680Exception::partialBeforeFull();
        }

        $full = $mode === RefreshMode::FULL;

        if ($full && $this->partial) {
            $this->initialize();
        }

        $border = $full ? $this->config()->get('border_waveform') : SSD1680BorderWaveform::partial();

        $this->sendCommand(SSD1680OpCode::BORDER_WAVEFORM_CONTROL, [$border->toByte()]);
        $this->runUpdate($full ? SSD1680UpdateSequence::FULL : SSD1680UpdateSequence::PARTIAL);

        foreach ($this->pending as [$x, $y, $width, $height, $bytes]) {
            $this->setRAMWindow($x, $y, $width, $height);
            $this->setRAMCursor($x, $y);
            $this->sendCommand(SSD1680OpCode::WRITE_RED_RAM, $bytes);
        }

        $this->pending = [];
        $this->shown = $this->shown || $full;
        $this->partial = ! $full;
    }

    protected function written(int $x, int $y, int $width, int $height, array $bytes): void
    {
        $this->pending[] = [$x, $y, $width, $height, $bytes];
    }

    protected function afterBoot(): void
    {
        $this->pending = [];
        $this->shown = false;
        $this->partial = false;
    }
}
