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
            'description' => 'Laravel Sanctum uses Bearer tokens to authenticate users',
        ],
        'passport' => [
            'type' => 'oauth2',
            'flows' => [
                'authorizationCode' => [
                    'authorizationUrl' => '/oauth/authorize',
                    'tokenUrl' => '/oauth/token',
                    'scopes' => [
                        'create' => 'Create resources',
                        'read' => 'Read resources',
                        'update' => 'Update resources',
                        'delete' => 'Delete resources',
                    ],
                ],
                'password' => [
                    'tokenUrl' => '/oauth/token',
                    'scopes' => [],
                ],
            ],
            'description' => 'Laravel Passport OAuth2 authentication',
        ],
        'apiKey' => [
            'type' => 'apiKey',
            'name' => 'X-API-KEY',
            'in' => 'header',
            'description' => 'API key authentication',
        ],
    ],
    
    // Global security requirements
    // Each array element represents an alternative security requirement (OR)
    // Keys within each element represent required schemes (AND)
    'security' => [
        // Option 1: Use sanctum authentication
        ['sanctum' => []],
        // Option 2: Use passport with specific scopes
        ['passport' => ['read', 'create']],
    ],
    
    // Reusable parameters that can be referenced in operations
    'parameters' => [
        'page' => [
            'name' => 'page',
            'in' => 'query',
            'description' => 'Page number for pagination',
            'required' => false,
            'schema' => [
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
            ],
        ],
        'per_page' => [
            'name' => 'per_page',
            'in' => 'query',
            'description' => 'Number of items per page',
            'required' => false,
            'schema' => [
                'type' => 'integer',
                'default' => 15,
                'minimum' => 1,
                'maximum' => 100,
            ],
        ],
        'sort' => [
            'name' => 'sort',
            'in' => 'query',
            'description' => 'Field to sort by, prefix with - for descending order',
            'required' => false,
            'schema' => [
                'type' => 'string',
                'example' => '-created_at',
            ],
        ],
    ],
    
    // Reusable responses that can be referenced in operations
    'responses' => [
        'Unauthorized' => [
            'description' => 'Authentication credentials were missing or invalid',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => [
                                'type' => 'string',
                                'example' => 'Unauthenticated.',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'Forbidden' => [
            'description' => 'The user does not have the necessary permissions',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => [
                                'type' => 'string',
                                'example' => 'This action is unauthorized.',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'NotFound' => [
            'description' => 'The specified resource was not found',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => [
                                'type' => 'string',
                                'example' => 'Resource not found.',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'ValidationError' => [
            'description' => 'The request was invalid',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => [
                                'type' => 'string',
                                'example' => 'The given data was invalid.',
                            ],
                            'errors' => [
                                'type' => 'object',
                                'example' => [
                                    'field_name' => [
                                        'The field name is required.',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'serve_spec' => env('OPENAPI_SERVE_SPEC', true),

    'paths' => [
        'json_route_path' => env('OPENAPI_JSON_ROUTE_PATH', '/openapi.json'),
        'json_route_name' => env('OPENAPI_JSON_ROUTE_NAME', 'openapi.json'),

        'yaml_route_path' => env('OPENAPI_YAML_ROUTE_PATH', '/openapi.yaml'),
        'yaml_route_name' => env('OPENAPI_YAML_ROUTE_NAME', 'openapi.yaml'),
        
        'output_directory' => env('OPENAPI_OUTPUT_DIRECTORY', ''),
        'output_filename' => env('OPENAPI_OUTPUT_FILENAME', 'openapi'),
    ],

    'ui' => [
        'enabled' => env('OPENAPI_UI_ENABLED', true),
        'route' => env('OPENAPI_UI_ROUTE', '/api-docs'),
        'route_name' => env('OPENAPI_UI_ROUTE_NAME', 'openapi.ui'),

        'title' => env('OPENAPI_UI_TITLE', 'OpenAPI Documentation UI'),
        
        'default_format' => env('OPENAPI_UI_DEFAULT_FORMAT', 'json'),
        
        'spec_route_name_json' => env('OPENAPI_UI_SPEC_ROUTE_NAME_JSON', 'openapi.json'),
        
        'config' => [
            'docExpansion' => env('OPENAPI_UI_DOC_EXPANSION', 'list'),
            'deepLinking' => true,
            'persistAuthorization' => true,
        ],
    ],
];
