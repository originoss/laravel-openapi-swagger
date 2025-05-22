<?php

namespace LaravelOpenApi\Discovery;

class RouteInfo
{
    public function __construct(
        public string $method,
        public string $uri,
        public mixed $action, // Can be a Closure or an array [controller, method]
        public ?string $controller,
        public ?string $controllerMethod,
        public array $middleware,
        public array $parameters, // Information about URI parameters
        public array $attributes  // Extracted PHP attributes
    ) {}
}
