<?php

namespace App\Listeners;

use App\Events\TaskDeleted;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendTaskDeletionNotification
{
    public function handle(TaskDeleted $event)
    {
Log::info('=== TASK DELETED LISTENER TRIGGERED ===');
        $assignee = $event->task->assignedTo;
        if (!$assignee || $assignee->id === $event->deletedBy->id) {
            return;
        }

        $preference = $assignee->notificationPreference;
        if ($preference && !$preference->task_assignment) {
            return;
        }

        Notification::create([
            'user_id' => $assignee->id,
            'type' => 'task_deleted',
            'title' => 'Task Deleted',
            'message' => "{$event->deletedBy->name} deleted the task: {$event->task->title}",
            'data' => json_encode([
                'task_id' => $event->task->id,
                'project_id' => $event->task->project_id,
            ]),
            'is_read' => false,
        ]);

        Log::info('🔔 Deletion notification sent to assignee: ' . $assignee->id);
    }
}