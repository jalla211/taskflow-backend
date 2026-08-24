<?php

namespace App\Listeners;

use App\Events\TaskDeadlineSoon;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDeadlineReminder implements ShouldQueue
{
    public function handle(TaskDeadlineSoon $event)
    {
        $assignedTo = $event->task->assignedTo;
        if (!$assignedTo) return;

        $preference = $assignedTo->notificationPreference;
        if ($preference && !$preference->deadline_reminder) {
            return;
        }

        Notification::create([
            'user_id' => $assignedTo->id,
            'type' => 'deadline_reminder',
            'title' => 'Task Deadline Approaching',
            'message' => "Task '{$event->task->title}' is due in {$event->daysRemaining} days",
            'data' => json_encode([
                'task_id' => $event->task->id,
                'days_remaining' => $event->daysRemaining,
            ]),
            'is_read' => false,
        ]);
    }
}