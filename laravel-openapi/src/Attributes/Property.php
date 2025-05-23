<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_ALL | Attribute::IS_REPEATABLE)]
class Property
{
    /**
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
     */
    public function __construct(
        public string $property,
        public ?string $description = null,
        public ?string $type = null,
        public ?string $format = null,
        public mixed $example = null,
        public array $examples = [],
        public bool $nullable = false,
        public mixed $default = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public mixed $minimum = null,
        public mixed $maximum = null,
        public array $enum = [],
        public ?Items $items = null,
        public array $properties = [],
        public bool $required = false,
        public ?string $ref = null,
    ) {}
}
