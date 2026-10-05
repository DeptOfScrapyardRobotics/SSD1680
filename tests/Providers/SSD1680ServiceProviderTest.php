<?php

use DeptOfScrapyardRobotics\Displays\SSD1680\Providers\SSD1680ServiceProvider;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680BW;
use DeptOfScrapyardRobotics\Displays\SSD1680\SSD1680BWR;
use DeptOfScrapyardRobotics\Displays\SSD1680\Tests\Support\ConfigPathVessel;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;
use Voyager\Config\Repository;
use Voyager\NutsAndBolts\ServiceProvider;

it('registers each panel kind\'s wiring config under circuits, keeping anything the app already set', function (): void {
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository(['circuits' => [
        'adxl345' => ['default_config' => 'i2c'],
        'ssd1680-bw' => ['default_config' => 'hat'],
    ]]));

    (new SSD1680ServiceProvider($app))->register();
    $config = $app->make('config');

    expect($config->get('circuits.ssd1680-bw.default_config'))->toBe('hat')
        ->and($config->get('circuits.ssd1680-bwr.configs.spi.busy.pin'))->toBe(2)
        ->and($config->get('circuits.ssd1680-bwr.configs.spi.pwr.enabled'))->toBeFalse()
        ->and($config->get('circuits.adxl345'))->toBe(['default_config' => 'i2c']);
});

it('publishes both configs into config/circuits under the ssd1680-config tag', function (): void {
    $app = new ConfigPathVessel('/app/config');
    $app->registerInstance('config', new Repository);

    $provider = new SSD1680ServiceProvider($app);
    $provider->register();
    $provider->boot();
    $root = dirname(__DIR__, 2);

    expect(ServiceProvider::pathsToPublish(SSD1680ServiceProvider::class, 'ssd1680-config'))->toBe([
        "{$root}/config/ssd1680-bw.php" => '/app/config/circuits/ssd1680-bw.php',
        "{$root}/config/ssd1680-bwr.php" => '/app/config/circuits/ssd1680-bwr.php',
    ]);
});

it('adds both panel kinds to the circuit catalog when one is bound', function (): void {
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository);
    $app->registerInstance('circuit', $catalog = new CircuitRegistry);

    $provider = new SSD1680ServiceProvider($app);
    $provider->register();
    $provider->boot();

    expect($catalog->listCircuits())->toBe(['ssd1680-bw' => SSD1680BW::class, 'ssd1680-bwr' => SSD1680BWR::class]);
});
