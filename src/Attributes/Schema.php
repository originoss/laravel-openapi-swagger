<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Schema
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $type = 'object',
        public array $required = [],
        public array $examples = [],
    ) {}
}
