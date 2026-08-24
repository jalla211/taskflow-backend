<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->foreignId('status_id')->constrained('task_statuses');
            $table->foreignId('priority_id')->constrained('task_priorities');
            $table->date('start_date')->nullable();
            $table->date('due_date');
            $table->text('blocked_reason')->nullable();
            $table->integer('progress')->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_pattern')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};