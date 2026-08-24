<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Full system access'],
            ['name' => 'Project Manager', 'slug' => 'project-manager', 'description' => 'Manage projects and tasks'],
            ['name' => 'Team Leader', 'slug' => 'team-leader', 'description' => 'Oversee team tasks'],
            ['name' => 'Team Member', 'slug' => 'team-member', 'description' => 'Execute tasks'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}