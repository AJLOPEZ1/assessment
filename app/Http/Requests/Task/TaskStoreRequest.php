<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStoreRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => ['required', 'string', Rule::in(TaskStatusEnum::values())],
            'priority' => 'required|string|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after:today',
            'project_id' => 'required|exists:projects,id',
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
            'description.max' => 'Task description must not exceed 1000 characters.',
            'status.required' => 'Task status is required.',
            'status.in' => 'Invalid task status selected.',
            'priority.required' => 'Task priority is required.',
            'priority.in' => 'Priority must be one of: low, medium, high, urgent.',
            'assigned_to.exists' => 'Selected user does not exist.',
            'due_date.date' => 'Please provide a valid due date.',
            'due_date.after' => 'Due date must be in the future.',
            'project_id.required' => 'Project is required.',
            'project_id.exists' => 'Selected project does not exist.',
        ];
    }
}
