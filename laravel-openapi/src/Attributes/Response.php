<?php

namespace LaravelOpenApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Response
{
    /**
     * @var int|string HTTP status code or 'default'
     */
    public int|string $status = 200;
    
    /**
     * @var mixed Content object or array of MediaType objects
     */
    public mixed $content = null;
    
    /**
     * @var string|null Response description
     */
    public ?string $description = null;
    
    /**
     * @var array Response headers
     */
    public array $headers = [];
    
    /**
     * @var array Response examples
     */
    public array $examples = [];
    
    /**
     * @var string|null Reference to a response definition
     */
    public ?string $ref = null;
    
    /**
     * @var array Properties for inline schema definition
     */
    public array $properties = [];
    
    /**
     * Constructor for Response attribute
     * 
     * @param int|string|null $status HTTP status code or 'default'
     * @param mixed $content Content object or array of MediaType objects
     * @param string|null $description Response description
     * @param array $headers Response headers
     * @param array $examples Response examples
     * @param string|null $ref Reference to a response definition
     * @param array $properties Properties for inline schema definition
     * @param int|string|null $response Alias for status (for backward compatibility)
     */
    public function __construct(
        int|string|null $status = 200,
        mixed $content = null,
        ?string $description = null,
        array $headers = [],
        array $examples = [],
        ?string $ref = null,
        array $properties = [],
        int|string|null $response = null,
    ) {
        // Use response parameter if provided (for backward compatibility)
        if ($response !== null) {
            $this->status = $response;
        } else {
            $this->status = $status ?? 200;
        }
        
        $this->content = $content;
        $this->description = $description;
        $this->headers = $headers;
        $this->examples = $examples;
        $this->ref = $ref;
        $this->properties = $properties;
    }
}
