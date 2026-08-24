<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            $table->boolean('task_assignment')->default(true);
            $table->boolean('status_change')->default(true);
            $table->boolean('mention')->default(true);
            $table->boolean('deadline_reminder')->default(true);
            $table->boolean('overdue')->default(true);
            $table->boolean('email_notifications')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'user_id',
                'task_assignment',
                'status_change',
                'mention',
                'deadline_reminder',
                'overdue',
                'email_notifications'
            ]);
        });
    }
};