<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680;

use DeptOfScrapyardRobotics\Displays\SSD1680\Concerns\ConjuresSSD1680;
use DeptOfScrapyardRobotics\Displays\SSD1680\Concerns\SSD1680Bootstrap;
use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680OpCode;
use DeptOfScrapyardRobotics\Displays\SSD1680\Transports\SSD1680DataTransport;
use GeneralPurposeIO\Contracts\IntegratedCircuits\DisplayPanel;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshesOnCommand;
use GeneralPurposeIO\Contracts\IntegratedCircuits\WindowAddressable;
use GeneralPurposeIO\IntegratedCircuits\Bootable;
use Surface\Contracts\Framebuffers\FormatSpec;

/**
 * An ePaper panel on an SSD1680. transmit() writes RAM; refresh() shows it. Each ink is a one-bit plane packed
 * eight pixels to a byte, leftmost pixel in bit 7, rows padded to a byte. SSD1680BW drives a black/white panel,
 * SSD1680BWR a black/white/red one.
 */
abstract class SSD1680 extends Bootable implements DisplayPanel, WindowAddressable, RefreshesOnCommand
{
    use ConjuresSSD1680;
    use SSD1680Bootstrap;

    protected FormatSpec $format_spec;

    public function __construct(
        protected readonly SSD1680DataTransport $transport,
        protected SSD1680Configuration $props = new SSD1680Configuration,
        bool $boot_now = false,
    ) {
        $this->format_spec = $this->generateFormatSpec();

        parent::__construct($boot_now);
    }

    /** The RAM each plane goes to, in the order transmit()'s bytes carry the planes. @return list<SSD1680OpCode> */
    abstract protected function ramPlanes(): array;

    abstract public function generateFormatSpec(): FormatSpec;

    public function width(): int
    {
        return $this->props->get('width');
    }

    public function height(): int
    {
        return $this->props->get('height');
    }

    public function transport(): SSD1680DataTransport
    {
        return $this->transport;
    }

    public function config(): SSD1680Configuration
    {
        return $this->props;
    }

    /** How transmit() wants its bytes packed. */
    public function formatSpec(): FormatSpec
    {
        return $this->format_spec;
    }

    public function setFormatSpec(FormatSpec $format_spec): void
    {
        $this->format_spec = $format_spec;
    }

    /**
     * Write planes packed per formatSpec() into the window at (x, y), planes back to back in ramPlanes() order.
     * RAM columns are bytes, so x is a multiple of 8 and the width a multiple of 8 unless the window ends at the
     * panel's right edge. Null width or height means the whole panel. The panel shows it on refresh().
     *
     * @param  list<int>  $raw_data
     *
     * @throws SSD1680Exception
     */
    public function transmit(int $origin_x, int $origin_y, array $raw_data, ?int $frame_width = null, ?int $frame_height = null): void
    {
        $width = $frame_width ?? $this->width();
        $height = $frame_height ?? $this->height();

        $this->guardWindow($origin_x, $origin_y, $width, $height);

        $planes = $this->ramPlanes();
        $plane_bytes = intdiv($width + 7, 8) * $height;
        $bytes = array_values($raw_data);

        if (count($bytes) !== $plane_bytes * count($planes)) {
            throw SSD1680Exception::wrongByteCount($plane_bytes * count($planes), count($bytes));
        }

        $this->setRAMWindow($origin_x, $origin_y, $width, $height);

        foreach ($planes as $index => $ram) {
            $this->setRAMCursor($origin_x, $origin_y);
            $this->sendCommand($ram, array_slice($bytes, $index * $plane_bytes, $plane_bytes));
        }

        $this->written($origin_x, $origin_y, $width, $height, $bytes);
    }

    /** After a window reached RAM: what a variant keeps of it. @param  list<int>  $bytes */
    protected function written(int $x, int $y, int $width, int $height, array $bytes): void {}

    /** Release DC, RST, BUSY and PWR. The bus belongs to its driver and stays open; the panel keeps its image. */
    public function close(): void
    {
        $this->transport->close();
    }

    protected function guardWindow(int $x, int $y, int $width, int $height): void
    {
        if ($x < 0 || $y < 0 || $width < 1 || $height < 1 || $x + $width > $this->width() || $y + $height > $this->height()) {
            throw SSD1680Exception::windowOutsidePanel($x, $y, $width, $height, $this->width(), $this->height());
        }

        if ($x % 8 !== 0 || ($width % 8 !== 0 && $x + $width !== $this->width())) {
            throw SSD1680Exception::unalignedWindow($x, $width, $this->width());
        }
    }
}
