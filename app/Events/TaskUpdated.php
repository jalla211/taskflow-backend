<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $updatedBy;
    public $oldData;

    public function __construct(Task $task, User $updatedBy, $oldData = null)
    {
        $this->task = $task;
        $this->updatedBy = $updatedBy;
        $this->oldData = $oldData;
    }
}