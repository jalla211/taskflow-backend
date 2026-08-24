<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaskStatus;
use App\Models\TaskPriority;

class TaskSettingsSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            ['name' => 'To Do', 'slug' => 'todo', 'color' => '#6B7280', 'order' => 1, 'is_default' => true],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => '#3B82F6', 'order' => 2, 'is_default' => false],
            ['name' => 'Review', 'slug' => 'review', 'color' => '#F59E0B', 'order' => 3, 'is_default' => false],
            ['name' => 'Testing', 'slug' => 'testing', 'color' => '#8B5CF6', 'order' => 4, 'is_default' => false],
            ['name' => 'Done', 'slug' => 'done', 'color' => '#10B981', 'order' => 5, 'is_default' => false],
        ];

        foreach ($statuses as $status) {
            TaskStatus::updateOrCreate(['slug' => $status['slug']], $status);
        }

        $priorities = [
            ['name' => 'Low', 'slug' => 'low', 'color' => '#6B7280', 'level' => 1],
            ['name' => 'Medium', 'slug' => 'medium', 'color' => '#F59E0B', 'level' => 2],
            ['name' => 'High', 'slug' => 'high', 'color' => '#EF4444', 'level' => 3],
            ['name' => 'Critical', 'slug' => 'critical', 'color' => '#DC2626', 'level' => 4],
        ];

        foreach ($priorities as $priority) {
            TaskPriority::updateOrCreate(['slug' => $priority['slug']], $priority);
        }
    }
}