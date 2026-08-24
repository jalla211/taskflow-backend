<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use App\Models\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
   public function index(Request $request)
{
    $user = $request->user();
    $now = now();
    
    // Build task query based on user role
    $tasksQuery = Task::query();
    
    // Admin sees all tasks
    if ($user->isAdmin()) {
        // Admin sees everything
    } 
    // Project Manager sees tasks in their projects
    elseif ($user->isProjectManager()) {
        $projectIds = Project::where('manager_id', $user->id)->pluck('id');
        $tasksQuery->whereIn('project_id', $projectIds);
    } 
    // Team Leader and Member see only their tasks
    else {
        $tasksQuery->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
    }
    
    $totalTasks = $tasksQuery->count();
    $toDoTasks = $tasksQuery->where('status_id', 1)->count();
    $inProgressTasks = $tasksQuery->where('status_id', 2)->count();
    $completedTasks = $tasksQuery->where('status_id', 5)->count();
    $overdueTasks = $tasksQuery->where('due_date', '<', $now)
        ->where('status_id', '!=', 5)
        ->count();
    
    // Projects - based on user role
    $projectsQuery = Project::query();
    
    if ($user->isAdmin()) {
        // Admin sees all projects
    } elseif ($user->isProjectManager()) {
        $projectsQuery->where('manager_id', $user->id);
    } else {
        // Team Leader and Member see projects they are part of
        $projectsQuery->whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->orWhere('manager_id', $user->id);
    }
    
    $projects = $projectsQuery->get();
    $projectProgress = [];
    
    foreach ($projects as $project) {
        $total = $project->tasks()->count();
        $completed = $project->tasks()->where('status_id', 5)->count();
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        $projectProgress[] = [
            'id' => $project->id,
            'name' => $project->name,
            'progress' => $progress,
            'status' => $project->status,
        ];
    }
    
    // Recent tasks - 10 most recent
    $recentTasks = $tasksQuery->with(['project', 'status', 'priority'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    // Team stats for managers/admins
    $teamStats = null;
    if ($user->isAdmin() || $user->isProjectManager() || $user->isTeamLeader()) {
        $teamStats = $this->getTeamStats($user);
    }
    
    return response()->json([
        'stats' => [
            'total_tasks' => $totalTasks,
            'to_do' => $toDoTasks,
            'in_progress' => $inProgressTasks,
            'completed' => $completedTasks,
            'overdue' => $overdueTasks,
        ],
        'project_progress' => $projectProgress,
        'recent_tasks' => $recentTasks,
        'team_stats' => $teamStats,
    ]);
}
    
private function getTeamStats($user)
{
    // For admins, get all users
    if ($user->isAdmin()) {
        $users = \App\Models\User::with('role')->where('id', '!=', $user->id)->get();
    } else {
        // For PM and Team Leader, get users in their projects
        $projectIds = Project::where('manager_id', $user->id)->pluck('id');
        
        // Also get projects where user is a member
        $memberProjectIds = \App\Models\ProjectMember::where('user_id', $user->id)->pluck('project_id');
        $allProjectIds = $projectIds->merge($memberProjectIds)->unique();
        
        $users = \App\Models\User::whereHas('tasksAssigned', function ($q) use ($allProjectIds) {
            $q->whereIn('project_id', $allProjectIds);
        })->with('role')->get();
        
        // If no users found, get users who have tasks in these projects
        if ($users->isEmpty()) {
            $users = \App\Models\User::whereHas('tasksAssigned', function ($q) use ($allProjectIds) {
                $q->whereIn('project_id', $allProjectIds);
            })->with('role')->limit(10)->get();
        }
    }
    
    $stats = [];
    foreach ($users as $u) {
        $tasks = \App\Models\Task::where('assigned_to', $u->id);
        
        // For PM, only count tasks in their projects
        if (!$user->isAdmin()) {
            $projectIds = Project::where('manager_id', $user->id)->pluck('id');
            $tasks->whereIn('project_id', $projectIds);
        }
        
        $stats[] = [
            'user_id' => $u->id,
            'name' => $u->name,
            'role' => $u->role ? $u->role->name : 'Unknown',
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $tasks->where('status_id', 5)->count(),
            'overdue_tasks' => $tasks->where('due_date', '<', now())
                ->where('status_id', '!=', 5)
                ->count(),
        ];
    }
    
    return $stats;
}
}