<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680;

use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Adapters\SSD1680DataCarrier;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Concerns\SSD1680API;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects\SSD1680BorderWaveform;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects\SSD1680DisplayUpdateControl1;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects\SSD1680DriverOutputControl;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680DataEntryMode;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680TemperatureSensor;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Exceptions\SSD1680Exception;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Factory\SSD1680Factory;
use Exception;
use RealityInterface\Displays\Applied\ePaper\Enums\EInkColor;
use RealityInterface\Displays\Attributes\OutputsOnlyBlackAndWhite;
use RealityInterface\Displays\Attributes\OutputsThreeColors;
use RealityInterface\Displays\Contracts\Applied\ePaper\BlackAndWhiteEInkDisplay;
use RealityInterface\Displays\Contracts\Applied\ePaper\TriColorEInkDisplay;
use RealityInterface\Displays\EmbeddedDisplay;
use ScrapyardIO\NutsAndBolts\DataObjects\ChannelPalette;
use ScrapyardIO\NutsAndBolts\DataObjects\ChannelSpec;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\ScanDirection;
use Waveforms\Carriers\GPIO\GPIO;
use Waveforms\Carriers\SPI\SPI;

#[OutputsThreeColors]
#[OutputsOnlyBlackAndWhite]
class SSD1680 extends EmbeddedDisplay implements BlackAndWhiteEInkDisplay, TriColorEInkDisplay
{
    use SSD1680API;

    protected bool $booted = false;

    /**
     * @throws Exception
     */
    public function __construct(
        protected readonly SSD1680DataCarrier $carrier,
        int $width,
        int $height,
        SSD1680DataEntryMode $data_entry_mode,
        SSD1680BorderWaveform $border_waveform,
        SSD1680DisplayUpdateControl1 $display_update_control,
        SSD1680TemperatureSensor $temperature_sensor,
        int $gate_setting,
    ) {
        parent::__construct($width, $height);
        $this->boot(
            $data_entry_mode,
            $border_waveform,
            $display_update_control,
            $temperature_sensor,
            $gate_setting,
        );
    }

    /**
     * @throws Exception
     */
    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            'data_entry_mode' => $this->setDataEntryMode($value),
            'border_waveform' => $this->setBorderWaveform($value),
            'display_update_control' => $this->setDisplayUpdateControl1($value),
            'temperature_sensor' => $this->setTemperatureSensor($value),
            'deep_sleep' => $this->deepSleep($value),
            default => throw SSD1680Exception::invalidProperty($name)
        };
    }

    /**
     * Run the SSD1680 power-on sequence.
     *
     * Hardware reset, software reset, then the documented framing registers:
     * gate driver geometry, RAM addressing window/counters, border waveform,
     * update options and the temperature sensor source. The panel raises BUSY
     * after the software reset and after the temperature read, so we pace the
     * sequence on the BUSY line. Writing pixel RAM and triggering a refresh is
     * left to the (forthcoming) data path.
     *
     * @throws Exception
     */
    protected function boot(
        SSD1680DataEntryMode $data_entry_mode,
        SSD1680BorderWaveform $border_waveform,
        SSD1680DisplayUpdateControl1 $display_update_control,
        SSD1680TemperatureSensor $temperature_sensor,
        int $gate_setting,
    ): void {
        if (! $this->booted) {
            $this->carrier->reset();
            $this->waitUntilIdle();

            $this->softwareReset();

            $this->setDriverOutputControl(
                SSD1680DriverOutputControl::forHeight($this->height, $gate_setting)
            );

            $this->setDataEntryMode($data_entry_mode);

            $this->setRAMXAddressRange(0x00, ($this->width - 1) >> 3);
            $this->setRAMYAddressRange(0x00, $this->height - 1);

            $this->setBorderWaveform($border_waveform);
            $this->setDisplayUpdateControl1($display_update_control);
            $this->setTemperatureSensor($temperature_sensor);

            $this->setRAMXAddressCounter(0x00);
            $this->setRAMYAddressCounter(0x00);

            $this->waitUntilIdle();

            $this->booted = true;
        }
    }

    /**
     * The SSD1680 keeps two 1bpp planes packed 8 horizontal pixels per byte
     * (MSB = leftmost): the black/white RAM (0x24) and the red RAM (0x26). The
     * same silicon backs both the BW and BWR panels, so we always describe both
     * planes; on a plain BW panel the red plane simply has no ink to show.
     *
     * Black RAM reads 1 as white and 0 as black, so the black channel is
     * inverted. Red RAM reads 1 as red, so the red channel is straight. If red
     * comes out reversed on your panel, flip the red ChannelSpec's inverted flag.
     */
    public function generateFormatSpec(): FormatSpec
    {
        return new FormatSpec(
            PixelFormat::MONO_HORIZONTAL,
            BitDepth::B1,
            ScanDirection::TOP_TO_BOTTOM,
            bit_order: BitOrder::MSB_FIRST,
            palette: new ChannelPalette(
                new ChannelSpec(EInkColor::BLACK->value, inverted: true),
                new ChannelSpec(EInkColor::RED->value),
            ),
        );
    }

    public function display(DumpedBuffer $buffer): void
    {
        $this->setRAMXAddressCounter(0x00);
        $this->setRAMYAddressCounter(0x00);
        $this->writeBlackRAM($this->planeBytes($buffer, EInkColor::BLACK->value, inverted: true));

        $this->setRAMXAddressCounter(0x00);
        $this->setRAMYAddressCounter(0x00);
        $this->writeRedRAM($this->planeBytes($buffer, EInkColor::RED->value, inverted: false));

        $this->refresh();
    }

    /**
     * The dump's bytes for a channel, or a full background-filled plane when the
     * dump omits it.
     *
     * An unwritten SSD1680 RAM powers up with random data, so every full refresh
     * must lay down both planes or the missing one renders as speckle. The
     * background byte follows the channel's bit polarity: an inverted plane
     * (black, 1 = white) clears to 0xFF, a straight plane (red, 1 = red) to 0x00.
     *
     * @return array<int, int>
     */
    private function planeBytes(DumpedBuffer $buffer, int $color, bool $inverted): array
    {
        $bytes = $buffer->raw_data[$color] ?? null;
        if (! is_null($bytes)) {
            return $bytes;
        }

        $stride = intdiv($this->width() + 7, 8);

        return array_fill(0, $stride * $this->height(), $inverted ? 0xFF : 0x00);
    }

    /**
     * @throws Exception
     */
    public static function connection(string $driver): SSD1680Factory
    {
        return new SSD1680Factory(
            SPI::connection($driver),
            GPIO::connection($driver)
        );
    }
}
