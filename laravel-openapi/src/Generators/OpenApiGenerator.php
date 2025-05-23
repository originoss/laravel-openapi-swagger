<?php

namespace LaravelOpenApi\Generators;

use LaravelOpenApi\Discovery\RouteDiscovery;
use LaravelOpenApi\Discovery\ModelDiscovery;
use LaravelOpenApi\Schema\SchemaBuilder;
use Illuminate\Support\Collection;
use LaravelOpenApi\Attributes\Operation as OperationAttribute;

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
                if ($operationAttribute->summary !== null) $operationObject['summary'] = $operationAttribute->summary;
                if ($operationAttribute->description !== null) $operationObject['description'] = $operationAttribute->description;
                if ($operationAttribute->operationId !== null) $operationObject['operationId'] = $operationAttribute->operationId;
                
                // Merge controller tags with operation tags
                $operationTags = !empty($operationAttribute->tags) ? $operationAttribute->tags : [];
                $mergedTags = array_unique(array_merge($controllerTags, $operationTags));
                if (!empty($mergedTags)) $operationObject['tags'] = $mergedTags;
                
                if ($operationAttribute->deprecated) $operationObject['deprecated'] = true; // Only set if true
                
                // If security is defined in Operation attribute, it overrides global/tag security
                // Note: OpenAPI spec expects security to be an array of Security Requirement Objects.
                // The OperationAttribute->security is expected to be in this format.
                if (!empty($operationAttribute->security)) {
                    $operationObject['security'] = $operationAttribute->security;
                }
            } else {
                // Auto-discovery: Generate basic operation details from route information
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
                return ['$ref' => $schema['ref']];
            }
            
            return $schema;
        }
        
        if ($schema instanceof \LaravelOpenApi\Attributes\Schema) {
            $result = [];
            
            // Extract schema properties
            $reflection = new \ReflectionClass($schema);
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
            
            foreach ($properties as $property) {
                $name = $property->getName();
                $value = $property->getValue($schema);
                
                if ($value !== null && $name !== 'properties') {
                    $result[$name] = $value;
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
        $result = [
            'type' => $property->type,
        ];
        
        // Add additional property attributes
        $reflection = new \ReflectionClass($property);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $prop) {
            $name = $prop->getName();
            $value = $prop->getValue($property);
            
            // Skip the property name and type as they're already processed
            if ($value !== null && $name !== 'property' && $name !== 'type') {
                if ($name === 'items' && $value instanceof \LaravelOpenApi\Attributes\Items) {
                    // Process items for array types
                    $result['items'] = $this->processItems($value);
                } else {
                    $result[$name] = $value;
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
            return ['$ref' => $items->ref];
        }
        
        $result = [];
        
        // Add items attributes
        $reflection = new \ReflectionClass($items);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($items);
            
            if ($value !== null && $name !== 'ref') {
                $result[$name] = $value;
            }
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
                    $result[$mediaType->mediaType] = [
                        'schema' => $this->processSchema($mediaType->schema),
                    ];
                    
                    // Add examples if provided
                    if (!empty($mediaType->examples)) {
                        $result[$mediaType->mediaType]['examples'] = $mediaType->examples;
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
        // Generate operation ID from route name or URI
        if ($routeInfo->name) {
            $operationObject['operationId'] = $routeInfo->name;
        } else {
            // Generate from URI and method
            $safeUri = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(['/', '{', '}'], ['_', '', ''], $uri));
            $operationObject['operationId'] = $method . $safeUri;
        }
        
        // Generate summary and description from controller and method names
        if ($routeInfo->controller && $routeInfo->controllerMethod) {
            // Extract controller name without namespace and 'Controller' suffix
            $controllerName = class_basename($routeInfo->controller);
            $controllerName = str_replace('Controller', '', $controllerName);
            
            // Format controller name for readability (e.g., 'UserProfile' -> 'User Profile')
            $controllerName = preg_replace('/(?<!^)[A-Z]/', ' $0', $controllerName);
            
            // Format method name for readability
            $methodName = $routeInfo->controllerMethod;
            if ($methodName === '__invoke') {
                $methodName = $method; // Use HTTP method if it's an invokable controller
            } else {
                $methodName = preg_replace('/(?<!^)[A-Z]/', ' $0', $methodName);
            }
            
            // Generate summary based on method and controller
            switch ($methodName) {
                case 'index':
                    $operationObject['summary'] = 'List all ' . strtolower($controllerName) . 's';
                    $operationObject['description'] = 'Returns a list of ' . strtolower($controllerName) . ' resources.';
                    break;
                case 'show':
                    $operationObject['summary'] = 'Get a specific ' . strtolower($controllerName);
                    $operationObject['description'] = 'Returns a specific ' . strtolower($controllerName) . ' resource.';
                    break;
                case 'store':
                    $operationObject['summary'] = 'Create a new ' . strtolower($controllerName);
                    $operationObject['description'] = 'Creates a new ' . strtolower($controllerName) . ' resource.';
                    break;
                case 'update':
                    $operationObject['summary'] = 'Update a ' . strtolower($controllerName);
                    $operationObject['description'] = 'Updates an existing ' . strtolower($controllerName) . ' resource.';
                    break;
                case 'destroy':
                    $operationObject['summary'] = 'Delete a ' . strtolower($controllerName);
                    $operationObject['description'] = 'Deletes a ' . strtolower($controllerName) . ' resource.';
                    break;
                default:
                    $operationObject['summary'] = ucfirst($methodName) . ' ' . strtolower($controllerName);
                    $operationObject['description'] = 'Endpoint for ' . strtolower($methodName) . ' operation on ' . strtolower($controllerName) . ' resource.';
            }
        } else {
            // Fallback if controller or method is not available
            $operationObject['summary'] = ucfirst($method) . ' ' . $uri;
            $operationObject['description'] = 'Endpoint for ' . $uri;
        }
        
        // Add controller tags
        if (!empty($controllerTags)) {
            $operationObject['tags'] = $controllerTags;
        } else if ($routeInfo->controller) {
            // Generate tag from controller name if no explicit tags are available
            $controllerName = class_basename($routeInfo->controller);
            $controllerName = str_replace('Controller', '', $controllerName);
            // Format for readability and pluralize
            $controllerName = preg_replace('/(?<!^)[A-Z]/', ' $0', $controllerName);
            $controllerName = trim($controllerName) . 's';
            $operationObject['tags'] = [$controllerName];
        }
        
        // Auto-discover parameters from route URI
        $this->autoDiscoverParameters($operationObject, $routeInfo);
        
        // Auto-discover request body for POST, PUT, PATCH methods
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $this->autoDiscoverRequestBody($operationObject, $routeInfo);
        }
        
        // Auto-discover responses based on method and route info
        $this->autoDiscoverResponses($operationObject, $method, $routeInfo);
    }
    
    /**
     * Auto-discover parameters from route URI and constraints
     *
     * @param array $operationObject The operation object to populate
     * @param RouteInfo $routeInfo Route information
     * @return void
     */
    private function autoDiscoverParameters(array &$operationObject, $routeInfo): void
    {
        $parameters = [];
        
        // Process path parameters from URI
        foreach ($routeInfo->parameters as $paramName) {
            $parameter = [
                'name' => $paramName,
                'in' => 'path',
                'required' => true, // Path parameters are always required
                'description' => 'The ' . str_replace('_', ' ', $paramName) . ' parameter',
                'schema' => ['type' => 'string'] // Default to string type
            ];
            
            // Check for route constraints to determine parameter type
            if (!empty($routeInfo->wheres) && isset($routeInfo->wheres[$paramName])) {
                $constraint = $routeInfo->wheres[$paramName];
                
                // Determine type from constraint pattern
                if ($constraint === '\d+' || $constraint === '[0-9]+') {
                    $parameter['schema'] = ['type' => 'integer'];
                } elseif (str_contains($constraint, '[0-9]') || str_contains($constraint, '\d')) {
                    $parameter['schema'] = ['type' => 'string', 'pattern' => $constraint];
                } elseif ($constraint === '[A-Za-z]+') {
                    $parameter['schema'] = ['type' => 'string', 'pattern' => $constraint];
                } elseif ($constraint === '[\w-]+') {
                    $parameter['schema'] = ['type' => 'string', 'pattern' => $constraint];
                } elseif (str_contains($constraint, 'uuid')) {
                    $parameter['schema'] = ['type' => 'string', 'format' => 'uuid'];
                } else {
                    $parameter['schema'] = ['type' => 'string', 'pattern' => $constraint];
                }
            }
            
            // Add to parameters array
            $parameters[] = $parameter;
        }
        
        // Add common query parameters based on controller method
        if ($routeInfo->controllerMethod === 'index') {
            // Add pagination parameters for index methods
            $parameters[] = [
                'name' => 'page',
                'in' => 'query',
                'description' => 'Page number for pagination',
                'required' => false,
                'schema' => ['type' => 'integer', 'default' => 1]
            ];
            
            $parameters[] = [
                'name' => 'per_page',
                'in' => 'query',
                'description' => 'Number of items per page',
                'required' => false,
                'schema' => ['type' => 'integer', 'default' => 15]
            ];
            
            // Add sorting parameters
            $parameters[] = [
                'name' => 'sort_by',
                'in' => 'query',
                'description' => 'Field to sort by',
                'required' => false,
                'schema' => ['type' => 'string']
            ];
            
            $parameters[] = [
                'name' => 'sort_direction',
                'in' => 'query',
                'description' => 'Direction to sort (asc or desc)',
                'required' => false,
                'schema' => ['type' => 'string', 'enum' => ['asc', 'desc'], 'default' => 'asc']
            ];
        }
        
        // Add parameters to operation object if any were discovered
        if (!empty($parameters)) {
            $operationObject['parameters'] = $parameters;
        }
    }
    
    /**
     * Auto-discover request body for write operations
     *
     * @param array $operationObject The operation object to populate
     * @param RouteInfo $routeInfo Route information
     * @return void
     */
    private function autoDiscoverRequestBody(array &$operationObject, $routeInfo): void
    {
        // Only add request body for controllers with identifiable resource name
        if (!$routeInfo->controller) {
            return;
        }
        
        // Extract resource name from controller
        $resourceName = class_basename($routeInfo->controller);
        $resourceName = str_replace('Controller', '', $resourceName);
        $resourceName = preg_replace('/(?<!^)[A-Z]/', ' $0', $resourceName);
        $resourceName = trim(strtolower($resourceName));
        
        // Create basic request body
        $requestBody = [
            'description' => ucfirst($resourceName) . ' information',
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            // Add some common properties based on resource name
                            'name' => ['type' => 'string', 'description' => ucfirst($resourceName) . ' name'],
                            'description' => ['type' => 'string', 'description' => ucfirst($resourceName) . ' description'],
                        ]
                    ]
                ]
            ]
        ];
        
        // Add method-specific properties
        if ($routeInfo->controllerMethod === 'store') {
            $requestBody['description'] = 'Create a new ' . $resourceName;
        } elseif ($routeInfo->controllerMethod === 'update') {
            $requestBody['description'] = 'Update an existing ' . $resourceName;
            $requestBody['required'] = false; // Updates might be partial
        }
        
        $operationObject['requestBody'] = $requestBody;
    }
    
    /**
     * Auto-discover responses based on HTTP method and controller information
     *
     * @param array $operationObject The operation object to populate
     * @param string $method HTTP method
     * @param RouteInfo|null $routeInfo Route information (optional)
     * @return void
     */
    private function autoDiscoverResponses(array &$operationObject, string $method, $routeInfo = null): void
    {
        $responses = [];
        $method = strtoupper($method);
        
        // Extract resource name from controller if available
        $resourceName = '';
        $resourceSchema = null;
        $isPaginated = false;
        
        if ($routeInfo && $routeInfo->controller) {
            // Extract resource name from controller
            $resourceName = class_basename($routeInfo->controller);
            $resourceName = str_replace('Controller', '', $resourceName);
            
            // Check if it's an index method (likely to return paginated results)
            if ($routeInfo->controllerMethod === 'index') {
                $isPaginated = true;
            }
            
            // Try to determine if there's a corresponding model for this resource
            $modelName = rtrim($resourceName, 's'); // Simple singularization
            $potentialModelClass = 'App\\Models\\' . $modelName;
            
            // Check if the model exists
            if (class_exists($potentialModelClass)) {
                // We found a matching model, use it for schema reference
                $resourceSchema = ['$ref' => '#/components/schemas/' . $modelName];
            } else {
                // No matching model found, create a generic schema
                $resourceSchema = $this->createGenericResourceSchema($resourceName);
            }
        }
        
        // Default response for all methods
        $responses['default'] = [
            'description' => 'Unexpected error',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'example' => 'An unexpected error occurred']
                        ]
                    ]
                ]
            ]
        ];
        
        // Method-specific success responses
        switch ($method) {
            case 'GET':
                if ($isPaginated) {
                    // Paginated collection response
                    $responses['200'] = [
                        'description' => 'A paginated list of resources',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            'type' => 'array',
                                            'items' => $resourceSchema ?: ['type' => 'object']
                                        ],
                                        'links' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'first' => ['type' => 'string', 'format' => 'uri'],
                                                'last' => ['type' => 'string', 'format' => 'uri'],
                                                'prev' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                                                'next' => ['type' => 'string', 'format' => 'uri', 'nullable' => true]
                                            ]
                                        ],
                                        'meta' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'current_page' => ['type' => 'integer', 'example' => 1],
                                                'from' => ['type' => 'integer', 'example' => 1],
                                                'last_page' => ['type' => 'integer', 'example' => 5],
                                                'path' => ['type' => 'string', 'format' => 'uri'],
                                                'per_page' => ['type' => 'integer', 'example' => 15],
                                                'to' => ['type' => 'integer', 'example' => 15],
                                                'total' => ['type' => 'integer', 'example' => 75]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                } else if (strpos($routeInfo->uri, '{') !== false) {
                    // Single resource response (has path parameter)
                    $responses['200'] = [
                        'description' => 'The requested resource',
                        'content' => [
                            'application/json' => [
                                'schema' => $resourceSchema ?: ['type' => 'object']
                            ]
                        ]
                    ];
                    
                    $responses['404'] = [
                        'description' => 'Resource not found',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => ['type' => 'string', 'example' => 'Resource not found']
                                    ]
                                ]
                            ]
                        ]
                    ];
                } else {
                    // Generic GET response
                    $responses['200'] = [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object']
                            ]
                        ]
                    ];
                }
                break;
                
            case 'POST':
                $responses['201'] = [
                    'description' => 'Resource created successfully',
                    'content' => [
                        'application/json' => [
                            'schema' => $resourceSchema ?: [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer', 'example' => 1],
                                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                                ]
                            ]
                        ]
                    ]
                ];
                
                $responses['422'] = [
                    'description' => 'Validation error',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'message' => ['type' => 'string', 'example' => 'The given data was invalid.'],
                                    'errors' => [
                                        'type' => 'object',
                                        'additionalProperties' => [
                                            'type' => 'array',
                                            'items' => ['type' => 'string']
                                        ],
                                        'example' => [
                                            'name' => ['The name field is required.'],
                                            'email' => ['The email must be a valid email address.']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                break;
                
            case 'PUT':
            case 'PATCH':
                $responses['200'] = [
                    'description' => 'Resource updated successfully',
                    'content' => [
                        'application/json' => [
                            'schema' => $resourceSchema ?: [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer', 'example' => 1],
                                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                                ]
                            ]
                        ]
                    ]
                ];
                
                $responses['404'] = [
                    'description' => 'Resource not found',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'message' => ['type' => 'string', 'example' => 'Resource not found']
                                ]
                            ]
                        ]
                    ]
                ];
                
                $responses['422'] = [
                    'description' => 'Validation error',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'message' => ['type' => 'string', 'example' => 'The given data was invalid.'],
                                    'errors' => [
                                        'type' => 'object',
                                        'additionalProperties' => [
                                            'type' => 'array',
                                            'items' => ['type' => 'string']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                break;
                
            case 'DELETE':
                $responses['204'] = [
                    'description' => 'Resource deleted successfully'
                ];
                
                $responses['404'] = [
                    'description' => 'Resource not found',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'message' => ['type' => 'string', 'example' => 'Resource not found']
                                ]
                            ]
                        ]
                    ]
                ];
                break;
        }
        
        $operationObject['responses'] = $responses;
    }
    
    /**
     * Create a generic schema for a resource based on its name
     *
     * @param string $resourceName The name of the resource
     * @return array The schema definition
     */
    private function createGenericResourceSchema(string $resourceName): array
    {
        // Singularize the resource name
        $singularName = rtrim($resourceName, 's');
        
        // Create a generic schema with common fields
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'example' => 1],
                'name' => ['type' => 'string', 'example' => ucfirst($singularName) . ' name'],
                'description' => ['type' => 'string', 'example' => 'Description of the ' . strtolower($singularName)],
                'created_at' => ['type' => 'string', 'format' => 'date-time'],
                'updated_at' => ['type' => 'string', 'format' => 'date-time']
            ]
        ];
    }

    private function buildTags(Collection $routes): array
    {
        $tags = [];
        $tagNames = [];
        
        // Extract tags from controller attributes (ApiTag)
        foreach ($routes as $routeInfo) {
            // Process controller attributes (ApiTag)
            if (!empty($routeInfo->controllerAttributes)) {
                foreach ($routeInfo->controllerAttributes as $attribute) {
                    if ($attribute instanceof \LaravelOpenApi\Attributes\ApiTag) {
                        // Avoid duplicate tags
                        if (!in_array($attribute->name, $tagNames)) {
                            $tagNames[] = $attribute->name;
                            $tag = ['name' => $attribute->name];
                            
                            if ($attribute->description) {
                                $tag['description'] = $attribute->description;
                            }
                            
                            if ($attribute->externalDocs) {
                                $tag['externalDocs'] = ['url' => $attribute->externalDocs];
                            }
                            
                            $tags[] = $tag;
                        }
                    }
                }
            }
            
            // Also collect tags from route operation attributes
            if (!empty($routeInfo->attributes)) {
                foreach ($routeInfo->attributes as $attribute) {
                    if ($attribute instanceof \LaravelOpenApi\Attributes\Operation && !empty($attribute->tags)) {
                        foreach ($attribute->tags as $tagName) {
                            // Only add tags that don't already exist and aren't just a string reference
                            if (!in_array($tagName, $tagNames) && is_array($tagName) && isset($tagName['name'])) {
                                $tagNames[] = $tagName['name'];
                                $tags[] = $tagName;
                            }
                        }
                    }
                }
            }
        }
        
        return $tags;
    }
}
