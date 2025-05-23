<?php

namespace LaravelOpenApi\Utilities;

class SchemaResolver
{
    /**
     * Resolve a schema reference from various formats
     * 
     * @param string|null $ref The reference to resolve
     * @return string|null The resolved reference
     */
    public static function resolve(?string $ref): ?string
    {
        if (empty($ref)) {
            return null;
        }

        // If it's already a proper OpenAPI reference, return it as is
        if (str_starts_with($ref, '#/')) {
            return $ref;
        }

        // Handle Model::class syntax
        if (class_exists($ref) && method_exists($ref, 'getTable')) {
            // Extract class name without namespace
            $className = class_basename($ref);
            return '#/components/schemas/' . $className;
        }

        // Handle simple model name
        return '#/components/schemas/' . $ref;
    }
}
