<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_assignment',
        'status_change',
        'mention',
        'deadline_reminder',
        'overdue',
        'email_notifications',
    ];

    protected $casts = [
        'task_assignment' => 'boolean',
        'status_change' => 'boolean',
        'mention' => 'boolean',
        'deadline_reminder' => 'boolean',
        'overdue' => 'boolean',
        'email_notifications' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}