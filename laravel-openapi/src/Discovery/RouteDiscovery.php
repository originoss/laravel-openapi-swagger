<?php

namespace LaravelOpenApi\Discovery;

use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Routing\Route;
use LaravelOpenApi\Parsers\AttributeParser;
use ReflectionMethod;

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
        if (str_starts_with($route->getActionName(), 'Laravel\\')) {
            return false;
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

        return new RouteInfo(
            method: $route->methods()[0], // Ensure methods returns at least one, or handle empty
            uri: $route->uri(),
            action: $route->getAction(), // This is the raw action
            controller: $controller,
            controllerMethod: $controllerMethod,
            middleware: $route->middleware(),
            parameters: $this->extractRouteParameters($route),
            attributes: $this->extractAttributes($route, $controller, $controllerMethod) // Pass controller and method
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

    // Modify extractAttributes to accept $controllerName and $methodName
    private function extractAttributes(Route $route, ?string $controllerName, ?string $methodName): array
    {
        if (!$controllerName || !$methodName) {
            return []; // Cannot get attributes if controller or method is not identified
        }

        // Ensure the controller class exists and the method exists on the controller
        if (!class_exists($controllerName) || !method_exists($controllerName, $methodName)) {
            // Optionally log this situation: e.g., controller class not found or method not found.
            // error_log("Controller {$controllerName} or method {$methodName} not found for attribute parsing.");
            return [];
        }

        try {
            $reflectionMethod = new ReflectionMethod($controllerName, $methodName);
            return $this->attributeParser->getMethodAttributes($reflectionMethod);
        } catch (\ReflectionException $e) {
            // Log error or handle if reflection fails (e.g., class or method doesn't exist)
            // error_log("ReflectionException for {$controllerName}::{$methodName}: " . $e->getMessage());
            return [];
        }
    }
}
