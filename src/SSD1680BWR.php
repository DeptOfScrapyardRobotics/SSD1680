<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680;

use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680OpCode;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680UpdateSequence;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshMode;
use Surface\Contracts\Framebuffers\BitDepth;
use Surface\Contracts\Framebuffers\BitOrder;
use Surface\Contracts\Framebuffers\ChannelPalette;
use Surface\Contracts\Framebuffers\ChannelSpec;
use Surface\Contracts\Framebuffers\EInkColor;
use Surface\Contracts\Framebuffers\FormatSpec;
use Surface\Contracts\Framebuffers\PixelFormat;
use Surface\Contracts\Framebuffers\ScanDirection;

/**
 * A black/white/red SSD1680 panel: two planes, black to the black RAM (0x24, 1 = white, 0 = black) and red to the
 * red RAM (0x26, 1 = red). transmit() takes the black plane then the red plane, back to back. Tri-colour panels
 * have no partial waveform.
 */
class SSD1680BWR extends SSD1680
{
    protected function ramPlanes(): array
    {
        return [SSD1680OpCode::WRITE_BLACK_RAM, SSD1680OpCode::WRITE_RED_RAM];
    }

    public function generateFormatSpec(): FormatSpec
    {
        return new FormatSpec(
            PixelFormat::PLANAR,
            BitDepth::B1,
            ScanDirection::TOP_TO_BOTTOM,
            BitOrder::MSB_FIRST,
            palette: new ChannelPalette(
                new ChannelSpec(EInkColor::BLACK->value, inverted: true),
                new ChannelSpec(EInkColor::RED->value),
            ),
        );
    }

    /** @throws SSD1680Exception for PARTIAL */
    public function refresh(RefreshMode $mode = RefreshMode::FULL): void
    {
        if ($mode !== RefreshMode::FULL) {
            throw SSD1680Exception::unsupportedRefreshMode($mode, static::class);
        }

        $this->sendCommand(SSD1680OpCode::BORDER_WAVEFORM_CONTROL, [$this->config()->get('border_waveform')->toByte()]);
        $this->runUpdate(SSD1680UpdateSequence::FULL);
    }
}
