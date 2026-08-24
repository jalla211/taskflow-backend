<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendStatusChangeNotification
{
    public function handle(TaskStatusChanged $event)
    {
        Log::info('=== STATUS CHANGE LISTENER TRIGGERED ===');
        Log::info('Task ID: ' . $event->task->id);
        Log::info('Changed By: ' . $event->changedBy->id);
        Log::info('Old Status: ' . ($event->oldStatus?->name ?? 'Unknown'));
        Log::info('New Status: ' . ($event->newStatus?->name ?? 'Unknown'));

        $changer = $event->changedBy;
        $assignee = $event->task->assignedTo;
        $creator = $event->task->creator;
        $oldStatusName = $event->oldStatus?->name ?? 'Unknown';
        $newStatusName = $event->newStatus?->name ?? 'Unknown';

        // --- 1. Notify the ASSIGNEE (if not the one who changed it) ---
        if ($assignee && $assignee->id !== $changer->id) {
            $preference = $assignee->notificationPreference;
            if (!$preference || $preference->status_change) {
                Notification::create([
                    'user_id' => $assignee->id,
                    'type' => 'status_change',
                    'title' => 'Task Status Changed',
                    'message' => "{$changer->name} changed status of '{$event->task->title}' from '{$oldStatusName}' to '{$newStatusName}'",
                    'data' => json_encode([
                        'task_id' => $event->task->id,
                        'old_status' => $oldStatusName,
                        'new_status' => $newStatusName,
                    ]),
                    'is_read' => false,
                ]);
                Log::info('✅ Status notification sent to assignee: ' . $assignee->id);
            }
        }

        // --- 2. Notify the CREATOR (PM) if assignee changed status ---
        if ($assignee && $assignee->id === $changer->id && $creator && $creator->id !== $changer->id) {
            $preference = $creator->notificationPreference;
            if (!$preference || $preference->status_change) {
                Notification::create([
                    'user_id' => $creator->id,
                    'type' => 'status_change_by_assignee',
                    'title' => 'Assignee Updated Task Status',
                    'message' => "{$assignee->name} changed status of '{$event->task->title}' from '{$oldStatusName}' to '{$newStatusName}'",
                    'data' => json_encode([
                        'task_id' => $event->task->id,
                        'old_status' => $oldStatusName,
                        'new_status' => $newStatusName,
                    ]),
                    'is_read' => false,
                ]);
                Log::info('✅ Status notification sent to creator (PM): ' . $creator->id);
            }
        }
    }
}