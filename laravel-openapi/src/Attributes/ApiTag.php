<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ApiTag
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $externalDocs = null
    ) {}
}
