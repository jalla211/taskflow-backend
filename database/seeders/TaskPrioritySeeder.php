<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaskPriority;

class TaskPrioritySeeder extends Seeder
{
    public function run(): void
    {
        $priorities = [
            ['name' => 'Low', 'slug' => 'low', 'color' => '#22C55E', 'level' => 1],
            ['name' => 'Medium', 'slug' => 'medium', 'color' => '#F59E0B', 'level' => 2],
            ['name' => 'High', 'slug' => 'high', 'color' => '#F97316', 'level' => 3],
            ['name' => 'Critical', 'slug' => 'critical', 'color' => '#EF4444', 'level' => 4],
        ];

        foreach ($priorities as $priority) {
            TaskPriority::create($priority);
        }
    }
}