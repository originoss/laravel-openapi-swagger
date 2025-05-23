<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_ALL)]
class Items
{
    public function __construct(
        public ?string $type = null,
        public ?string $format = null,
        public ?string $ref = null,
        public mixed $default = null,
        public mixed $example = null,
        public mixed $minimum = null,
        public mixed $maximum = null,
        public ?bool $nullable = null,
        public ?array $enum = null
    ) {}
}
