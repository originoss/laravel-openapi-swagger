<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_ALL)]
class Items
{
    /**
     * @param string|null $type Data type (string, number, integer, boolean, array, object)
     * @param string|null $format Format (e.g., date-time, email, uuid)
     * @param string|null $ref Reference to another schema
     * @param mixed $default Default value
     * @param mixed $example Example value
     * @param array $examples Multiple examples
     * @param mixed $minimum Minimum value for numeric types
     * @param mixed $maximum Maximum value for numeric types
     * @param bool|null $nullable Whether the item can be null
     * @param array $enum Enumerated values
     * @param int|null $minLength Minimum string length
     * @param int|null $maxLength Maximum string length
     */
    public function __construct(
        public ?string $type = null,
        public ?string $format = null,
        public ?string $ref = null,
        public mixed $default = null,
        public mixed $example = null,
        public array $examples = [],
        public mixed $minimum = null,
        public mixed $maximum = null,
        public ?bool $nullable = null,
        public array $enum = [],
        public ?int $minLength = null,
        public ?int $maxLength = null
    ) {}
}
