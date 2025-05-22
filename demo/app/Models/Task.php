<?php

namespace App\Models; // Assuming demo app uses App namespace

use Illuminate\Database\Eloquent\Factories\HasFactory; // Optional: For seeding/testing
use Illuminate\Database\Eloquent\Model;
use LaravelOpenApi\Attributes as OA;

#[OA\Schema(
    title: 'Task',
    description: 'Represents a task item in the to-do list.',
    required: ['id', 'title', 'status'] // id is usually required for responses
    // The 'properties' array is intentionally OMITTED here as per instructions.
)]
class Task extends Model
{
    // use HasFactory; // Uncomment if you plan to add a factory

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'due_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    #[OA\Property(
        description: 'Unique identifier for the task.',
        type: 'integer',
        format: 'int64',
        example: 1,
        readOnly: true // Typically IDs are read-only after creation
    )]
    public int $id;

    #[OA\Property(
        description: 'Title or name of the task.',
        type: 'string',
        example: 'Buy groceries',
        maxLength: 255
    )]
    public string $title;

    #[OA\Property(
        description: 'Detailed description of the task.',
        type: 'string',
        example: 'Milk, Eggs, Bread, and Cheese',
        nullable: true
    )]
    public ?string $description; // Nullable string

    #[OA\Property(
        description: 'Current status of the task.',
        type: 'string',
        enum: ['pending', 'in-progress', 'completed'],
        default: 'pending',
        example: 'pending'
    )]
    public string $status;

    #[OA\Property(
        description: 'Due date for the task.',
        type: 'string',
        format: 'date',
        example: '2024-12-31',
        nullable: true
    )]
    public ?string $due_date; // Nullable date string

    #[OA\Property(
        description: 'Timestamp when the task was created.',
        type: 'string',
        format: 'date-time',
        example: '2023-01-01T12:00:00Z',
        readOnly: true
    )]
    public ?string $created_at; // Made nullable string to align with typical Eloquent behavior if not set

    #[OA\Property(
        description: 'Timestamp when the task was last updated.',
        type: 'string',
        format: 'date-time',
        example: '2023-01-01T13:30:00Z',
        readOnly: true
    )]
    public ?string $updated_at; // Made nullable string
}
