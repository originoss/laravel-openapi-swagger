<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Schema
{
    /**
     * @var string|null Schema title
     */
    public ?string $title = null;
    
    /**
     * @var string|null Schema description
     */
    public ?string $description = null;
    
    /**
     * @var string|null Data type (string, number, integer, boolean, array, object)
     */
    public ?string $type = 'object';
    
    /**
     * @var array List of required properties
     */
    public array $required = [];
    
    /**
     * @var array Example values
     */
    public array $examples = [];
    
    /**
     * @var array Enumerated values
     */
    public array $enum = [];
    
    /**
     * @var string|null Reference to another schema
     */
    public ?string $ref = null;
    
    /**
     * @var string|null Format (e.g., date-time, email, uuid)
     */
    public ?string $format = null;
    
    /**
     * @var bool|null Whether the schema can be null
     */
    public ?bool $nullable = null;
    
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
     * @var array For object types, nested properties
     */
    public array $properties = [];
    
    /**
     * Constructor for Schema attribute
     * 
     * @param string|null $title Schema title
     * @param string|null $description Schema description
     * @param string|null $type Data type (string, number, integer, boolean, array, object)
     * @param array $required List of required properties
     * @param array $examples Example values
     * @param array $enum Enumerated values
     * @param string|null $ref Reference to another schema
     * @param string|null $format Format (e.g., date-time, email, uuid)
     * @param bool|null $nullable Whether the schema can be null
     * @param mixed $default Default value
     * @param int|null $minLength Minimum string length
     * @param int|null $maxLength Maximum string length
     * @param mixed $minimum Minimum value for numeric types
     * @param mixed $maximum Maximum value for numeric types
     * @param array $properties For object types, nested properties
     */
    public function __construct(
        ?string $title = null,
        ?string $description = null,
        ?string $type = 'object',
        array $required = [],
        array $examples = [],
        array $enum = [],
        ?string $ref = null,
        ?string $format = null,
        ?bool $nullable = null,
        mixed $default = null,
        ?int $minLength = null,
        ?int $maxLength = null,
        mixed $minimum = null,
        mixed $maximum = null,
        array $properties = [],
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->type = $type;
        $this->required = $required;
        $this->examples = $examples;
        $this->enum = $enum;
        $this->ref = $ref;
        $this->format = $format;
        $this->nullable = $nullable;
        $this->default = $default;
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->minimum = $minimum;
        $this->maximum = $maximum;
        
        $this->properties = $properties;
    }
}
