<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $deletedBy;

    public function __construct(Task $task, User $deletedBy)
    {
        $this->task = $task;
        $this->deletedBy = $deletedBy;
    }
}