<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeadlineSoon
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $daysRemaining;

    public function __construct(Task $task, $daysRemaining)
    {
        $this->task = $task;
        $this->daysRemaining = $daysRemaining;
    }
}