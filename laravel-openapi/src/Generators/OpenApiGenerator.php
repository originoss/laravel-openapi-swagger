<?php

namespace LaravelOpenApi\Generators;

use LaravelOpenApi\Discovery\RouteDiscovery;
use LaravelOpenApi\Discovery\ModelDiscovery;
use LaravelOpenApi\Schema\SchemaBuilder;
use Illuminate\Support\Collection;
use LaravelOpenApi\Attributes\Operation as OperationAttribute;
use LaravelOpenApi\Utilities\SchemaResolver;

class OpenApiGenerator
{
    private array $config;

    public function __construct(
        private RouteDiscovery $routeDiscovery,
        private ModelDiscovery $modelDiscovery,
        private SchemaBuilder $schemaBuilder
    ) {
        $this->config = config('openapi') ?? [];
    }

    public function generate(): array
    {
        $routes = $this->routeDiscovery->discover();
        $models = $this->modelDiscovery->discoverModels();

        return [
            'openapi' => '3.0.3',
            'info' => $this->buildInfo(),
            'servers' => $this->buildServers(),
            'paths' => $this->buildPaths($routes),
            'components' => [
                'schemas' => $this->buildSchemas($models),
                'securitySchemes' => $this->buildSecuritySchemes(),
                'parameters' => $this->buildReusableParameters(),
                'responses' => $this->buildReusableResponses(),
            ],
            'security' => $this->buildGlobalSecurity(),
            'tags' => $this->buildTags($routes),
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
            error_log('url() helper failed: ' . $e->getMessage());
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
                'parameters' => [],
                'requestBody' => null,
                'responses' => ['default' => ['description' => 'Default response']],
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
            
            // Extract controller-level tags from ApiTag attributes
            $controllerTags = [];
            if (!empty($routeInfo->controllerAttributes)) {
                foreach ($routeInfo->controllerAttributes as $attribute) {
                    if ($attribute instanceof \LaravelOpenApi\Attributes\ApiTag) {
                        $controllerTags[] = $attribute->name;
                    }
                }
            }

            if ($operationAttribute) {
                // Always prioritize explicit attributes from the Operation annotation
                if ($operationAttribute->summary !== null) $operationObject['summary'] = $operationAttribute->summary;
                if ($operationAttribute->description !== null) $operationObject['description'] = $operationAttribute->description;
                
                // Always use operationId from the Operation attribute if provided
                if ($operationAttribute->operationId !== null) {
                    $operationObject['operationId'] = $operationAttribute->operationId;
                }
                
                // Merge controller tags with operation tags
                $operationTags = !empty($operationAttribute->tags) ? $operationAttribute->tags : [];
                $mergedTags = array_unique(array_merge($controllerTags, $operationTags));
                if (!empty($mergedTags)) $operationObject['tags'] = $mergedTags;
                
                if ($operationAttribute->deprecated) $operationObject['deprecated'] = true; // Only set if true
                
                if (!empty($operationAttribute->security)) {
                    $operationObject['security'] = $operationAttribute->security;
                }
                
                // Auto-discover any missing details, but don't override what's explicitly set in the annotation
                $this->autoDiscoverOperation($operationObject, $routeInfo, $uri, $method, $controllerTags);
            } else {
                // No Operation attribute found, use full auto-discovery
                $this->autoDiscoverOperation($operationObject, $routeInfo, $uri, $method, $controllerTags);
            }
            
            // Process parameters, request body, and responses from method attributes
            if (!empty($routeInfo->attributes)) {
                // Process parameters
                $parameters = $this->extractParameters($routeInfo->attributes);
                if (!empty($parameters)) {
                    $operationObject['parameters'] = $parameters;
                }
                
                // Process request body
                $requestBody = $this->extractRequestBody($routeInfo->attributes);
                if ($requestBody !== null) {
                    $operationObject['requestBody'] = $requestBody;
                }
                
                // Process responses
                $responses = $this->extractResponses($routeInfo->attributes);
                if (!empty($responses)) {
                    $operationObject['responses'] = $responses;
                }
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
            
            $schemaName = class_basename($model->class);
            
            $schemas[$schemaName] = $modelSchemaArray;
        }

        return $schemas;
    }

    /**
     * Build security schemes from configuration
     * 
     * @return array Security schemes for OpenAPI spec
     */
    private function buildSecuritySchemes(): array
    {
        $securitySchemes = $this->config['security_schemes'] ?? [];
        
        if (empty($securitySchemes)) {
            return [];
        }
        
        $result = [];
        
        foreach ($securitySchemes as $name => $scheme) {
            // Validate required fields based on scheme type
            if (!isset($scheme['type'])) {
                continue; // Skip invalid schemes
            }
            
            // Process different security scheme types
            switch ($scheme['type']) {
                case 'apiKey':
                    if (!isset($scheme['name']) || !isset($scheme['in'])) {
                        continue 2; // Skip invalid apiKey scheme and continue with next scheme
                    }
                    break;
                    
                case 'http':
                    if (!isset($scheme['scheme'])) {
                        continue 2; // Skip invalid http scheme and continue with next scheme
                    }
                    break;
                    
                case 'oauth2':
                    if (!isset($scheme['flows'])) {
                        continue 2; // Skip invalid oauth2 scheme and continue with next scheme
                    }
                    break;
                    
                case 'openIdConnect':
                    if (!isset($scheme['openIdConnectUrl'])) {
                        continue 2; // Skip invalid openIdConnect scheme and continue with next scheme
                    }
                    break;
            }
            
            $result[$name] = $scheme;
        }
        
        return $result;
    }

    /**
     * Build reusable parameters from configuration
     * 
     * @return array Reusable parameters for OpenAPI spec
     */
    private function buildReusableParameters(): array
    {
        $parameters = $this->config['parameters'] ?? [];
        
        if (empty($parameters)) {
            return [];
        }
        
        $result = [];
        
        foreach ($parameters as $name => $parameter) {
            // Validate required fields
            if (!isset($parameter['name']) || !isset($parameter['in'])) {
                continue; // Skip invalid parameters
            }
            
            // Ensure description exists (required by OpenAPI spec)
            if (!isset($parameter['description'])) {
                $parameter['description'] = $name;
            }
            
            // Process schema if it exists
            if (isset($parameter['schema']) && is_array($parameter['schema'])) {
                $parameter['schema'] = $this->processSchema($parameter['schema']);
            }
            
            $result[$name] = $parameter;
        }
        
        return $result;
    }

    /**
     * Build reusable responses from configuration
     * 
     * @return array Reusable responses for OpenAPI spec
     */
    private function buildReusableResponses(): array
    {
        $responses = $this->config['responses'] ?? [];
        
        if (empty($responses)) {
            return [];
        }
        
        $result = [];
        
        foreach ($responses as $name => $response) {
            // Ensure description exists (required by OpenAPI spec)
            if (!isset($response['description'])) {
                $response['description'] = $name;
            }
            
            // Process content if it exists
            if (isset($response['content']) && is_array($response['content'])) {
                $processedContent = [];
                
                foreach ($response['content'] as $mediaType => $content) {
                    if (isset($content['schema'])) {
                        $content['schema'] = $this->processSchema($content['schema']);
                    }
                    
                    $processedContent[$mediaType] = $content;
                }
                
                $response['content'] = $processedContent;
            }
            
            $result[$name] = $response;
        }
        
        return $result;
    }

    /**
     * Build global security requirements from configuration
     * 
     * @return array Global security requirements for OpenAPI spec
     */
    private function buildGlobalSecurity(): array
    {
        $security = $this->config['security'] ?? [];
        
        if (empty($security)) {
            return [];
        }
        
        // Validate security requirements format
        $result = [];
        
        foreach ($security as $requirement) {
            // Security requirement must be an array mapping security scheme names to scopes
            if (!is_array($requirement)) {
                continue;
            }
            
            // Each requirement is a separate security option (OR relationship)
            $validRequirement = [];
            
            foreach ($requirement as $name => $scopes) {
                // Validate that the security scheme exists in the configuration
                if (!isset($this->config['security_schemes'][$name])) {
                    continue;
                }
                
                // Scopes must be an array (can be empty for schemes that don't use scopes)
                if (!is_array($scopes)) {
                    $scopes = [];
                }
                
                $validRequirement[$name] = $scopes;
            }
            
            if (!empty($validRequirement)) {
                $result[] = $validRequirement;
            }
        }
        
        return $result;
    }

    /**
     * Extract parameters from method attributes
     *
     * @param array $attributes Method attributes
     * @return array OpenAPI parameters array
     */
    private function extractParameters(array $attributes): array
    {
        $parameters = [];
        
        foreach ($attributes as $attribute) {
            if ($attribute instanceof \LaravelOpenApi\Attributes\Parameter) {
                $parameter = [
                    'name' => $attribute->name,
                    'in' => $attribute->in,
                    'required' => $attribute->required,
                ];
                
                if ($attribute->description !== null) {
                    $parameter['description'] = $attribute->description;
                }
                
                // Process schema
                if ($attribute->schema !== null) {
                    $parameter['schema'] = $this->processSchema($attribute->schema);
                }
                
                // Process example
                if ($attribute->example !== null) {
                    $parameter['example'] = $attribute->example;
                }
                
                // Process examples
                if (!empty($attribute->examples)) {
                    $parameter['examples'] = $attribute->examples;
                }
                
                $parameters[] = $parameter;
            }
        }
        
        return $parameters;
    }
    
    /**
     * Extract request body from method attributes
     *
     * @param array $attributes Method attributes
     * @return array|null OpenAPI request body object or null if not found
     */
    private function extractRequestBody(array $attributes): ?array
    {
        foreach ($attributes as $attribute) {
            if ($attribute instanceof \LaravelOpenApi\Attributes\RequestBody) {
                $requestBody = [
                    'required' => $attribute->required,
                ];
                
                if ($attribute->description !== null) {
                    $requestBody['description'] = $attribute->description;
                }
                
                // Process content
                if ($attribute->content !== null) {
                    $requestBody['content'] = $this->processContent($attribute->content);
                }
                
                return $requestBody;
            }
        }
        
        return null;
    }
    
    /**
     * Extract responses from method attributes
     *
     * @param array $attributes Method attributes
     * @return array OpenAPI responses object
     */
    private function extractResponses(array $attributes): array
    {
        $responses = [];
        
        foreach ($attributes as $attribute) {
            if ($attribute instanceof \LaravelOpenApi\Attributes\Response) {
                $response = [];
                
                if ($attribute->description !== null) {
                    $response['description'] = $attribute->description;
                } else {
                    // Description is required by OpenAPI spec
                    $response['description'] = 'Response for status ' . $attribute->status;
                }
                
                // Process content
                if ($attribute->content !== null) {
                    $response['content'] = $this->processContent($attribute->content);
                }
                
                // Process headers
                if (!empty($attribute->headers)) {
                    $response['headers'] = $attribute->headers;
                }
                
                $responses[(string) $attribute->status] = $response;
            }
        }
        
        // If no responses were found, provide a default response
        if (empty($responses)) {
            $responses['default'] = ['description' => 'Default response'];
        }
        
        return $responses;
    }
    
    /**
     * Process schema objects from attributes
     *
     * @param mixed $schema Schema object or reference
     * @return array Processed schema
     */
    private function processSchema($schema): array
    {
        if (is_array($schema)) {
            // Handle schema reference
            if (isset($schema['ref'])) {
                $resolvedRef = SchemaResolver::resolve($schema['ref']);
                return ['$ref' => $resolvedRef];
            }
            
            return $schema;
        }
        
        if ($schema instanceof \LaravelOpenApi\Attributes\Schema) {
            $result = [];
            
            // Handle reference first if provided
            if ($schema->ref !== null) {
                $resolvedRef = SchemaResolver::resolve($schema->ref);
                return ['$ref' => $resolvedRef];
            }
            
            // Extract schema properties
            $reflection = new \ReflectionClass($schema);
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
            
            foreach ($properties as $property) {
                $name = $property->getName();
                $value = $property->getValue($schema);
                
                if ($value !== null && $name !== 'properties' && $name !== 'ref') {
                    // Handle enum specially
                    if ($name === 'enum' && !empty($value)) {
                        $result['enum'] = $value;
                    } else {
                        $result[$name] = $value;
                    }
                }
            }
            
            // Process nested properties
            if (!empty($schema->properties)) {
                $result['properties'] = [];
                
                foreach ($schema->properties as $property) {
                    if ($property instanceof \LaravelOpenApi\Attributes\Property) {
                        $result['properties'][$property->property] = $this->processProperty($property);
                    }
                }
            }
            
            // Handle constructor named parameters
            if ($reflection->hasMethod('__construct')) {
                $constructor = $reflection->getMethod('__construct');
                if ($constructor->getNumberOfParameters() > 0) {
                    $args = $constructor->getParameters();
                    foreach ($args as $arg) {
                        $argName = $arg->getName();
                        if ($argName === 'properties') {
                            // This is a special case for handling properties passed as a named parameter
                            // We need to extract the properties from the constructor arguments
                            try {
                                // Get the actual value passed to the constructor
                                $propertiesValue = null;
                                
                                // Try to get the properties from the schema instance
                                $reflectionObject = new \ReflectionObject($schema);
                                $constructorArgs = $reflectionObject->getConstructor()->getParameters();
                                
                                // First, try to access the properties directly from the schema object
                                if (isset($schema->properties) && is_array($schema->properties) && !empty($schema->properties)) {
                                    if (!isset($result['properties'])) {
                                        $result['properties'] = [];
                                    }
                                    
                                    foreach ($schema->properties as $prop) {
                                        if ($prop instanceof \LaravelOpenApi\Attributes\Property) {
                                            $result['properties'][$prop->property] = $this->processProperty($prop);
                                        }
                                    }
                                }
                                
                                // If we couldn't get properties directly, try to extract them from constructor arguments
                                if (empty($result['properties'])) {
                                    foreach ($constructorArgs as $cArg) {
                                        if ($cArg->getName() === 'properties') {
                                            $attributes = $cArg->getAttributes();
                                            if (!empty($attributes)) {
                                                $propValue = $attributes[0]->getArguments()[0] ?? null;
                                                if (is_array($propValue) && !empty($propValue)) {
                                                    if (!isset($result['properties'])) {
                                                        $result['properties'] = [];
                                                    }
                                                    
                                                    foreach ($propValue as $prop) {
                                                        if ($prop instanceof \LaravelOpenApi\Attributes\Property) {
                                                            $result['properties'][$prop->property] = $this->processProperty($prop);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                // Ignore reflection errors
                            }
                        }
                    }
                }
            }
            
            return $result;
        }
        
        // Default fallback for simple types
        return ['type' => 'string'];
    }
    
    /**
     * Process property objects from attributes
     *
     * @param \LaravelOpenApi\Attributes\Property $property
     * @return array Processed property
     */
    private function processProperty(\LaravelOpenApi\Attributes\Property $property): array
    {
        // Handle reference first if provided
        if ($property->ref !== null) {
            $resolvedRef = SchemaResolver::resolve($property->ref);
            return ['$ref' => $resolvedRef];
        }
        
        $result = [];
        
        // Add type if available (required field)
        if ($property->type !== null) {
            $result['type'] = $property->type;
        }
        
        // Add description if available
        if (!empty($property->description)) {
            $result['description'] = $property->description;
        }
        
        // Add format if available
        if (!empty($property->format)) {
            $result['format'] = $property->format;
        }
        
        // Add example if available
        if ($property->example !== null) {
            $result['example'] = $property->example;
        }
        
        // Only add non-empty arrays
        if (!empty($property->enum)) {
            $result['enum'] = $property->enum;
        }
        
        // Add constraints if they exist
        if ($property->minLength !== null) {
            $result['minLength'] = $property->minLength;
        }
        
        if ($property->maxLength !== null) {
            $result['maxLength'] = $property->maxLength;
        }
        
        if ($property->minimum !== null) {
            $result['minimum'] = $property->minimum;
        }
        
        if ($property->maximum !== null) {
            $result['maximum'] = $property->maximum;
        }
        
        // Add boolean flags only if they're true (since false is default)
        if ($property->nullable === true) {
            $result['nullable'] = true;
        }
        
        if ($property->readOnly === true) {
            $result['readOnly'] = true;
        }
        
        if ($property->writeOnly === true) {
            $result['writeOnly'] = true;
        }
        
        // Add default value if it exists
        if ($property->default !== null) {
            $result['default'] = $property->default;
        }
        
        // Process items for array types
        if ($property->type === 'array' && $property->items !== null) {
            $result['items'] = $this->processItems($property->items);
        }
        
        // Process nested properties for object types
        if (!empty($property->properties)) {
            $result['properties'] = [];
            foreach ($property->properties as $nestedProperty) {
                if ($nestedProperty instanceof \LaravelOpenApi\Attributes\Property) {
                    $result['properties'][$nestedProperty->property] = $this->processProperty($nestedProperty);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Process items objects from attributes
     *
     * @param \LaravelOpenApi\Attributes\Items $items
     * @return array Processed items
     */
    private function processItems(\LaravelOpenApi\Attributes\Items $items): array
    {
        if (isset($items->ref)) {
            $resolvedRef = SchemaResolver::resolve($items->ref);
            return ['$ref' => $resolvedRef];
        }
        
        $result = [];
        
        // Add type if available
        if (!empty($items->type)) {
            $result['type'] = $items->type;
        }
        
        // Add format if available
        if (!empty($items->format)) {
            $result['format'] = $items->format;
        }
        
        // Add example if available
        if ($items->example !== null) {
            $result['example'] = $items->example;
        }
        
        // Only add non-empty arrays
        if (!empty($items->enum)) {
            $result['enum'] = $items->enum;
        }
        
        // Add constraints if they exist
        if ($items->minimum !== null) {
            $result['minimum'] = $items->minimum;
        }
        
        if ($items->maximum !== null) {
            $result['maximum'] = $items->maximum;
        }
        
        // Add nullable flag if true
        if ($items->nullable === true) {
            $result['nullable'] = true;
        }
        
        // Add default value if it exists
        if ($items->default !== null) {
            $result['default'] = $items->default;
        }
        
        return $result;
    }
    
    /**
     * Process content objects from attributes
     *
     * @param mixed $content Content object or array
     * @return array Processed content
     */
    private function processContent($content): array
    {
        $result = [];
        
        if (is_array($content)) {
            foreach ($content as $mediaType) {
                if ($mediaType instanceof \LaravelOpenApi\Attributes\MediaType) {
                    $result[$mediaType->mediaType] = [];
                    
                    // Process schema if provided
                    if ($mediaType->schema !== null) {
                        $result[$mediaType->mediaType]['schema'] = $this->processSchema($mediaType->schema);
                    }
                    
                    // Add examples if provided
                    if (!empty($mediaType->examples)) {
                        $result[$mediaType->mediaType]['examples'] = $mediaType->examples;
                    }
                    
                    // Add example if provided
                    if ($mediaType->example !== null) {
                        $result[$mediaType->mediaType]['example'] = $mediaType->example;
                    }
                    
                    // Add encoding if provided
                    if (!empty($mediaType->encoding)) {
                        $result[$mediaType->mediaType]['encoding'] = $mediaType->encoding;
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Auto-discover operation details from route information
     *
     * @param array $operationObject The operation object to populate
     * @param RouteInfo $routeInfo Route information
     * @param string $uri URI of the route
     * @param string $method HTTP method of the route
     * @param array $controllerTags Controller tags
     * @return void
     */
    private function autoDiscoverOperation(array &$operationObject, $routeInfo, string $uri, string $method, array $controllerTags): void
    {
        // Check if operationId is already set from the Operation attribute
        if (!isset($operationObject['operationId'])) {
            // Generate operation ID from route name or URI
            if ($routeInfo->name) {
                $operationObject['operationId'] = $routeInfo->name;
            } else {
                // Generate from URI and method
                $safeUri = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(['/', '{', '}'], ['_', '', ''], $uri));
                $operationObject['operationId'] = $method . ucfirst($safeUri);
            }
        }
        
        // Set summary if not already set
        if (!isset($operationObject['summary'])) {
            // Generate summary from route name or URI
            if ($routeInfo->name) {
                $operationObject['summary'] = ucfirst(str_replace(['.', '-', '_'], ' ', $routeInfo->name));
            } else {
                $operationObject['summary'] = ucfirst($method) . ' ' . $uri;
            }
        }
        
        // Set tags if not already set
        if (!isset($operationObject['tags']) && !empty($controllerTags)) {
            $operationObject['tags'] = $controllerTags;
        }
    }
    
    /**
     * Build tags from discovered routes
     *
     * @param Collection $routes
     * @return array
     */
    private function buildTags(Collection $routes): array
    {
        $tags = [];
        $tagNames = [];
        
        // Extract tags from ApiTag attributes
        foreach ($routes as $routeInfo) {
            if (!empty($routeInfo->controllerAttributes)) {
                foreach ($routeInfo->controllerAttributes as $attribute) {
                    if ($attribute instanceof \LaravelOpenApi\Attributes\ApiTag) {
                        $tagName = $attribute->name;
                        
                        // Only add unique tags
                        if (!in_array($tagName, $tagNames)) {
                            $tagNames[] = $tagName;
                            
                            $tag = [
                                'name' => $tagName,
                            ];
                            
                            if ($attribute->description) {
                                $tag['description'] = $attribute->description;
                            }
                            
                            $tags[] = $tag;
                        }
                    }
                }
            }
        }
        
        // Add tags from config if any
        if (isset($this->config['tags']) && is_array($this->config['tags'])) {
            foreach ($this->config['tags'] as $configTag) {
                if (isset($configTag['name']) && !in_array($configTag['name'], $tagNames)) {
                    $tagNames[] = $configTag['name'];
                    $tags[] = $configTag;
                }
            }
        }
        
        return $tags;
    }
}
