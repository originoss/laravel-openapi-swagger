<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use LaravelOpenApi\Attributes as OA;

#[OA\ApiTag(
    name: 'Users',
    description: 'User management endpoints',
    externalDocs: 'https://example.com/docs/users'
)]
class UserController extends Controller
{
    #[OA\Operation(
        path: '/api/users',
        method: 'get',
        summary: 'List all users',
        description: 'Retrieves a paginated list of users.'
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'Page number for pagination.',
        required: false,
        schema: new OA\Schema(type: 'integer', example: 1, default: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'A paginated list of users.',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(['ref' => '#/components/schemas/User'])),
                        new OA\Property(property: 'current_page', type: 'integer'),
                        new OA\Property(property: 'last_page', type: 'integer'),
                        new OA\Property(property: 'per_page', type: 'integer'),
                        new OA\Property(property: 'total', type: 'integer'),
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $users = User::paginate($request->query('per_page', 15));
        return response()->json($users);
    }

    #[OA\Operation(
        path: '/api/users/{id}',
        method: 'get',
        summary: 'Get a specific user',
        description: 'Retrieves a single user by their ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the user to retrieve.',
        required: true,
        schema: new OA\Schema(type: 'integer', format: 'int64', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'The requested user.',
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(['ref' => '#/components/schemas/User']))
        ]
    )]
    #[OA\Response(response: 404, description: 'User not found.')]
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json($user);
    }
}
