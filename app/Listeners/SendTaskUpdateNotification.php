<?php

namespace App\Listeners;

use App\Events\TaskUpdated;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendTaskUpdateNotification
{
    public function handle(TaskUpdated $event)
    {
Log::info('=== TASK UPDATED LISTENER TRIGGERED ===');
        $assignee = $event->task->assignedTo;
        if (!$assignee || $assignee->id === $event->updatedBy->id) {
            return;
        }

        $preference = $assignee->notificationPreference;
        if ($preference && !$preference->task_assignment) {
            return;
        }

        // Build a meaningful message
        $message = "{$event->updatedBy->name} updated the task: {$event->task->title}";
        
        // Include what changed
        if ($event->oldData) {
            $changedFields = [];
            $fieldLabels = [
                'title' => 'title',
                'description' => 'description',
                'priority_id' => 'priority',
                'due_date' => 'due date',
                'start_date' => 'start date',
                'progress' => 'progress',
            ];
            
            foreach ($event->oldData as $key => $oldValue) {
                if (in_array($key, ['id', 'created_at', 'updated_at', 'assigned_to', 'status_id', 'completed_at'])) continue;
                if ($event->task->{$key} != $oldValue) {
                    $label = $fieldLabels[$key] ?? $key;
                    $changedFields[] = $label;
                }
            }
            
            if (!empty($changedFields)) {
                $message .= " (changed: " . implode(', ', $changedFields) . ")";
            }
        }

        Notification::create([
            'user_id' => $assignee->id,
            'type' => 'task_updated',
            'title' => 'Task Updated',
            'message' => $message,
            'data' => json_encode([
                'task_id' => $event->task->id,
                'project_id' => $event->task->project_id,
                'updated_by' => $event->updatedBy->id,
            ]),
            'is_read' => false,
        ]);

        Log::info('🔔 Update notification sent to assignee: ' . $assignee->id);
    }
}