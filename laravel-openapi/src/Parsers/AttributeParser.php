<?php

namespace LaravelOpenApi\Parsers;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionAttribute; // Import ReflectionAttribute

class AttributeParser
{
    /**
     * Parses PHP attributes from a given reflector (class, method, or property).
     *
     * @param ReflectionClass|ReflectionMethod|ReflectionProperty $reflector
     * @return array An array of instantiated attribute objects.
     */
    public function getAttributes(ReflectionClass|ReflectionMethod|ReflectionProperty $reflector): array
    {
        $attributes = [];
        // Ensure $reflector is not null and getAttributes method exists
        if (!$reflector || !method_exists($reflector, 'getAttributes')) {
            return [];
        }

        $reflectionAttributes = $reflector->getAttributes();

        foreach ($reflectionAttributes as $reflectionAttribute) {
            try {
                // Ensure the attribute class exists before trying to instantiate
                if (class_exists($reflectionAttribute->getName())) {
                    $attributes[] = $reflectionAttribute->newInstance();
                }
            } catch (\Throwable $e) {
                
                // Optional: Log error or handle specific exceptions if an attribute fails to instantiate
                error_log("Failed to instantiate attribute " . $reflectionAttribute->getName() . ": " . $e->getMessage());
                // For now, re-throw or handle as per application error policy
                // For this implementation, we'll silently ignore to match skeleton's leniency
            }
        }

        return $attributes;
    }

    /**
     * A more specific method for ReflectionClass.
     *
     * @param ReflectionClass $reflectionClass
     * @return array An array of instantiated attribute objects.
     */
    public function getClassAttributes(ReflectionClass $reflectionClass): array
    {
        return $this->getAttributes($reflectionClass);
    }

    /**
     * A more specific method for ReflectionMethod.
     *
     * @param ReflectionMethod $reflectionMethod
     * @return array An array of instantiated attribute objects.
     */
    public function getMethodAttributes(ReflectionMethod $reflectionMethod): array
    {
        return $this->getAttributes($reflectionMethod);
    }

    /**
     * A more specific method for ReflectionProperty.
     *
     * @param ReflectionProperty $reflectionProperty
     * @return array An array of instantiated attribute objects.
     */
    public function getPropertyAttributes(ReflectionProperty $reflectionProperty): array
    {
        return $this->getAttributes($reflectionProperty);
    }
}
