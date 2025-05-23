<?php

namespace LaravelOpenApi\Schema;

// Remove: use LaravelOpenApi\Schema\DatabaseColumn; // No longer needed
use LaravelOpenApi\Discovery\ModelSchema;
use LaravelOpenApi\Attributes\Property as PropertyAttribute;
use LaravelOpenApi\Attributes\Schema as SchemaAttribute;

class SchemaBuilder
{
    // Constructor might be needed later for config or other dependencies
    // public function __construct() {}

    public function buildModelSchema(ModelSchema $model): array
    {
        $properties = [];
        $inferredRequired = [];

        $schemaAttribute = null;
        foreach ($model->attributes['class'] ?? [] as $attr) {
            if ($attr instanceof SchemaAttribute) {
                $schemaAttribute = $attr;
                break;
            }
        }
        
        // Process property attributes from class properties
        foreach ($model->attributes['properties'] ?? [] as $propertyName => $propertyAttributes) {
            /** @var PropertyAttribute|null $propertyAttrInstance */
            $propertyAttrInstance = null;
            foreach ($propertyAttributes as $attr) {
                if ($attr instanceof PropertyAttribute) {
                    $propertyAttrInstance = $attr;
                    break;
                }
            }

            if ($propertyAttrInstance) {
                $properties[$propertyName] = $this->buildSchemaFromPropertyAttribute($propertyAttrInstance);

                if ($propertyAttrInstance->required || (!$propertyAttrInstance->nullable && $propertyAttrInstance->type !== 'array' && $propertyAttrInstance->type !== 'object')) {
                    $inferredRequired[] = $propertyName;
                }
            } else {
                // For properties without explicit attributes, infer from model metadata
                $properties[$propertyName] = $this->inferPropertySchema($propertyName, $model);
            }
        }
        
        // Also process property attributes defined at class level
        // This is for the new Property attribute structure
        foreach ($model->attributes['class'] ?? [] as $attr) {
            if ($attr instanceof PropertyAttribute && !empty($attr->property)) {
                $propertyName = $attr->property;
                $properties[$propertyName] = $this->buildSchemaFromPropertyAttribute($attr);
                
                if ($attr->required || (!$attr->nullable && $attr->type !== 'array' && $attr->type !== 'object')) {
                    $inferredRequired[] = $propertyName;
                }
            }
        }
        
        $finalRequired = $schemaAttribute?->required ?: $inferredRequired;

        return [
            'type' => $schemaAttribute?->type ?: 'object',
            'title' => $schemaAttribute?->title ?: class_basename($model->class),
            'description' => $schemaAttribute?->description ?: $this->generateModelDescription($model->class, $schemaAttribute),
            'properties' => $properties,
            'required' => array_values(array_unique($finalRequired)),
        ];
    }

    /**
     * Builds an OpenAPI property schema from a Property attribute.
     *
     * @param PropertyAttribute $propertyAttribute The Property attribute instance.
     * @return array The OpenAPI property schema.
     */
    private function buildSchemaFromPropertyAttribute(PropertyAttribute $propertyAttribute): array
    {
        $propertySchema = [];

        // Check for reference first
        if ($propertyAttribute->ref) {
            return ['$ref' => $propertyAttribute->ref];
        }

        // Required OpenAPI fields (even if null in attribute, provide key if applicable)
        // Type is not strictly required by OpenAPI spec for a property if using oneOf, anyOf etc.
        // but for a basic property, it's fundamental.
        if ($propertyAttribute->type) {
            $propertySchema['type'] = $propertyAttribute->type;
        } else {
            // Default to 'string' if not specified, or handle as error, or make it configurable.
            // For now, let's default to string as it's a common scenario.
            $propertySchema['type'] = 'string'; 
        }

        // Optional OpenAPI fields, only add if value is not null/empty
        if ($propertyAttribute->description) $propertySchema['description'] = $propertyAttribute->description;
        if ($propertyAttribute->format) $propertySchema['format'] = $propertyAttribute->format;
        
        // Examples & Example
        if ($propertyAttribute->example !== null) $propertySchema['example'] = $propertyAttribute->example; // Allow any type for example
        if (!empty($propertyAttribute->examples)) $propertySchema['examples'] = $propertyAttribute->examples; // examples should be an array of Example Objects or values

        // Nullable: In OpenAPI 3.0, nullable is a boolean.
        // If $propertyAttribute->nullable is true, set 'nullable: true'.
        // If false, it's the default, so we can omit it or explicitly set 'nullable: false'.
        // Let's be explicit for clarity if it's true.
        if ($propertyAttribute->nullable) $propertySchema['nullable'] = true;
        // If type is "object" or "array", nullable is handled differently (not as a boolean sibling to type).
        // However, for scalar types, this is fine. The Property attribute has a boolean nullable.

        if ($propertyAttribute->default !== null) $propertySchema['default'] = $propertyAttribute->default;
        
        // Constraints
        if ($propertyAttribute->minLength !== null) $propertySchema['minLength'] = $propertyAttribute->minLength;
        if ($propertyAttribute->maxLength !== null) $propertySchema['maxLength'] = $propertyAttribute->maxLength;
        if ($propertyAttribute->minimum !== null) $propertySchema['minimum'] = $propertyAttribute->minimum;
        if ($propertyAttribute->maximum !== null) $propertySchema['maximum'] = $propertyAttribute->maximum;
        if (!empty($propertyAttribute->enum)) $propertySchema['enum'] = $propertyAttribute->enum;
        
        // Handle array items
        if ($propertyAttribute->type === 'array' && $propertyAttribute->items) {
            $propertySchema['items'] = $this->processItems($propertyAttribute->items);
        }
        
        // Handle nested properties for object type
        if ($propertyAttribute->type === 'object' && !empty($propertyAttribute->properties)) {
            $propertySchema['properties'] = [];
            foreach ($propertyAttribute->properties as $nestedProperty) {
                if ($nestedProperty instanceof PropertyAttribute) {
                    $propertySchema['properties'][$nestedProperty->property] = $this->buildSchemaFromPropertyAttribute($nestedProperty);
                }
            }
        }
        
        // Add other relevant properties from your PropertyAttribute definition as needed
        // e.g., pattern, uniqueItems, readOnly, writeOnly etc.

        return $propertySchema;
    }
    
