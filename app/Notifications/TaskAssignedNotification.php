<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $task;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('New Task Assigned: ' . $this->task->title)
                    ->greeting('Hello ' . $notifiable->name . '!')
                    ->line('You have been assigned a new task: ' . $this->task->title)
                    ->line('Description: ' . $this->task->description)
                    ->line('Due Date: ' . $this->task->due_date)
                    ->line('Project: ' . $this->task->project->title)
                    ->action('View Task', url('/api/tasks/' . $this->task->id))
                    ->line('Thank you for using our project management system!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_title' => $this->task->project->title,
            'due_date' => $this->task->due_date,
        ];
    }
}
