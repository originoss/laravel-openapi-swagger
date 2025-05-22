<?php

return [
    'info' => [
        'title' => env('OPENAPI_TITLE', config('app.name') . ' API'),
        'version' => env('OPENAPI_VERSION', '1.0.0'),
        'description' => env('OPENAPI_DESCRIPTION'),
        'contact' => [
            'name' => env('OPENAPI_CONTACT_NAME'),
            'email' => env('OPENAPI_CONTACT_EMAIL'),
            'url' => env('OPENAPI_CONTACT_URL'),
        ],
    ],

    'servers' => [
        [
            'url' => env('APP_URL', 'http://localhost'),
            'description' => 'Development server'
        ],
    ],

    'discovery' => [
        'routes' => [
            'include_patterns' => ['api/*'],
            'exclude_patterns' => ['telescope/*', 'horizon/*'],
            'middleware_filters' => ['api'],
        ],
        'models' => [
            'directories' => ['app/Models'],
            'exclude_classes' => [],
        ],
    ],

    'generation' => [
        'cache_enabled' => env('OPENAPI_CACHE', true),
        'cache_ttl' => 3600, // 1 hour
        'auto_generate_examples' => true,
        'include_hidden_fields' => false,
    ],

    'security_schemes' => [
        'sanctum' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ],
        'passport' => [
            'type' => 'oauth2',
            'flows' => [
                'authorizationCode' => [
                    'authorizationUrl' => '/oauth/authorize',
                    'tokenUrl' => '/oauth/token',
                    'scopes' => [],
                ],
            ],
        ],
    ],

    // Placeholder for spec serving configuration, to be used by the Service Provider's boot method later
    // 'serve_spec' => env('OPENAPI_SERVE_SPEC', false), 
];
