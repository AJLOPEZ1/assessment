<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:1000',
            'status' => ['sometimes', 'required', 'string', Rule::in(TaskStatusEnum::values())],
            'assigned_to' => 'sometimes|required|exists:users,id',
            'due_date' => 'sometimes|required|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required.',
            'title.max' => 'Task title must not exceed 255 characters.',
            'description.required' => 'Task description is required.',
            'description.max' => 'Task description must not exceed 1000 characters.',
            'status.required' => 'Task status is required.',
            'status.in' => 'Invalid task status selected.',
            'assigned_to.required' => 'Task must be assigned to a user.',
            'assigned_to.exists' => 'Selected user does not exist.',
            'due_date.required' => 'Due date is required.',
            'due_date.date' => 'Please provide a valid due date.',
        ];
    }
}
