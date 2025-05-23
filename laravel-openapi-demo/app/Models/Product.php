<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelOpenApi\Attributes as OA;

#[OA\Schema(
    title: 'Product',
    description: 'Represents a product in the store.',
    required: ['id', 'name', 'price']
)]
#[OA\Property(
    property: 'id',
    description: 'Unique identifier for the product.',
    type: 'integer',
    format: 'int64',
    example: 1,
    readOnly: true
)]
#[OA\Property(
    property: 'name',
    description: 'Name of the product.',
    type: 'string',
    example: 'Smartphone XYZ',
    maxLength: 255
)]
#[OA\Property(
    property: 'description',
    description: 'Detailed description of the product.',
    type: 'string',
    example: 'The latest smartphone with amazing features',
    nullable: true
)]
#[OA\Property(
    property: 'price',
    description: 'Price of the product in USD.',
    type: 'number',
    format: 'float',
    example: 999.99,
    minimum: 0.01
)]
#[OA\Property(
    property: 'category',
    description: 'Category of the product.',
    type: 'string',
    example: 'Electronics',
    nullable: true
)]
#[OA\Property(
    property: 'in_stock',
    description: 'Whether the product is in stock.',
    type: 'boolean',
    example: true,
    default: true
)]
#[OA\Property(
    property: 'specifications',
    description: 'Technical specifications of the product.',
    type: 'object',
    nullable: true,
    properties: [
        new OA\Property(property: 'weight', type: 'string', example: '200g'),
        new OA\Property(property: 'dimensions', type: 'string', example: '15 x 7 x 0.8 cm'),
        new OA\Property(property: 'color', type: 'string', example: 'Black')
    ]
)]
#[OA\Property(
    property: 'images',
    description: 'Product images.',
    type: 'array',
    nullable: true,
    items: new OA\Items(
        type: 'object',
        properties: [
            new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://example.com/images/product1.jpg'),
            new OA\Property(property: 'is_primary', type: 'boolean', example: true)
        ]
    )
)]
#[OA\Property(
    property: 'created_at',
    description: 'Timestamp when the product was created.',
    type: 'string',
    format: 'date-time',
    example: '2023-01-01T12:00:00Z',
    readOnly: true
)]
#[OA\Property(
    property: 'updated_at',
    description: 'Timestamp when the product was last updated.',
    type: 'string',
    format: 'date-time',
    example: '2023-01-01T13:30:00Z',
    readOnly: true
)]
class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'in_stock',
        'specifications',
        'images'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'in_stock' => 'boolean',
        'specifications' => 'array',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
