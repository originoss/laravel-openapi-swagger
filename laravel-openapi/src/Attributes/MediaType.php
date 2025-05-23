<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_ALL)]
class MediaType
{
    public function __construct(
        public string $mediaType,
        public mixed $schema = null,
        public array $examples = [],
        public mixed $example = null,
        public array $encoding = []
    ) {}
}
