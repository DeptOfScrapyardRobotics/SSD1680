<?php

return [
    'default_config' => 'spi',
    'configs' => [
        'spi' => [
            'driver' => 'none',
            'device' => '',
            'chip_select' => 0,
            'mode' => 0,
            'speed' => 10_000_000,  // Hz, this chip select's clock, up to 20 MHz
            'width' => null,        // null keeps 122
            'height' => null,       // null keeps 250
            'dc' => [
                'driver' => 'none',
                'device' => '',
                'pin' => 0,
            ],
            'rst' => [
                'driver' => 'none',
                'device' => '',
                'pin' => 1,
            ],
            'busy' => [
                'driver' => 'none',
                'device' => '',
                'pin' => 2,
            ],
            'pwr' => [              // supply switch on some HATs; leave disabled when the panel is always powered
                'enabled' => false,
                'driver' => 'none',
                'device' => '',
                'pin' => 3,
            ],
        ],
    ],
];
