<?php

namespace LaravelOpenApi\Schema;

// Import the DatabaseColumn DTO
use LaravelOpenApi\Schema\DatabaseColumn;
use LaravelOpenApi\Discovery\ModelSchema; // Add this
use LaravelOpenApi\Attributes\Schema as OpenApiSchemaAttribute; // Add this with alias

class SchemaBuilder
{
    // Constructor might be needed later for config or other dependencies
    // public function __construct() {}

    /**
     * Builds an OpenAPI property schema from a database column definition.
     *
     * @param DatabaseColumn $column
     * @return array
     */
    public function buildPropertyFromColumn(DatabaseColumn $column): array
    {
        $property = match(strtolower($column->type)) { // Use strtolower for case-insensitivity
            'string', 'varchar', 'text', 'char' => [ // Added char
                'type' => 'string',
                'maxLength' => $column->length,
            ],
            'integer', 'int', 'smallint', 'mediumint', 'tinyint' => [ // Added more int types
                'type' => 'integer',
                'format' => 'int32', // Default to int32, can be overridden
            ],
            'bigint' => [
                'type' => 'integer',
                'format' => 'int64',
            ],
            'decimal', 'numeric' => [ // Added numeric
                'type' => 'number',
                'format' => 'double', // Or choose a more specific format if available
            ],
            'float' => [
                'type' => 'number',
                'format' => 'float',
            ],
            'double' => [
                'type' => 'number',
                'format' => 'double',
            ],
            'boolean', 'bool' => ['type' => 'boolean'], // Added bool
            'date' => ['type' => 'string', 'format' => 'date'],
            'datetime', 'timestamp' => ['type' => 'string', 'format' => 'date-time'],
            'json', 'jsonb' => ['type' => 'object', 'additionalProperties' => true], // Added jsonb
            'enum', 'set' => ['type' => 'string', 'enum' => []], // Placeholder for actual enum values
            default => ['type' => 'string'] // Fallback for unknown types
        };

        if ($column->nullable) {
            $property['nullable'] = true;
        }
        
        // Remove maxLength if null, as it's not valid OpenAPI if not set.
        if (isset($property['maxLength']) && is_null($property['maxLength'])) {
            unset($property['maxLength']);
        }

        return $property;
    }

    // buildModelSchema will be added in the next step

    /**
     * Builds an OpenAPI schema for a given model.
     * (Partial implementation focusing on columns for now)
     *
     * @param ModelSchema $model The model schema DTO from discovery.
     * @return array The OpenAPI schema definition for the model.
     */
    public function buildModelSchema(ModelSchema $model): array
    {
        $properties = [];
        $required = [];

        // ---- Database Columns (Mocked for now) ----
        // TODO: Replace this with actual database schema inspection
        $mockColumns = $this->getMockDatabaseColumns($model->table, $model->casts);
        
        foreach ($mockColumns as $column) {
            $propertySchema = $this->buildPropertyFromColumn($column);
            $properties[$column->name] = $propertySchema;

            if (!$column->nullable && !$column->hasDefault) {
                $required[] = $column->name;
            }
        }
        // ---- End Database Columns ----

        // ---- Relationships (Placeholder) ----
        // TODO: Implement relationship processing based on $model->relationships
        // foreach ($model->relationships as $relationship) {
        //     $properties[$relationship->name] = $this->buildRelationshipProperty($relationship);
        // }
        // ---- End Relationships ----

        // ---- Attribute Overrides (Placeholder) ----
        // TODO: Implement attribute overrides processing using $model->attributes
        // $this->applyAttributeOverrides($properties, $model->attributes);
        // ---- End Attribute Overrides ----

        return [
            'type' => 'object',
            'title' => class_basename($model->class), // Using class_basename for a cleaner title
            'description' => $this->generateModelDescription($model), // Optional: Generate a basic description
            'properties' => $properties,
            'required' => array_values(array_unique($required)), // Ensure unique and re-index
        ];
    }

    /**
     * MOCK IMPLEMENTATION: Generates dummy database columns for a table.
     * Replace with actual DB schema reading (e.g., from Doctrine DBAL).
     */
    private function getMockDatabaseColumns(string $tableName, array $casts): array
    {
        $uniqueColumns = []; // Use this to track columns and avoid duplicates by name

        // Example: Add an ID column for any table
        $uniqueColumns['id'] = new DatabaseColumn(name: 'id', type: 'bigint', nullable: false, hasDefault: false);

        // Add some common fields based on table name or conventions
        if ($tableName === 'users') { // Example for a 'users' table
            $uniqueColumns['name'] = new DatabaseColumn(name: 'name', type: 'string', length: 255, nullable: false);
            $uniqueColumns['email'] = new DatabaseColumn(name: 'email', type: 'string', length: 255, nullable: false);
            $uniqueColumns['email_verified_at'] = new DatabaseColumn(name: 'email_verified_at', type: 'timestamp', nullable: true);
            $uniqueColumns['password'] = new DatabaseColumn(name: 'password', type: 'string', length: 255, nullable: false);
            $uniqueColumns['remember_token'] = new DatabaseColumn(name: 'remember_token', type: 'string', length: 100, nullable: true);
        }

        // Add timestamp columns if not already added by specific table logic
        if (!isset($uniqueColumns['created_at'])) {
            $uniqueColumns['created_at'] = new DatabaseColumn(name: 'created_at', type: 'timestamp', nullable: true);
        }
        if (!isset($uniqueColumns['updated_at'])) {
            $uniqueColumns['updated_at'] = new DatabaseColumn(name: 'updated_at', type: 'timestamp', nullable: true);
        }
        
        // Infer types from casts if possible (simplified)
        foreach($casts as $fieldName => $castType) {
            if (!isset($uniqueColumns[$fieldName])) { // Only if not already defined by common fields
                 $dbType = match(explode(':', $castType)[0]) { // explode for casts like 'decimal:2'
                    'int', 'integer' => 'integer',
                    'real', 'float', 'double', 'decimal' => 'float', // Assuming float for simplicity
                    'string' => 'string',
                    'bool', 'boolean' => 'boolean',
                    'date', 'datetime', 'custom_datetime', 'timestamp' => 'datetime',
                    'array', 'json', 'object', 'collection' => 'json',
                    default => null, // Unknown cast
                 };
                 if ($dbType) {
                    // Add to uniqueColumns to ensure it's not overridden by a later, less specific definition
                    $uniqueColumns[$fieldName] = new DatabaseColumn(name: $fieldName, type: $dbType, nullable: true); // Assume nullable for cast fields
                 }
            }
        }
        
        return array_values($uniqueColumns); // Return the values, which are the DatabaseColumn objects
    }
    
    /**
     * Generates a basic model description from its class name or attributes.
     */
    private function generateModelDescription(ModelSchema $model): string
    {
        // Check for a #[Schema(description: "...")] attribute first
        $classAttributes = $model->attributes['class'] ?? [];
        foreach ($classAttributes as $attribute) {
            // Use the aliased import here
            if ($attribute instanceof OpenApiSchemaAttribute && !empty($attribute->description)) {
                return $attribute->description;
            }
        }
        return class_basename($model->class) . ' model.';
    }
}
