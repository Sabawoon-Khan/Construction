<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS driver
    |--------------------------------------------------------------------------
    |
    | Lean starter foundation: fake/log only. Clone apps can add real gateways
    | (Android HTTP, Gammu, etc.) behind this same interface.
    |
    */

    'default' => env('SMS_DRIVER', 'fake'),

    'log_channel' => env('SMS_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    */

    'drivers' => [
        'fake' => [
            'driver' => 'fake',
        ],
    ],

];
