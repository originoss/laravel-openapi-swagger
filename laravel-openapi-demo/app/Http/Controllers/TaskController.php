<?php

namespace App\Http\Controllers; // Assuming demo app uses App namespace

use App\Models\Task; // Assuming Task model is in App\Models
use Illuminate\Http\Request; // Standard Laravel request
use Illuminate\Http\JsonResponse; // For type hinting response
use Illuminate\Support\Facades\Validator; // For basic validation
use LaravelOpenApi\Attributes as OA;

#[OA\ApiTag(
    name: 'Tasks',
    description: 'Task management endpoints',
)]
class TaskController extends Controller
{
    #[OA\Operation(
        tags: ['Tasks'],
        summary: 'List all tasks',
        description: 'Retrieves a paginated list of tasks, optionally filtered by status.'
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'Page number for pagination.',
        required: false,
        schema: new OA\Schema(type: 'integer', examples: [1], default: 1)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Number of tasks per page.',
        required: false,
        schema: new OA\Schema(type: 'integer', examples: [15], default: 15)
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter tasks by status.',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['pending', 'in-progress', 'completed'])
    )]
    #[OA\Response(
        response: 200,
        description: 'A paginated list of tasks.',
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: Task::class)),
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
        // Placeholder logic
        $status = $request->query('status');
        $tasks = Task::when($status, fn ($query, $status) => $query->where('status', $status))
                     ->paginate($request->query('per_page', 15));
        return response()->json($tasks);
    }

    #[OA\Operation(
        tags: ['Tasks'],
        summary: 'Create a new task',
        description: 'Adds a new task to the to-do list.'
    )]
    #[OA\RequestBody(
        description: 'Task object to be created. `id`, `created_at`, `updated_at` are ignored.',
        required: true,
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: Task::class))
        ]
    )]
    #[OA\Response(
        response: 201,
        description: 'Task created successfully.',
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: Task::class))
        ]
    )]
    #[OA\Response(response: 422, description: 'Validation error.')]
    public function store(Request $request): JsonResponse
    {
        // Placeholder logic
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|string|in:pending,in-progress,completed',
            'due_date' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $task = Task::create($validator->validated());
        return response()->json($task, 201);
    }

    #[OA\Operation(
        tags: ['Tasks'],
        summary: 'Get a specific task',
        description: 'Retrieves a single task by its ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the task to retrieve.',
        required: true,
        schema: new OA\Schema(type: 'integer', format: 'int64', examples: [1])
    )]
    #[OA\Response(
        response: 200,
        description: 'The requested task.',
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: Task::class))
        ]
    )]
    #[OA\Response(response: 404, description: 'Task not found.')]
    public function show(int $id): JsonResponse
    {
        // Placeholder logic
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        return response()->json($task);
    }

    #[OA\Operation(
        tags: ['Tasks'],
        summary: 'Update an existing task',
        description: 'Updates an existing task by its ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the task to update.',
        required: true,
        schema: new OA\Schema(type: 'integer', format: 'int64', examples: [1])
    )]
    #[OA\RequestBody(
        description: 'Task object with updated fields. `id`, `created_at`, `updated_at` are ignored. All fields are optional for partial updates.',
        required: true,
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: Task::class))
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Task updated successfully.',
        content: [
            new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(ref: Task::class))
        ]
    )]
    #[OA\Response(response: 404, description: 'Task not found.')]
    #[OA\Response(response: 422, description: 'Validation error.')]
    public function update(Request $request, int $id): JsonResponse
    {
        // Placeholder logic
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255', // 'sometimes' means only validate if present
            'description' => 'nullable|string',
            'status' => 'sometimes|string|in:pending,in-progress,completed',
            'due_date' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task->update($validator->validated());
        return response()->json($task);
    }

    #[OA\Operation(
        tags: ['Tasks'],
        summary: 'Delete a task',
        description: 'Deletes a task by its ID.'
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID of the task to delete.',
        required: true,
        schema: new OA\Schema(type: 'integer', examples: [1])
    )]
    #[OA\Response(response: 204, description: 'Task deleted successfully (No Content).')]
    #[OA\Response(response: 404, description: 'Task not found.')]
    public function destroy(int $id): JsonResponse
    {
        // Placeholder logic
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        $task->delete();
        return response()->json(null, 204);
    }
}
