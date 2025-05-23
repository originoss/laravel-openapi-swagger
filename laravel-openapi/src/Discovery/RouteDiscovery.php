<?php

namespace LaravelOpenApi\Discovery;

use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Routing\Route;
use LaravelOpenApi\Parsers\AttributeParser;
use ReflectionMethod;
use ReflectionClass;

class RouteDiscovery
{
    public function __construct(
        private Router $router,
        private AttributeParser $attributeParser,
        private ?array $config = []
    ) {}

    public function discover(): Collection
    {
        return collect($this->router->getRoutes()->getRoutes())
            ->filter(fn(Route $route) => $this->shouldIncludeRoute($route))
            ->map(fn(Route $route) => $this->analyzeRoute($route));
    }

    private function shouldIncludeRoute(Route $route): bool
    {
        // Skip routes without controller actions
        if (!is_string($route->getActionName()) || !str_contains($route->getActionName(), '@')) {
            return false;
        }
        
        // Skip routes from Laravel's internal controllers
        if (str_starts_with($route->getActionName(), 'Laravel\\') || str_starts_with($route->getActionName(), 'LaravelOpenApi\\')) {
            return false;
        }
        
        // Skip the OpenAPI JSON/YAML routes
        if (isset($this->config['paths'])) {
            if (isset($this->config['paths']['json_route_path'])) {
                $jsonPath = ltrim($this->config['paths']['json_route_path'], '/');
                if ($route->uri() === $jsonPath) {
                    return false;
                }
            }
            if (isset($this->config['paths']['yaml_route_path'])) {
                $yamlPath = ltrim($this->config['paths']['yaml_route_path'], '/');
                if ($route->uri() === $yamlPath) {
                    return false;
                }
            }
        }
        
        // Implement config-based filters
        if (!empty($this->config['exclude_patterns'])) {
            foreach ($this->config['exclude_patterns'] as $pattern) {
                if (fnmatch($pattern, $route->uri())) {
                    return false;
                }
            }
        }
        
        // Skip file paths (like those ending with extensions)
        if (preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|ico|pdf|txt|html|xml|json|yml|yaml)$/', $route->uri())) {
            return false;
        }

        
        // Only include API routes (those with 'api' in the URI)
        if (!str_contains($route->uri(), 'api')) {
            return false;
        }
        
        return true;
    }

    private function analyzeRoute(Route $route): RouteInfo
    {
        $actionName = $route->getActionName();
        $controller = null;
        $controllerMethod = null;
        $controllerAttributes = [];
        $routeName = null;
        if ($route->getName()) {
            // Handle standard route names
            $routeName = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $route->getName()))));
            
            // Handle name.method style route names
            if (str_contains($route->getName(), '.')) {
                $parts = explode('.', $route->getName());
                $routeName = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $parts[0]))));
                if (isset($parts[1])) {
                    $routeName .= ucfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $parts[1]))));
                }
            }
        }
        $routeAction = $route->getAction();

        if (is_string($actionName) && str_contains($actionName, '@')) {
            [$controller, $controllerMethod] = explode('@', $actionName);
        } elseif (is_array($route->getAction('uses')) && count($route->getAction('uses')) === 2) {
            // Handle [Controller::class, 'method'] syntax
            if (is_string($route->getAction('uses')[0])) {
                 $controller = $route->getAction('uses')[0];
            } elseif (is_object($route->getAction('uses')[0])) {
                // This case might occur if the controller is already an instance, though less common in raw route definitions
                $controller = get_class($route->getAction('uses')[0]);
            }
            $controllerMethod = $route->getAction('uses')[1];
        } elseif ($route->getControllerClass()) { 
            $controller = $route->getControllerClass();
            // Try to get method from action method string if available and not default
            if (is_string($route->getActionMethod()) && $route->getActionMethod() !== get_class($route)) {
                 $controllerMethod = $route->getActionMethod();
            }
        }
        // Additional check for routes defined with only a controller class (e.g. invokable controller)
        if ($controller && !$controllerMethod && method_exists($controller, '__invoke')) {
            $controllerMethod = '__invoke';
        }

        // Extract controller attributes if controller exists
        if ($controller && class_exists($controller)) {
            $controllerAttributes = $this->extractControllerAttributes($controller);
        }
        
        // Extract route constraints (wheres)
        $wheres = [];
        if (method_exists($route, 'wheres')) {
            $wheres = $route->wheres();
        } elseif (method_exists($route, 'getWheres')) {
            $wheres = $route->getWheres();
        } elseif (property_exists($route, 'wheres')) {
            $wheres = $route->wheres;
        }

        return new RouteInfo(
            method: $route->methods()[0], // Ensure methods returns at least one, or handle empty
            uri: $route->uri(),
            action: $route->getAction(), // This is the raw action
            controller: $controller,
            controllerMethod: $controllerMethod,
            middleware: $route->middleware(),
            parameters: $this->extractRouteParameters($route),
            attributes: $this->extractAttributes($route, $controller, $controllerMethod),
            controllerAttributes: $controllerAttributes,
            name: $routeName,
            wheres: $wheres
        );
    }

    private function extractRouteParameters(Route $route): array
    {
        // Safe extraction of route parameters without calling ->parameters()
        $parameterNames = [];
        
        // Use the compiled route pattern if available
        if ($route->compiled) {
            return $route->compiled->getVariables();
        }
        
        // Fallback: Extract parameters from URI using regex
        $uri = $route->uri();
        if (preg_match_all('/{([^}]+)}/', $uri, $matches)) {
            $parameterNames = $matches[1];
        }
        
        return $parameterNames;
    }

    // Extract attributes from controller method
    private function extractAttributes(Route $route, ?string $controllerName, ?string $methodName): array
    {
        if (!$controllerName || !$methodName) {
            return []; // Cannot get attributes if controller or method is not identified
        }

        // Ensure the controller class exists and the method exists on the controller
        if (!class_exists($controllerName) || !method_exists($controllerName, $methodName)) {
            error_log("Controller {$controllerName} or method {$methodName} not found for attribute parsing.");
            return [];
        }

        try {
            $reflectionMethod = new ReflectionMethod($controllerName, $methodName);
            
            // Get all method attributes
            $attributes = $this->attributeParser->getMethodAttributes($reflectionMethod);
            
            // Prioritize Operation attribute if it exists
            // This ensures that the Operation attribute is properly processed
            // during OpenAPI generation
            foreach ($attributes as $key => $attribute) {
                if ($attribute instanceof \LaravelOpenApi\Attributes\Operation) {
                    // Move the Operation attribute to the beginning of the array
                    // to ensure it's processed first
                    unset($attributes[$key]);
                    array_unshift($attributes, $attribute);
                    break;
                }
            }
            
            return $attributes;
        } catch (\ReflectionException $e) {
            error_log("ReflectionException for {$controllerName}::{$methodName}: " . $e->getMessage());
            return [];
        }
    }
    
    // Extract attributes from controller class
    private function extractControllerAttributes(string $controllerName): array
    {
        if (!class_exists($controllerName)) {
            return [];
        }
        
        try {
            $reflectionClass = new ReflectionClass($controllerName);
            return $this->attributeParser->getClassAttributes($reflectionClass);
        } catch (\ReflectionException $e) {
            error_log("ReflectionException for controller {$controllerName}: " . $e->getMessage());
            return [];
        }
    }
}
