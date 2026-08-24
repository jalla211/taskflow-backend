<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TaskAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $assignedTo;
    public $assignedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task, User $assignedTo, User $assignedBy)
    {
Log::info('=== TaskAssigned event constructed ===');
        $this->task = $task;
        $this->assignedTo = $assignedTo;
        $this->assignedBy = $assignedBy;
    }
}