    /**
     * Process items for array properties
     *
     * @param \LaravelOpenApi\Attributes\Items $items
     * @return array
     */
    private function processItems(\LaravelOpenApi\Attributes\Items $items): array
    {
        if ($items->ref) {
            return ['$ref' => $items->ref];
        }
        
        $result = [];
        
        // Add basic properties
        if ($items->type) $result['type'] = $items->type;
        if ($items->format) $result['format'] = $items->format;
        if ($items->example !== null) $result['example'] = $items->example;
        if ($items->default !== null) $result['default'] = $items->default;
        if ($items->minimum !== null) $result['minimum'] = $items->minimum;
        if ($items->maximum !== null) $result['maximum'] = $items->maximum;
        if ($items->nullable !== null) $result['nullable'] = $items->nullable;
        if (!empty($items->enum)) $result['enum'] = $items->enum;
        
        return $result;
    }

    /**
     * Infer property schema from model metadata
     *
     * @param string $propertyName The name of the property
     * @param ModelSchema $model The model schema
     * @return array The inferred property schema
     */
    private function inferPropertySchema(string $propertyName, ModelSchema $model): array
    {
        $schema = [
            'type' => 'string', // Default type
            'description' => ucfirst(str_replace('_', ' ', $propertyName)),
        ];
        
        // Check if property is in casts to determine type
        if (isset($model->casts[$propertyName])) {
            $castType = $model->casts[$propertyName];
            
            switch ($castType) {
                case 'int':
                case 'integer':
                    $schema['type'] = 'integer';
                    break;
                    
                case 'real':
                case 'float':
                case 'double':
                case 'decimal':
                    $schema['type'] = 'number';
                    $schema['format'] = 'float';
                    break;
                    
                case 'bool':
                case 'boolean':
                    $schema['type'] = 'boolean';
                    break;
                    
                case 'array':
                case 'json':
                case 'collection':
                    $schema['type'] = 'array';
                    $schema['items'] = ['type' => 'string'];
                    break;
                    
                case 'object':
                    $schema['type'] = 'object';
                    break;
                    
                case 'date':
                    $schema['type'] = 'string';
                    $schema['format'] = 'date';
                    break;
                    
                case 'datetime':
                case 'timestamp':
                    $schema['type'] = 'string';
                    $schema['format'] = 'date-time';
                    break;
            }
        }
        
        // Special handling for common field names
        if ($propertyName === 'id') {
            $schema['type'] = 'integer';
            $schema['format'] = 'int64';
            $schema['description'] = 'Unique identifier';
            $schema['readOnly'] = true;
        } else if ($propertyName === 'created_at' || $propertyName === 'updated_at') {
            $schema['type'] = 'string';
            $schema['format'] = 'date-time';
            $schema['readOnly'] = true;
        } else if (str_ends_with($propertyName, '_id')) {
            $schema['type'] = 'integer';
            $schema['format'] = 'int64';
            $schema['description'] = 'Foreign key to ' . str_replace('_id', '', $propertyName);
        } else if (str_ends_with($propertyName, '_at')) {
            $schema['type'] = 'string';
            $schema['format'] = 'date-time';
        }
        
        // Add example values for common fields
        if ($propertyName === 'id') {
            $schema['example'] = 1;
        } else if ($propertyName === 'name') {
            $schema['example'] = 'Example ' . class_basename($model->class);
        } else if ($propertyName === 'email') {
            $schema['example'] = 'user@example.com';
            $schema['format'] = 'email';
        } else if ($propertyName === 'created_at' || $propertyName === 'updated_at') {
            $schema['example'] = '2023-01-01T12:00:00Z';
        }
        
        return $schema;
    }
    
    private function generateModelDescription(string $modelClassName, ?SchemaAttribute $schemaAttribute): string
    {
        if ($schemaAttribute && !empty($schemaAttribute->description)) {
            return $schemaAttribute->description;
        }
        return class_basename($modelClassName) . ' model definition.';
    }
}
