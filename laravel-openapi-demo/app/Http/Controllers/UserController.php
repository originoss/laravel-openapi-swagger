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
        summary: 'List all users',
        description: 'Retrieves a paginated list of users.'
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'Page number for pagination.',
        required: false,
        schema: new OA\Schema(type: 'integer', examples: [1])
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
                        'data' => new OA\Property(
                            type: 'array',
                            items: new OA\Items(ref: User::class)
                        ),
                        'current_page' => new OA\Property(type: 'integer'),
                        'last_page' => new OA\Property(type: 'integer'),
                        'per_page' => new OA\Property(type: 'integer'),
                        'total' => new OA\Property(type: 'integer')
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
        summary: 'Get a specific user',
        description: 'Retrieves a single user by their ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the user to retrieve.',
        required: true,
        schema: new OA\Schema(type: 'integer', examples: [1])
    )]
    #[OA\Response(
        response: 200,
        description: 'The requested user.',
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: User::class))
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
