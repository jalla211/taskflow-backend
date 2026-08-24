<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'project_id',
        'created_by',
        'assigned_to',
        'status_id',
        'priority_id',
        'start_date',
        'due_date',
        'blocked_reason',
        'progress',
        'is_recurring',
        'recurring_pattern',
        'completed_at',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function status()
    {
        return $this->belongsTo(TaskStatus::class);
    }

    public function priority()
    {
        return $this->belongsTo(TaskPriority::class);
    }

    public function subtasks()
    {
        return $this->hasMany(Subtask::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'task_tag');
    }

    public function dependencies()
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    public function dependents()
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_task_id');
    }
}