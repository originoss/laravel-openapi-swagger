<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Hidden
{
    // Exclude from schema generation
}
