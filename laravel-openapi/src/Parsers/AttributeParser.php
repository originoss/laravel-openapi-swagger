<?php

namespace LaravelOpenApi\Parsers;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionAttribute;

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
        if (!$reflector || !method_exists($reflector, 'getAttributes')) {
            return [];
        }

        $reflectionAttributes = $reflector->getAttributes();

        foreach ($reflectionAttributes as $reflectionAttribute) {
            try {
                if (class_exists($reflectionAttribute->getName())) {
                    $attributes[] = $reflectionAttribute->newInstance();
                }
            } catch (\Throwable $e) {
                error_log("Failed to instantiate attribute " . $reflectionAttribute->getName() . ": " . $e->getMessage());
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
