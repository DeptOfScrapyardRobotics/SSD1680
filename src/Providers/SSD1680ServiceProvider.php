<?php

namespace DeptOfScrapyardRobotics\Displays\SSD1680\Providers;

use DeptOfScrapyardRobotics\Displays\SSD1680\Enums\SSD1680CatalogIc;
use Voyager\NutsAndBolts\ServiceProvider;

/**
 * Each panel kind's wiring config lives under the circuits tree: config('circuits.ssd1680-bw'), published to
 * config/circuits/ssd1680-bw.php, which the config loader keys the same way.
 */
class SSD1680ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (SSD1680CatalogIc::cases() as $ic) {
            $this->mergeConfigFrom(dirname(__DIR__, 2)."/config/{$ic->value}.php", "circuits.{$ic->value}");
        }
    }

    public function boot(): void
    {
        $paths = [];

        foreach (SSD1680CatalogIc::cases() as $ic) {
            $paths[dirname(__DIR__, 2)."/config/{$ic->value}.php"] = $this->app->configPath("circuits/{$ic->value}.php");
        }

        $this->publishes($paths, 'ssd1680-config');

        // With the GPIO catalog installed, each panel kind is conjurable by slug: app('circuit')->conjure('ssd1680-bw').
        if ($this->app->isBound('circuit')) {
            foreach (SSD1680CatalogIc::cases() as $ic) {
                $this->app->make('circuit')->addCircuit($ic->value, $ic->panelClass());
            }
        }
    }
}
