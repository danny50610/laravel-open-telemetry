<?php

return [
    'enable' => env('LARAVEL_OTEL_ENABLE', false),

    'otlp' => [
        'protocol' => env('LARAVEL_OTEL_EXPORTER_OTLP_PROTOCOL', 'application/json'),
        'endpoint' => env('LARAVEL_OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),
    ]
];
