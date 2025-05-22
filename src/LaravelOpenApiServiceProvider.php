<?php

namespace LaravelOpenApi;

use Illuminate\Support\ServiceProvider;
use LaravelOpenApi\Discovery\RouteDiscovery;
use LaravelOpenApi\Discovery\ModelDiscovery;
use LaravelOpenApi\Parsers\AttributeParser;
use LaravelOpenApi\Schema\SchemaBuilder;
use LaravelOpenApi\Generators\OpenApiGenerator;
use LaravelOpenApi\Commands\GenerateCommand; // Add this import
// Import necessary classes for Commands and Routes later
use Illuminate\Support\Facades\Route; 
use LaravelOpenApi\Http\Controllers\SpecController;

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
        $this->app->singleton(SchemaBuilder::class); 

        // Register Core Generator
        $this->app->singleton(OpenApiGenerator::class); // Uncomment/Add this line
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/openapi.php' => config_path('openapi.php'),
            ], 'openapi-config');

            // Command registration will be added in a future step
            $this->commands([
                GenerateCommand::class,
                // Commands\CacheCommand::class, // Keep commented
                // Commands\ClearCacheCommand::class, // Keep commented
            ]);
        }

        // Register middleware for runtime spec serving
        // Ensure 'openapi.serve_spec' key exists in config/openapi.php (add it if missing, default to false or true)
        if ($this->app['config']->get('openapi.serve_spec', false)) { // Use $this->app['config']->get for reliability
            Route::get(
                $this->app['config']->get('openapi.paths.json_route_path', '/openapi.json'), // Configurable path
                [SpecController::class, 'json']
            )->name('openapi.json'); // Optional: name the route

            Route::get(
                $this->app['config']->get('openapi.paths.yaml_route_path', '/openapi.yaml'), // Configurable path
                [SpecController::class, 'yaml']
            )->name('openapi.yaml'); // Optional: name the route
        }
    }
}
