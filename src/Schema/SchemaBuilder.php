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
                // *** This is the line to change ***
                $properties[$propertyName] = $this->buildSchemaFromPropertyAttribute($propertyAttrInstance);

                if (!$propertyAttrInstance->nullable) {
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
        
        // Add other relevant properties from your PropertyAttribute definition as needed
        // e.g., pattern, uniqueItems, readOnly, writeOnly etc.

        return $propertySchema;
    }

    private function generateModelDescription(string $modelClassName, ?SchemaAttribute $schemaAttribute): string
    {
        if ($schemaAttribute && !empty($schemaAttribute->description)) {
            return $schemaAttribute->description;
        }
        return class_basename($modelClassName) . ' model definition.';
    }
}
