<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Get roles
        $adminRole = Role::where('slug', 'admin')->first();
        $pmRole = Role::where('slug', 'project-manager')->first();
        $teamLeaderRole = Role::where('slug', 'team-leader')->first();
        $teamMemberRole = Role::where('slug', 'team-member')->first();

        // Create Admin
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole?->id ?? 1,
                'is_active' => true,
                'phone' => '0788000000',
            ]
        );

        // Create Project Manager
        User::updateOrCreate(
            ['email' => 'pm@example.com'],
            [
                'name' => 'Project Manager',
                'password' => Hash::make('password123'),
                'role_id' => $pmRole?->id ?? 2,
                'is_active' => true,
                'phone' => '0788000001',
            ]
        );

        // Create Team Leader
        User::updateOrCreate(
            ['email' => 'teamleader@example.com'],
            [
                'name' => 'Team Leader',
                'password' => Hash::make('password123'),
                'role_id' => $teamLeaderRole?->id ?? 3,
                'is_active' => true,
                'phone' => '0788000002',
            ]
        );

        // Create Team Member
        User::updateOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Team Member',
                'password' => Hash::make('password123'),
                'role_id' => $teamMemberRole?->id ?? 4,
                'is_active' => true,
                'phone' => '0788000003',
            ]
        );

        $this->command->info('Default users created successfully!');
    }
}