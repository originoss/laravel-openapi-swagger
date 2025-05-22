<?php

namespace LaravelOpenApi\Generators;

use LaravelOpenApi\Discovery\RouteDiscovery;
use LaravelOpenApi\Discovery\ModelDiscovery;
use LaravelOpenApi\Schema\SchemaBuilder;
use Illuminate\Support\Collection;
use LaravelOpenApi\Attributes\Operation as OperationAttribute; // Add this import

class OpenApiGenerator
{
    public function __construct(
        private RouteDiscovery $routeDiscovery,
        private ModelDiscovery $modelDiscovery,
        private SchemaBuilder $schemaBuilder,
        private array $config
    ) {}

    public function generate(): array
    {
        $routes = $this->routeDiscovery->discover();
        $models = $this->modelDiscovery->discoverModels(); // Assuming discoverModels() is the correct method name

        return [
            'openapi' => '3.0.3', // As per spec
            'info' => $this->buildInfo(),
            'servers' => $this->buildServers(),
            'paths' => $this->buildPaths($routes),
            'components' => [
                'schemas' => $this->buildSchemas($models),
                'securitySchemes' => $this->buildSecuritySchemes(),
                'parameters' => $this->buildReusableParameters(), // Placeholder
                'responses' => $this->buildReusableResponses(),   // Placeholder
            ],
            'security' => $this->buildGlobalSecurity(), // Placeholder
            'tags' => $this->buildTags($routes),       // Placeholder
        ];
    }

    // --- Placeholder private methods ---
    // These will be implemented in detail in later steps.

    private function buildInfo(): array
    {
        return $this->config['info'] ?? [
            'title' => 'Laravel API', 
            'version' => '1.0.0',
        ];
    }

    private function buildServers(): array
    {
        // Use $this->config['servers'] if set and not empty, otherwise provide the default.
        // The check for !empty is important if an empty array in config means "use default"
        // If an empty array in config means "no servers", then a simple ?? would be different.
        // Given the config structure, `servers` is an array of server objects.
        
        if (isset($this->config['servers']) && is_array($this->config['servers']) && !empty($this->config['servers'])) {
            return $this->config['servers'];
        }

        // Default server entry
        $defaultUrl = 'http://localhost'; // Sensible static fallback
        try {
            if (function_exists('url')) {
                $generatedUrl = url('/');
                // Ensure url() returns a valid string and not, for example, null or an empty string.
                if (is_string($generatedUrl) && trim($generatedUrl) !== '') {
                    $defaultUrl = $generatedUrl;
                }
            }
        } catch (\Throwable $e) {
            // If url() helper fails (e.g. outside Laravel context or during early boot), stick to static fallback.
            // Optionally log this event: error_log('url() helper failed: ' . $e->getMessage());
        }
        
        return [
            ['url' => $defaultUrl, 'description' => 'Default Server']
        ];
    }

    private function buildPaths(Collection $routes): array
    {
        $paths = [];
        if ($routes->isEmpty()) {
            return $paths;
        }

        foreach ($routes as $routeInfo) {
            // $routeInfo is an instance of LaravelOpenApi\Discovery\RouteInfo
            $uri = '/' . ltrim($routeInfo->uri, '/'); // Ensure leading slash
            $method = strtolower($routeInfo->method);

            $operationObject = [
                'parameters' => [], // Placeholder
                'requestBody' => null, // Placeholder (OpenAPI spec requires requestBody to be an object or null)
                'responses' => ['default' => ['description' => 'Default response. To be updated.']], // Basic placeholder
                // Other fields like security can be added later
            ];

            // Find the Operation attribute
            /** @var OperationAttribute|null $operationAttribute */
            $operationAttribute = null;
            if (isset($routeInfo->attributes) && is_array($routeInfo->attributes)) {
                foreach ($routeInfo->attributes as $attribute) {
                    if ($attribute instanceof OperationAttribute) {
                        $operationAttribute = $attribute;
                        break;
                    }
                }
            }

            if ($operationAttribute) {
                if ($operationAttribute->summary !== null) $operationObject['summary'] = $operationAttribute->summary;
                if ($operationAttribute->description !== null) $operationObject['description'] = $operationAttribute->description;
                if ($operationAttribute->operationId !== null) $operationObject['operationId'] = $operationAttribute->operationId;
                if (!empty($operationAttribute->tags)) $operationObject['tags'] = $operationAttribute->tags;
                if ($operationAttribute->deprecated) $operationObject['deprecated'] = true; // Only set if true
                
                // If security is defined in Operation attribute, it overrides global/tag security
                // Note: OpenAPI spec expects security to be an array of Security Requirement Objects.
                // The OperationAttribute->security is expected to be in this format.
                if (!empty($operationAttribute->security)) {
                    $operationObject['security'] = $operationAttribute->security;
                }
            } else {
                // Default summary or operationId if no Operation attribute is present
                $operationObject['summary'] = 'Endpoint for ' . $uri;
                // Generate a basic operationId if none provided, e.g., from method and URI
                // Remove characters that are not typically allowed or are problematic in operationIds
                $safeUri = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(['/', '{', '}'], ['_', '', ''], $uri));
                $operationObject['operationId'] = $method . $safeUri;

            }
            
            // Ensure the path itself exists in the $paths array
            if (!isset($paths[$uri])) {
                $paths[$uri] = [];
            }
            $paths[$uri][$method] = $operationObject;
        }

        return $paths;
    }

    private function buildSchemas(Collection $models): array
    {
        $schemas = [];

        if ($models->isEmpty()) {
            return $schemas;
        }

        foreach ($models as $model) {
            // Assuming $model is an instance of LaravelOpenApi\Discovery\ModelSchema
            $modelSchemaArray = $this->schemaBuilder->buildModelSchema($model);
            
            // Use class_basename for the schema name/key
            // It's a common Laravel helper, so it should be available.
            // If not, a manual string manipulation would be needed:
            // $parts = explode('\\', $model->class);
            // $schemaName = end($parts);
            $schemaName = class_basename($model->class);
            
            $schemas[$schemaName] = $modelSchemaArray;
        }

        return $schemas;
    }

    private function buildSecuritySchemes(): array
    {
        // To be implemented using $this->config['security_schemes']
        return []; // Placeholder
    }

    private function buildReusableParameters(): array
    {
        // Optional: For defining common parameters
        return []; // Placeholder
    }

    private function buildReusableResponses(): array
    {
        // Optional: For defining common responses
        return []; // Placeholder
    }

    private function buildGlobalSecurity(): array
    {
        // To be implemented using $this->config['security'] (if a global security is defined)
        return []; // Placeholder
    }

    private function buildTags(Collection $routes): array
    {
        // To be implemented: Extract tags from routes/operations
        return []; // Placeholder
    }
}
