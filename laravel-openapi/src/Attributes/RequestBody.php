<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RequestBody
{
    /**
     * @param mixed $content Content object or array of MediaType objects
     * @param string|null $description Request body description
     * @param bool $required Whether the request body is required
     * @param array $examples Request body examples
     * @param string|null $ref Reference to a request body definition
     */
    public function __construct(
        public mixed $content = null,
        public ?string $description = null,
        public bool $required = true,
        public array $examples = [],
        public ?string $ref = null,
    ) {}
}
