<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
 protected $listen = [
    Registered::class => [
        SendEmailVerificationNotification::class,
    ],
    
    // ===== TASK NOTIFICATIONS =====
    \App\Events\TaskAssigned::class => [
        \App\Listeners\SendTaskAssignmentNotification::class,
    ],
    \App\Events\TaskStatusChanged::class => [
        \App\Listeners\SendStatusChangeNotification::class,
    ],
    \App\Events\TaskUpdated::class => [              // <-- NEW
        \App\Listeners\SendTaskUpdateNotification::class,
    ],
    \App\Events\TaskDeleted::class => [              // <-- NEW
        \App\Listeners\SendTaskDeletionNotification::class,
    ],
    \App\Events\UserMentioned::class => [
        \App\Listeners\SendMentionNotification::class,
    ],
    \App\Events\TaskDeadlineSoon::class => [
        \App\Listeners\SendDeadlineReminder::class,
    ],
    \App\Events\TaskOverdue::class => [
        \App\Listeners\SendOverdueNotification::class,
    ],
];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}