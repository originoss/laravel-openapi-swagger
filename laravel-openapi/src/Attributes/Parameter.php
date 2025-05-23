<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Parameter
{
    public function __construct(
        public string $name,
        public string $in, // 'query', 'header', 'path', 'cookie'
        public ?string $description = null,
        public mixed $schema = null,
        public bool $required = false,
        public mixed $example = null,
        public array $examples = [],
    ) {}
}
