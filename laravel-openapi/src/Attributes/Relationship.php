<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Relationship
{
    public function __construct(
        public string $type, // 'hasOne', 'hasMany', 'belongsTo', etc.
        public string $related,
        public bool $nullable = false,
    ) {}
}
