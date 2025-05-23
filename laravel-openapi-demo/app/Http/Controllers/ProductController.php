<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use LaravelOpenApi\Attributes as OA;

#[OA\ApiTag(
    name: 'Products',
    description: 'Product management endpoints',
    externalDocs: 'https://example.com/docs/products'
)]
class ProductController extends Controller
{
    /**
     * List all products with filtering, sorting, and pagination
     */
    #[OA\Operation(
        path: '/api/products',
        method: 'get',
        summary: 'List all products',
        description: 'Retrieves a paginated list of products with optional filtering and sorting.'
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'Page number for pagination.',
        required: false,
        schema: new OA\Schema(type: 'integer', example: 1, default: 1)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Number of products per page.',
        required: false,
        schema: new OA\Schema(type: 'integer', example: 15, default: 15)
    )]
    #[OA\Parameter(
        name: 'category',
        in: 'query',
        description: 'Filter products by category.',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'price_min',
        in: 'query',
        description: 'Filter products by minimum price.',
        required: false,
        schema: new OA\Schema(type: 'number', format: 'float')
    )]
    #[OA\Parameter(
        name: 'price_max',
        in: 'query',
        description: 'Filter products by maximum price.',
        required: false,
        schema: new OA\Schema(type: 'number', format: 'float')
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query',
        description: 'Sort products by field (price, name, created_at).',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['price', 'name', 'created_at'])
    )]
    #[OA\Parameter(
        name: 'direction',
        in: 'query',
        description: 'Sort direction (asc or desc).',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'asc')
    )]
    #[OA\Response(
        response: 200,
        description: 'A paginated list of products.',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Product Name'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Product description text'),
                                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                                    new OA\Property(property: 'category', type: 'string', example: 'Electronics'),
                                    new OA\Property(property: 'in_stock', type: 'boolean', example: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time')
                                ]
                            )
                        ),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 5),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'total', type: 'integer', example: 75)
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        // Implementation would go here
        return response()->json([
            'data' => [],
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 15,
            'total' => 0
        ]);
    }

    /**
     * Get a specific product by ID
     */
    #[OA\Operation(
        path: '/api/products/{id}',
        method: 'get',
        summary: 'Get a specific product',
        description: 'Retrieves detailed information about a specific product by its ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the product to retrieve.',
        required: true,
        schema: new OA\Schema(type: 'integer', format: 'int64')
    )]
    #[OA\Response(
        response: 200,
        description: 'Detailed product information.',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Product Name'),
                        new OA\Property(property: 'description', type: 'string', example: 'Detailed product description'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                        new OA\Property(property: 'category', type: 'string', example: 'Electronics'),
                        new OA\Property(property: 'in_stock', type: 'boolean', example: true),
                        new OA\Property(property: 'specifications', type: 'object', properties: [
                            new OA\Property(property: 'weight', type: 'string', example: '1.5kg'),
                            new OA\Property(property: 'dimensions', type: 'string', example: '10 x 20 x 5 cm'),
                            new OA\Property(property: 'color', type: 'string', example: 'Black')
                        ]),
                        new OA\Property(property: 'images', type: 'array', items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://example.com/images/product1.jpg'),
                                new OA\Property(property: 'is_primary', type: 'boolean', example: true)
                            ]
                        )),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time')
                    ]
                )
            )
        ]
    )]
    #[OA\Response(
        response: 404,
        description: 'Product not found.',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Product not found')
                    ]
                )
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        // Implementation would go here
        return response()->json([
            'id' => $id,
            'name' => 'Example Product',
            'description' => 'This is an example product',
            'price' => 99.99,
            'category' => 'Example',
            'in_stock' => true,
            'specifications' => [
                'weight' => '1.5kg',
                'dimensions' => '10 x 20 x 5 cm',
                'color' => 'Black'
            ],
            'images' => [
                [
                    'url' => 'https://example.com/images/product1.jpg',
                    'is_primary' => true
                ]
            ],
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Create a new product
     */
    #[OA\Operation(
        path: '/api/products',
        method: 'post',
        summary: 'Create a new product',
        description: 'Creates a new product with the provided information.'
    )]
    #[OA\RequestBody(
        description: 'Product information',
        required: true,
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['name', 'price', 'category'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', description: 'Product name', minLength: 2, maxLength: 255),
                        new OA\Property(property: 'description', type: 'string', description: 'Product description', nullable: true),
                        new OA\Property(property: 'price', type: 'number', format: 'float', description: 'Product price', minimum: 0.01),
                        new OA\Property(property: 'category', type: 'string', description: 'Product category'),
                        new OA\Property(property: 'in_stock', type: 'boolean', description: 'Whether the product is in stock', default: true),
                        new OA\Property(
                            property: 'specifications',
                            type: 'object',
                            description: 'Product specifications',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'weight', type: 'string', nullable: true),
                                new OA\Property(property: 'dimensions', type: 'string', nullable: true),
                                new OA\Property(property: 'color', type: 'string', nullable: true)
                            ]
                        ),
                        new OA\Property(
                            property: 'images',
                            type: 'array',
                            description: 'Product images',
                            nullable: true,
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'url', type: 'string', format: 'uri'),
                                    new OA\Property(property: 'is_primary', type: 'boolean', default: false)
                                ]
                            )
                        )
                    ]
                ),
                example: [
                    'name' => 'New Product',
                    'description' => 'This is a new product',
                    'price' => 149.99,
                    'category' => 'Electronics',
                    'in_stock' => true,
                    'specifications' => [
                        'weight' => '2kg',
                        'dimensions' => '15 x 25 x 8 cm',
                        'color' => 'Silver'
                    ],
                    'images' => [
                        [
                            'url' => 'https://example.com/images/newproduct.jpg',
                            'is_primary' => true
                        ]
                    ]
                ]
            )
        ]
    )]
    #[OA\Response(
        response: 201,
        description: 'Product created successfully.',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'New Product'),
                        new OA\Property(property: 'description', type: 'string', example: 'This is a new product'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 149.99),
                        new OA\Property(property: 'category', type: 'string', example: 'Electronics'),
                        new OA\Property(property: 'in_stock', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time')
                    ]
                )
            )
        ]
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error.',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'name', type: 'array', items: new OA\Items(type: 'string')),
                                new OA\Property(property: 'price', type: 'array', items: new OA\Items(type: 'string')),
                                new OA\Property(property: 'category', type: 'array', items: new OA\Items(type: 'string'))
                            ]
                        )
                    ]
                ),
                example: [
                    'errors' => [
                        'name' => ['The name field is required.'],
                        'price' => ['The price must be at least 0.01.'],
                        'category' => ['The category field is required.']
                    ]
                ]
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        // Implementation would go here
        return response()->json([
            'id' => 1,
            'name' => $request->input('name', 'New Product'),
            'description' => $request->input('description', 'This is a new product'),
            'price' => $request->input('price', 149.99),
            'category' => $request->input('category', 'Electronics'),
            'in_stock' => $request->input('in_stock', true),
            'created_at' => now(),
            'updated_at' => now()
        ], 201);
    }

    /**
     * Update an existing product
     */
    #[OA\Operation(
        path: '/api/products/{id}',
        method: 'put',
        summary: 'Update an existing product',
        description: 'Updates an existing product with the provided information.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the product to update.',
        required: true,
        schema: new OA\Schema(type: 'integer', format: 'int64')
    )]
    #[OA\RequestBody(
        description: 'Updated product information',
        required: true,
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'name', type: 'string', description: 'Product name', minLength: 2, maxLength: 255),
                        new OA\Property(property: 'description', type: 'string', description: 'Product description', nullable: true),
                        new OA\Property(property: 'price', type: 'number', format: 'float', description: 'Product price', minimum: 0.01),
                        new OA\Property(property: 'category', type: 'string', description: 'Product category'),
                        new OA\Property(property: 'in_stock', type: 'boolean', description: 'Whether the product is in stock'),
                        new OA\Property(
                            property: 'specifications',
                            type: 'object',
                            description: 'Product specifications',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'weight', type: 'string', nullable: true),
                                new OA\Property(property: 'dimensions', type: 'string', nullable: true),
                                new OA\Property(property: 'color', type: 'string', nullable: true)
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Product updated successfully.',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Updated Product'),
                        new OA\Property(property: 'description', type: 'string', example: 'This is an updated product'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 199.99),
                        new OA\Property(property: 'category', type: 'string', example: 'Electronics'),
                        new OA\Property(property: 'in_stock', type: 'boolean', example: true),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time')
                    ]
                )
            )
        ]
    )]
    #[OA\Response(response: 404, description: 'Product not found.')]
    #[OA\Response(response: 422, description: 'Validation error.')]
    public function update(Request $request, int $id): JsonResponse
    {
        // Implementation would go here
        return response()->json([
            'id' => $id,
            'name' => $request->input('name', 'Updated Product'),
            'description' => $request->input('description', 'This is an updated product'),
            'price' => $request->input('price', 199.99),
            'category' => $request->input('category', 'Electronics'),
            'in_stock' => $request->input('in_stock', true),
            'updated_at' => now()
        ]);
    }

    /**
     * Delete a product
     */
    #[OA\Operation(
        path: '/api/products/{id}',
        method: 'delete',
        summary: 'Delete a product',
        description: 'Deletes a product by its ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the product to delete.',
        required: true,
        schema: new OA\Schema(type: 'integer', format: 'int64')
    )]
    #[OA\Response(response: 204, description: 'Product deleted successfully.')]
    #[OA\Response(
        response: 404,
        description: 'Product not found.',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Product not found')
                    ]
                )
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        // Implementation would go here
        return response()->json(null, 204);
    }
}
