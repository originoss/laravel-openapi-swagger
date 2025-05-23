<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use LaravelOpenApi\Attributes as OA;

#[OA\ApiTag(
    name: 'Products',
    description: 'Product management endpoints'
)]
class ProductController extends Controller
{
    #[OA\Operation(
        summary: 'List all products',
        description: 'Retrieves a paginated list of products, optionally filtered by category.'
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'Page number for pagination.',
        required: false,
        schema: new OA\Schema(type: 'integer', examples: [1])
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Number of products per page.',
        required: false,
        schema: new OA\Schema(type: 'integer', examples: [15])
    )]
    #[OA\Parameter(
        name: 'category',
        in: 'query',
        description: 'Filter products by category.',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'A paginated list of products.',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object'
                )
            )
        ]
    )]
    #[OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product'))]
    #[OA\Property(property: 'current_page', type: 'integer')]
    #[OA\Property(property: 'last_page', type: 'integer')]
    #[OA\Property(property: 'per_page', type: 'integer')]
    #[OA\Property(property: 'total', type: 'integer')]
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('category');
        $products = Product::when($category, fn ($query, $category) => $query->where('category', $category))
                          ->paginate($request->query('per_page', 15));
        return response()->json($products);
    }

    #[OA\Operation(
        summary: 'Create a new product',
        description: 'Adds a new product to the catalog.'
    )]
    #[OA\RequestBody(
        description: 'Product object to be created.',
        required: true,
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/Product'))
        ]
    )]
    #[OA\Response(
        response: 201,
        description: 'Product created successfully.',
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/Product'))
        ]
    )]
    #[OA\Response(response: 422, description: 'Validation error.')]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $product = Product::create($validator->validated());
        return response()->json($product, 201);
    }

    #[OA\Operation(
        summary: 'Get a specific product',
        description: 'Retrieves a single product by its ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the product to retrieve.',
        required: true,
        schema: new OA\Schema(type: 'integer', examples: [1])
    )]
    #[OA\Response(
        response: 200,
        description: 'The requested product.',
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/Product'))
        ]
    )]
    #[OA\Response(response: 404, description: 'Product not found.')]
    public function show(int $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        return response()->json($product);
    }

    #[OA\Operation(
        summary: 'Update an existing product',
        description: 'Updates an existing product by its ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the product to update.',
        required: true,
        schema: new OA\Schema(type: 'integer', examples: [1])
    )]
    #[OA\RequestBody(
        description: 'Product object with updated fields.',
        required: true,
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/Product'))
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Product updated successfully.',
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: '#/components/schemas/Product'))
        ]
    )]
    #[OA\Response(response: 404, description: 'Product not found.')]
    #[OA\Response(response: 422, description: 'Validation error.')]
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'category' => 'sometimes|required|string|max:100',
            'stock' => 'sometimes|required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update($validator->validated());
        return response()->json($product);
    }

    #[OA\Operation(
        summary: 'Delete a product',
        description: 'Deletes a product by its ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the product to delete.',
        required: true,
        schema: new OA\Schema(type: 'integer', examples: [1])
    )]
    #[OA\Response(response: 204, description: 'Product deleted successfully (No Content).')]
    #[OA\Response(response: 404, description: 'Product not found.')]
    public function destroy(int $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        
        $product->delete();
        return response()->json(null, 204);
    }
}
