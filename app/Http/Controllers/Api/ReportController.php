<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Get reports data
    public function index(Request $request)
    {
        $user = $request->user();
        $range = $request->get('range', 'this-month');
        
        // Get date range
        $dateRange = $this->getDateRange($range);
        
        // Build task query based on user role
        $tasksQuery = Task::query();
        
        if ($user->isAdmin()) {
            // Admin sees all
        } elseif ($user->isProjectManager()) {
            $projectIds = Project::where('manager_id', $user->id)->pluck('id');
            $tasksQuery->whereIn('project_id', $projectIds);
        } else {
            $tasksQuery->where('assigned_to', $user->id)
                      ->orWhere('created_by', $user->id);
        }
        
        // Apply date range
        if ($dateRange) {
            $tasksQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }
        
        // Get stats
        $totalTasks = $tasksQuery->count();
        $completedTasks = $tasksQuery->where('status_id', 5)->count();
        $overdueTasks = $tasksQuery->where('due_date', '<', now())
            ->where('status_id', '!=', 5)
            ->count();
        $inProgressTasks = $tasksQuery->where('status_id', 2)->count();
        
        // Project stats
        $projectsQuery = Project::query();
        if (!$user->isAdmin()) {
            $projectsQuery->where('manager_id', $user->id)
                ->orWhereHas('members', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        }
        $projects = $projectsQuery->get();
        $activeProjects = $projects->where('status', 'active')->count();
        
        // Team members count
        $teamMembers = User::where('is_active', true)->count();
        
        // Completion rate
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        
        // On time rate
        $onTimeTasks = $tasksQuery->where('status_id', 5)
            ->where('completed_at', '<=', DB::raw('due_date'))
            ->count();
        $onTimeRate = $completedTasks > 0 ? round(($onTimeTasks / $completedTasks) * 100) : 0;
        
        // Get tasks for list
        $tasks = $tasksQuery->with(['project', 'status', 'priority'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        
        // Get project progress
        $projectProgress = [];
        foreach ($projects as $project) {
            $total = $project->tasks()->count();
            $completed = $project->tasks()->where('status_id', 5)->count();
            $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
            
            $projectProgress[] = [
                'id' => $project->id,
                'name' => $project->name,
                'progress' => $progress,
                'total_tasks' => $total,
                'completed_tasks' => $completed,
            ];
        }
        
        // Team workload
        $teamStats = $this->getTeamStats($user);
        
        return response()->json([
            'stats' => [
                'completed' => $completedTasks,
                'overdue' => $overdueTasks,
                'active_projects' => $activeProjects,
                'team_members' => $teamMembers,
                'completion_rate' => $completionRate,
                'avg_completion_time' => $this->getAvgCompletionTime($tasksQuery),
                'on_time_rate' => $onTimeRate,
            ],
            'tasks' => $tasks,
            'projects' => $projectProgress,
            'team_stats' => $teamStats,
        ]);
    }
    
    // Export report
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $range = $request->get('range', 'this-month');
        
        // Get data
        $data = $this->getReportData($request);
        
        if ($format === 'csv') {
            return $this->exportCSV($data);
        } else {
            return $this->exportPDF($data);
        }
    }
    
    private function getReportData($request)
    {
        $user = $request->user();
        $range = $request->get('range', 'this-month');
        $dateRange = $this->getDateRange($range);
        
        $tasksQuery = Task::with(['project', 'status', 'priority', 'assignee']);
        
        if (!$user->isAdmin()) {
            $tasksQuery->where('assigned_to', $user->id)
                      ->orWhere('created_by', $user->id);
        }
        
        if ($dateRange) {
            $tasksQuery->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }
        
        return [
            'tasks' => $tasksQuery->get(),
            'generated_at' => now()->toDateTimeString(),
        ];
    }
    
    private function exportCSV($data)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="report.csv"',
        ];
        
        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Task', 'Project', 'Status', 'Priority', 'Assignee', 'Due Date', 'Created At']);
            
            foreach ($data['tasks'] as $task) {
                fputcsv($file, [
                    $task->title,
                    $task->project->name ?? 'N/A',
                    $task->status->name ?? 'N/A',
                    $task->priority->name ?? 'N/A',
                    $task->assignee->name ?? 'N/A',
                    $task->due_date ?? 'N/A',
                    $task->created_at->format('Y-m-d'),
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    private function exportPDF($data)
    {
        // For now, return CSV as fallback
        // You can install barryvdh/laravel-dompdf for PDF export
        return $this->exportCSV($data);
    }
    
    private function getDateRange($range)
    {
        $now = now();
        switch ($range) {
            case 'today':
                return ['start' => $now->startOfDay(), 'end' => $now->endOfDay()];
            case 'this-week':
                return ['start' => $now->startOfWeek(), 'end' => $now->endOfWeek()];
            case 'this-month':
                return ['start' => $now->startOfMonth(), 'end' => $now->endOfMonth()];
            case 'last-month':
                return ['start' => $now->subMonth()->startOfMonth(), 'end' => $now->subMonth()->endOfMonth()];
            case 'this-quarter':
                return ['start' => $now->startOfQuarter(), 'end' => $now->endOfQuarter()];
            default:
                return null;
        }
    }
    
    private function getAvgCompletionTime($query)
    {
        $tasks = $query->where('status_id', 5)
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at')
            ->get();
        
        if ($tasks->isEmpty()) {
            return 'N/A';
        }
        
        $totalHours = 0;
        foreach ($tasks as $task) {
            $diff = $task->created_at->diffInHours($task->completed_at);
            $totalHours += $diff;
        }
        
        $avg = round($totalHours / $tasks->count());
        
        if ($avg < 1) {
            return '< 1 hour';
        } elseif ($avg < 24) {
            return $avg . ' hours';
        } else {
            return round($avg / 24) . ' days';
        }
    }
    
    private function getTeamStats($user)
    {
        if ($user->isAdmin()) {
            $users = User::with('role')->where('id', '!=', $user->id)->get();
        } else {
            $projectIds = Project::where('manager_id', $user->id)->pluck('id');
            $memberProjectIds = ProjectMember::where('user_id', $user->id)->pluck('project_id');
            $allProjectIds = $projectIds->merge($memberProjectIds)->unique();
            
            $users = User::whereHas('tasksAssigned', function ($q) use ($allProjectIds) {
                $q->whereIn('project_id', $allProjectIds);
            })->with('role')->limit(10)->get();
        }
        
        $stats = [];
        foreach ($users as $u) {
            $tasks = Task::where('assigned_to', $u->id);
            
            if (!$user->isAdmin()) {
                $projectIds = Project::where('manager_id', $user->id)->pluck('id');
                $tasks->whereIn('project_id', $projectIds);
            }
            
            $stats[] = [
                'user_id' => $u->id,
                'name' => $u->name,
                'role' => $u->role->name ?? 'Unknown',
                'total_tasks' => $tasks->count(),
                'completed_tasks' => $tasks->where('status_id', 5)->count(),
                'in_progress_tasks' => $tasks->where('status_id', 2)->count(),
                'overdue_tasks' => $tasks->where('due_date', '<', now())
                    ->where('status_id', '!=', 5)
                    ->count(),
            ];
        }
        
        return $stats;
    }
}