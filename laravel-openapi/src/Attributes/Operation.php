<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Operation
{
    public function __construct(
        public ?string $summary = null,
        public ?string $description = null,
        public array $tags = [],
        public ?string $operationId = null,
        public bool $deprecated = false,
        public array $security = [],
    ) {}
}
