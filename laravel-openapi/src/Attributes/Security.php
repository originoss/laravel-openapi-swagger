<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Security
{
    public function __construct(
        public array $schemes = [],
        public array $scopes = [],
    ) {}
}
