<?php

namespace Vyuldashev\LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RequestBody
{
    public function __construct(
        public mixed $content = null,
        public ?string $description = null,
        public bool $required = true,
        public array $examples = [],
    ) {}
}
