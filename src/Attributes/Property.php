<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Property
{
    public function __construct(
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
    ) {}
}
