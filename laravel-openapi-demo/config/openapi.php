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
    'serve_spec' => env('OPENAPI_SERVE_SPEC', true), // Default to true to enable by default

    'paths' => [
        'json_route_path' => env('OPENAPI_JSON_ROUTE_PATH', '/openapi.json'),
        'json_route_name' => env('OPENAPI_JSON_ROUTE_NAME', 'openapi.json'), 

        'yaml_route_path' => env('OPENAPI_YAML_ROUTE_PATH', '/openapi.yaml'),
        'yaml_route_name' => env('OPENAPI_YAML_ROUTE_NAME', 'openapi.yaml'), 
        
        // Output path for the openapi:generate command.
        // This should align with what SpecController expects.
        // The 'output_directory' is relative to public_path().
        // The 'output_filename' is without extension.
        'output_directory' => env('OPENAPI_OUTPUT_DIRECTORY', ''), // e.g., 'api-docs' for public/api-docs/openapi.json
        'output_filename' => env('OPENAPI_OUTPUT_FILENAME', 'openapi'), // results in openapi.json or openapi.yaml
    ],

    'ui' => [
        'enabled' => env('OPENAPI_UI_ENABLED', true),
        'route' => env('OPENAPI_UI_ROUTE', '/api-docs'), // Path for the Swagger UI page
        'route_name' => env('OPENAPI_UI_ROUTE_NAME', 'openapi.ui'), // Route name for the UI page

        // Configuration for the Swagger UI view itself:
        'title' => env('OPENAPI_UI_TITLE', 'OpenAPI Documentation UI'), // Title for the HTML page
        
        // Specifies the route *name* for the JSON spec, used by the view to generate the URL.
        // This should match one of the names defined in 'paths'.
        'spec_route_name_json' => env('OPENAPI_UI_SPEC_ROUTE_NAME_JSON', 'openapi.json'), 
    ],
];
