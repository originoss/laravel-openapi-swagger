<?php

namespace LaravelOpenApi;

use Illuminate\Support\ServiceProvider;
use LaravelOpenApi\Discovery\RouteDiscovery;
use LaravelOpenApi\Discovery\ModelDiscovery;
use LaravelOpenApi\Parsers\AttributeParser;
use LaravelOpenApi\Schema\SchemaBuilder; // Add this import
// Import necessary classes for Commands and Routes later
// use LaravelOpenApi\Commands; 
// use Illuminate\Support\Facades\Route; 
// use LaravelOpenApi\Http\Controllers\SpecController;

class LaravelOpenApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/openapi.php', 'openapi');

        // Register Discovery services
        $this->app->singleton(RouteDiscovery::class);
        $this->app->singleton(ModelDiscovery::class);
        
        // Register Parser services
        $this->app->singleton(AttributeParser::class); // AttributeParser is needed by RouteDiscovery

        // Register Schema Builder
        $this->app->singleton(SchemaBuilder::class); // Register SchemaBuilder

        // Placeholders for other services from the spec that will be registered later
        // $this->app->singleton(OpenApiGenerator::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/openapi.php' => config_path('openapi.php'),
            ], 'openapi-config');

            // Command registration will be added in a future step
            /*
            $this->commands([
                Commands\GenerateCommand::class,
                Commands\CacheCommand::class,
                Commands\ClearCacheCommand::class,
            ]);
            */
        }

        // Route registration for spec serving will be added in a future step
        /*
        if (config('openapi.serve_spec', false)) {
            Route::get('/openapi.json', [SpecController::class, 'json']);
            Route::get('/openapi.yaml', [SpecController::class, 'yaml']);
        }
        */
    }
}
