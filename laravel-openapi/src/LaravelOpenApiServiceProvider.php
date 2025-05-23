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
        // Define the base path for views for clarity relative to this ServiceProvider in src/
        // The view file is at src/resources/views/swagger-ui.blade.php
        $viewBasePath = __DIR__.'/resources/views'; // Corrected path

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/openapi.php' => config_path('openapi.php'),
            ], 'openapi-config');

            // Add this block for publishing views
            $this->publishes([
                $viewBasePath . '/swagger-ui.blade.php' => resource_path('views/vendor/openapi/swagger-ui.blade.php'),
            ], 'openapi-views');

            $this->commands([
                GenerateCommand::class,
                // Commands\CacheCommand::class, // Keep commented
                // Commands\ClearCacheCommand::class, // Keep commented
            ]);
        }
        
        // Load views from the package
        $this->loadViewsFrom($viewBasePath, 'openapi'); // Namespace views with 'openapi::'

        // Register middleware for runtime spec serving
        // Note: Changed default for 'openapi.serve_spec' to true as per subtask snippet for UI
        if ($this->app['config']->get('openapi.serve_spec', true)) { 
            Route::get(
                $this->app['config']->get('openapi.paths.json_route_path', '/openapi.json'),
                [SpecController::class, 'json']
            )->name($this->app['config']->get('openapi.paths.json_route_name', 'openapi.json')); // Use configured route name

            Route::get(
                $this->app['config']->get('openapi.paths.yaml_route_path', '/openapi.yaml'),
                [SpecController::class, 'yaml']
            )->name($this->app['config']->get('openapi.paths.yaml_route_name', 'openapi.yaml')); // Use configured route name
        }

        // New route for Swagger UI
        if ($this->app['config']->get('openapi.ui.enabled', true)) {
            Route::get(
                $this->app['config']->get('openapi.ui.route', '/api-docs'), // Use configured UI route path
                [SpecController::class, 'ui']
            )->name($this->app['config']->get('openapi.ui.route_name', 'openapi.ui')); // Optional: name the UI route
        }
    }
}
