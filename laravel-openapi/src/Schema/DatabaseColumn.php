<?php

namespace LaravelOpenApi\Schema;

// This is a simple Data Transfer Object (DTO) to represent database column information.
// In a real scenario, this might come from Doctrine DBAL or similar.
class DatabaseColumn
{
    public function __construct(
        public string $name,
        public string $type, // e.g., 'string', 'integer', 'text', 'bigint', 'decimal', 'float', 'boolean', 'date', 'datetime', 'json'
        public ?int $length = null,
        public bool $nullable = false,
        public mixed $default = null, // Could be string, int, bool etc.
        public bool $hasDefault = false // Helper to distinguish null default from no default
    ) {}
}
