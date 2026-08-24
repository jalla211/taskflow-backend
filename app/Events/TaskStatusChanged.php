<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $changedBy;
    public $oldStatus;
    public $newStatus;

    public function __construct(Task $task, User $changedBy, $oldStatus, $newStatus)
    {
        $this->task = $task;
        $this->changedBy = $changedBy;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}