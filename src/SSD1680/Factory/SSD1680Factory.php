<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Factory;

use BareMetal\CircuitFactory;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Adapters\SSD1680SPIAdapter;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects\SSD1680BorderWaveform;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\DataObjects\SSD1680DisplayUpdateControl1;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680DataEntryMode;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\Enums\SSD1680TemperatureSensor;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680\SSD1680;
use Exception;
use Waveforms\Carriers\GPIO\Factory\GPIOConnectionBuilder;
use Waveforms\Carriers\GPIO\GPIOPin;
use Waveforms\Carriers\SPI\Enums\SPIMode;
use Waveforms\Carriers\SPI\Factory\SPIConnectionBuilder;

class SSD1680Factory extends CircuitFactory
{
    protected bool $has_dc = false;

    protected bool $has_rst = false;

    protected bool $has_busy = false;

    protected int $width = 122;

    protected int $height = 250;

    protected int $max_packet_size = 1024;

    public string $consumer = 'ssd1680';

    public SSD1680DataEntryMode $data_entry_mode = SSD1680DataEntryMode::Y_INCREMENT_X_INCREMENT;

    public SSD1680BorderWaveform $border_waveform;

    public SSD1680DisplayUpdateControl1 $display_update_control;

    public SSD1680TemperatureSensor $temperature_sensor = SSD1680TemperatureSensor::INTERNAL;

    public int $gate_setting = 0x00;

    public ?SPIConnectionBuilder $connection = null;

    public function __construct(
        public SPIConnectionBuilder $spi_connection,
        public GPIOConnectionBuilder $gpio_connection
    ) {
        $this->border_waveform = new SSD1680BorderWaveform;
        $this->display_update_control = new SSD1680DisplayUpdateControl1;
    }

    public function spi(string|int $master, int $chip_select): static
    {
        $this->connection = $this->spi_connection->firstly($master)
            ->chip($chip_select)
            ->speed(25000000)
            ->mode(SPIMode::MODE_0);

        return $this;
    }

    public function gpiochip(int|string $chip): static
    {
        $this->gpio_connection = $this->gpio_connection->firstly($chip);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function dc(int $pin): static
    {
        if (! $this->has_dc) {
            $gpio_output = GPIOPin::createOutput($this->connection->connection(), $pin, 'dc');
            $this->gpio_connection = $this->gpio_connection->addOutput($gpio_output);
            $this->has_dc = true;
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function rst(int $pin): static
    {
        if (! $this->has_rst) {
            $gpio_output = GPIOPin::createOutput($this->connection->connection(), $pin, 'rst');
            $this->gpio_connection = $this->gpio_connection->addOutput($gpio_output);
            $this->has_rst = true;
        }

        return $this;
    }

    public function busy(int $pin, bool $nonblocking = false): static
    {
        if (! $this->has_busy) {
            $gpio_input = GPIOPin::createInput($this->connection->connection(), $pin, 'busy')
                ->edgeEvents();

            if ($nonblocking) {
                $gpio_input = $gpio_input->nonblocking();
            }

            $this->gpio_connection = $this->gpio_connection->addInput($gpio_input);
            $this->has_busy = true;
        }

        return $this;
    }

    public function consumer(string $consumer): static
    {
        $this->consumer = $consumer;

        return $this;
    }

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function maxPacketSize(int $max_packet_size): static
    {
        $this->max_packet_size = $max_packet_size;

        return $this;
    }

    public function dataEntryMode(SSD1680DataEntryMode $mode): static
    {
        $this->data_entry_mode = $mode;

        return $this;
    }

    public function borderWaveform(SSD1680BorderWaveform $border): static
    {
        $this->border_waveform = $border;

        return $this;
    }

    public function displayUpdateControl(SSD1680DisplayUpdateControl1 $control): static
    {
        $this->display_update_control = $control;

        return $this;
    }

    public function temperatureSensor(SSD1680TemperatureSensor $sensor): static
    {
        $this->temperature_sensor = $sensor;

        return $this;
    }

    public function gateSetting(int $gate_setting): static
    {
        $this->gate_setting = $gate_setting;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function create(): SSD1680
    {
        $carrier = $this->connection?->boot();
        if (is_null($carrier)) {
            throw new Exception('A connection was not registered.');
        }

        $gpio = $this->gpio_connection
            ->shareConnectionWith($carrier)
            ->consumer($this->consumer)
            ->boot();

        $carrier = new SSD1680SPIAdapter($carrier, $gpio, $this->max_packet_size, $this->has_busy);

        return new SSD1680(
            $carrier,
            $this->width,
            $this->height,
            $this->data_entry_mode,
            $this->border_waveform,
            $this->display_update_control,
            $this->temperature_sensor,
            $this->gate_setting,
        );
    }
}
