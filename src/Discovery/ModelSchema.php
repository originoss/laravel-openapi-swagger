<?php

namespace LaravelOpenApi\Discovery;

class ModelSchema
{
    public function __construct(
        public string $class,
        public string $table,
        public array $attributes, // Modified: Now holds ['class' => [...], 'properties' => [...]]
        public array $relationships,
        public array $casts,
        public array $fillable,
        public array $hidden
    ) {}
}
