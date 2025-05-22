<?php
return [
    'name' => env('APP_NAME', 'Laravel OpenAPI Demo'),
    'env' => env('APP_ENV', 'development'),
    'debug' => env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost'),
    'providers' => [
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Routing\RouteServiceProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class, 
        LaravelOpenApi\LaravelOpenApiServiceProvider::class,
    ],
    'aliases' => Illuminate\Support\Facades\Facade::defaultAliases()->toArray(),
];
