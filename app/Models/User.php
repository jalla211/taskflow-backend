<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'phone',
        'profile_picture',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isAdmin()
    {
        return $this->hasRole('Administrator');
    }

    public function isProjectManager()
    {
        return $this->hasRole('Project Manager');
    }

    public function isTeamLeader()
    {
        return $this->hasRole('Team Leader');
    }

    public function isTeamMember()
    {
        return $this->hasRole('Team Member');
    }

    // New relationships
    public function projectsAsManager()
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function projectsAsMember()
    {
        return $this->belongsToMany(Project::class, 'project_members');
    }

    public function tasksCreated()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function tasksAssigned()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
public function notificationPreference()
{
    return $this->hasOne(NotificationPreference::class);
}
}