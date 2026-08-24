<?php

namespace App\Listeners;

use App\Events\TaskOverdue;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOverdueNotification implements ShouldQueue
{
    public function handle(TaskOverdue $event)
    {
        $assignedTo = $event->task->assignedTo;
        if (!$assignedTo) return;

        $preference = $assignedTo->notificationPreference;
        if ($preference && !$preference->overdue) {
            return;
        }

        Notification::create([
            'user_id' => $assignedTo->id,
            'type' => 'overdue',
            'title' => 'Task Overdue',
            'message' => "Task '{$event->task->title}' is overdue!",
            'data' => json_encode([
                'task_id' => $event->task->id,
            ]),
            'is_read' => false,
        ]);
    }
}