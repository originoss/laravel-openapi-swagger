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
        $this->app->singleton(AttributeParser::class);

        // Register Schema Builder
        $this->app->singleton(SchemaBuilder::class); 

        // Register Core Generator
        $this->app->singleton(OpenApiGenerator::class); 
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
            ]);
        }
        
        // Load views from the package
        $this->loadViewsFrom($viewBasePath, 'openapi'); 

        // Register middleware for runtime spec serving
        if ($this->app['config']->get('openapi.serve_spec', true)) { 
            Route::get(
                $this->app['config']->get('openapi.paths.json_route_path', '/openapi.json'),
                [SpecController::class, 'json']
            )->name($this->app['config']->get('openapi.paths.json_route_name', 'openapi.json'));

            Route::get(
                $this->app['config']->get('openapi.paths.yaml_route_path', '/openapi.yaml'),
                [SpecController::class, 'yaml']
            )->name($this->app['config']->get('openapi.paths.yaml_route_name', 'openapi.yaml'));
        }

        // New route for Swagger UI
        if ($this->app['config']->get('openapi.ui.enabled', true)) {
            Route::get(
                $this->app['config']->get('openapi.ui.route', '/api-docs'),
                [SpecController::class, 'ui']
            )->name($this->app['config']->get('openapi.ui.route_name', 'openapi.ui'));
        }
    }
}
