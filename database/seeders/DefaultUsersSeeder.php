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
        // ===== FIRST: Ensure roles exist =====
        $this->command->info('Checking and creating roles...');

        $roles = [
            ['name' => 'System Administrator', 'slug' => 'admin'],
            ['name' => 'Project Manager', 'slug' => 'project-manager'],
            ['name' => 'Team Leader', 'slug' => 'team-leader'],
            ['name' => 'Team Member', 'slug' => 'team-member'],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                ['name' => $roleData['name']]
            );
            $this->command->info("Role '{$roleData['name']}' created/updated.");
        }

        // ===== SECOND: Get roles =====
        $adminRole = Role::where('slug', 'admin')->first();
        $pmRole = Role::where('slug', 'project-manager')->first();
        $teamLeaderRole = Role::where('slug', 'team-leader')->first();
        $teamMemberRole = Role::where('slug', 'team-member')->first();

        // ===== THIRD: Create users =====
        $this->command->info('Creating users...');

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
        $this->command->info('Admin user created.');

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
        $this->command->info('Project Manager created.');

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
        $this->command->info('Team Leader created.');

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
        // Create Real User (gateteprince24@gmail.com - Project Manager)
        User::updateOrCreate(
            ['email' => 'gateteprince24@gmail.com'],
            [
                'name' => 'GATETE Prince',
                'password' => Hash::make('KIGALI24'),
                'role_id' => $pmRole?->id ?? 2,
                'is_active' => true,
                'phone' => '0790781195',
            ]
        );
// Admin user with real email
User::updateOrCreate(
    ['email' => 'gateteprince000@gmail.com'],
    [
        'name' => 'Admin Prince',
        'password' => Hash::make('KIGALI24'),
        'role_id' => $adminRole?->id ?? 1,
        'is_active' => true,
        'phone' => '0798620162',
    ]
);
        $this->command->info('Team Member created.');

        $this->command->info('✅ Default users created successfully!');
    }
}