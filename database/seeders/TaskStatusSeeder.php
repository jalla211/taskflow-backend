<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaskStatus;

class TaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'To Do', 'slug' => 'todo', 'color' => '#6B7280', 'order' => 1, 'is_default' => true],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => '#3B82F6', 'order' => 2, 'is_default' => false],
            ['name' => 'Blocked', 'slug' => 'blocked', 'color' => '#EF4444', 'order' => 3, 'is_default' => false],
            ['name' => 'Under Review', 'slug' => 'under-review', 'color' => '#F59E0B', 'order' => 4, 'is_default' => false],
            ['name' => 'Completed', 'slug' => 'completed', 'color' => '#10B981', 'order' => 5, 'is_default' => false],
            ['name' => 'Cancelled', 'slug' => 'cancelled', 'color' => '#8B5CF6', 'order' => 6, 'is_default' => false],
        ];

        foreach ($statuses as $status) {
            TaskStatus::create($status);
        }
    }
}