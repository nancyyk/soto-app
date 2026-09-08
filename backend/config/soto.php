<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SOTO Business Configuration
    |--------------------------------------------------------------------------
    */

    // Points awarded per bottle deposited
    'points_per_bottle' => (int) env('SOTO_POINTS_PER_BOTTLE', 10),

    // Capacity threshold (%) that triggers TSP route recalculation
    'default_threshold' => (int) env('SOTO_DEFAULT_THRESHOLD', 80),

    // Internal API secret shared between Node-RED and Laravel
    'internal_api_secret' => env('INTERNAL_API_SECRET', ''),

    // MQTT broker configuration
    'mqtt' => [
        'host'     => env('MQTT_HOST', 'localhost'),
        'port'     => (int) env('MQTT_PORT', 1883),
        'username' => env('MQTT_USERNAME', ''),
        'password' => env('MQTT_PASSWORD', ''),
        'topic_prefix' => 'soto',
    ],

    // TSP solver: brute-force threshold
    'tsp_brute_force_max_nodes' => 10,
];
