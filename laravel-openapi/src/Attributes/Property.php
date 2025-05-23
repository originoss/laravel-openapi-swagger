<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_ALL | Attribute::IS_REPEATABLE)]
class Property
{
    /**
     * @var string The name of the property
     */
    public string $property = 'unnamed';
    
    /**
     * @var string|null Property description
     */
    public ?string $description = null;
    
    /**
     * @var string|null Data type (string, number, integer, boolean, array, object)
     */
    public ?string $type = null;
    
    /**
     * @var string|null Format (e.g., date-time, email, uuid)
     */
    public ?string $format = null;
    
    /**
     * @var mixed Example value
     */
    public mixed $example = null;
    
    /**
     * @var array Multiple examples
     */
    public array $examples = [];
    
    /**
     * @var bool Whether the property can be null
     */
    public bool $nullable = false;
    
    /**
     * @var mixed Default value
     */
    public mixed $default = null;
    
    /**
     * @var int|null Minimum string length
     */
    public ?int $minLength = null;
    
    /**
     * @var int|null Maximum string length
     */
    public ?int $maxLength = null;
    
    /**
     * @var mixed Minimum value for numeric types
     */
    public mixed $minimum = null;
    
    /**
     * @var mixed Maximum value for numeric types
     */
    public mixed $maximum = null;
    
    /**
     * @var array Enumerated values
     */
    public array $enum = [];
    
    /**
     * @var Items|null For array types, describes the array items
     */
    public ?Items $items = null;
    
    /**
     * @var array For object types, nested properties
     */
    public array $properties = [];
    
    /**
     * @var bool Whether the property is required
     */
    public bool $required = false;
    
    /**
     * @var string|null Reference to a schema definition
     */
    public ?string $ref = null;
    
    /**
     * @var bool|null Whether the property is read-only
     */
    public ?bool $readOnly = null;
    
    /**
     * @var bool|null Whether the property is write-only
     */
    public ?bool $writeOnly = null;
    
    /**
     * Constructor for Property attribute
     * 
     * @param string|null $property The name of the property
     * @param string|null $description Property description
     * @param string|null $type Data type (string, number, integer, boolean, array, object)
     * @param string|null $format Format (e.g., date-time, email, uuid)
     * @param mixed $example Example value
     * @param array $examples Multiple examples
     * @param bool $nullable Whether the property can be null
     * @param mixed $default Default value
     * @param int|null $minLength Minimum string length
     * @param int|null $maxLength Maximum string length
     * @param mixed $minimum Minimum value for numeric types
     * @param mixed $maximum Maximum value for numeric types
     * @param array $enum Enumerated values
     * @param Items|null $items For array types, describes the array items
     * @param array $properties For object types, nested properties
     * @param bool $required Whether the property is required
     * @param string|null $ref Reference to a schema definition
     * @param bool|null $readOnly Whether the property is read-only
     * @param bool|null $writeOnly Whether the property is write-only
     */
    public function __construct(
        ?string $property = null,
        ?string $description = null,
        ?string $type = null,
        ?string $format = null,
        mixed $example = null,
        array $examples = [],
        bool $nullable = false,
        mixed $default = null,
        ?int $minLength = null,
        ?int $maxLength = null,
        mixed $minimum = null,
        mixed $maximum = null,
        array $enum = [],
        ?Items $items = null,
        array $properties = [],
        bool $required = false,
        ?string $ref = null,
        ?bool $readOnly = null,
        ?bool $writeOnly = null,
        // Named parameters for backward compatibility
        array $properties_param = [],
    ) {
        // Set property name if provided
        if ($property !== null) {
            $this->property = $property;
        }
        
        $this->description = $description;
        $this->type = $type;
        $this->format = $format;
        $this->example = $example;
        $this->examples = $examples;
        $this->nullable = $nullable;
        $this->default = $default;
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->minimum = $minimum;
        $this->maximum = $maximum;
        $this->enum = $enum;
        $this->items = $items;
        
        // Handle properties parameter - use properties_param if provided, otherwise use properties
        if (!empty($properties_param)) {
            $this->properties = $properties_param;
        } else {
            $this->properties = $properties;
        }
        
        $this->required = $required;
        $this->ref = $ref;
        $this->readOnly = $readOnly;
        $this->writeOnly = $writeOnly;
        
        // If property is unnamed but we have a ref, use the ref as a base for the property name
        if ($this->property === 'unnamed' && $this->ref !== null) {
            // Extract class name without namespace if it's a class reference
            if (class_exists($this->ref)) {
                $parts = explode('\\', $this->ref);
                $className = end($parts);
                $this->property = lcfirst($className);
            } else {
                // Extract the last part of the reference path
                $parts = explode('/', $this->ref);
                $this->property = end($parts);
            }
        }
    }
}
