<?php

namespace Vyuldashev\LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Response
{
    public function __construct(
        public int|string $status,
        public mixed $content = null,
        public ?string $description = null,
        public array $headers = [],
        public array $examples = [],
    ) {}
}
