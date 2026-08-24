<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $user = $request->user();
        $query = Task::with(['project', 'assignee', 'creator', 'status', 'priority']);
        
        // User can only see tasks they have access to
        if (!$user->isAdmin() && !$user->isProjectManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        
        // Search by title or description
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        
        // Filter by project
        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }
        
        // Filter by status
        if ($request->has('status_id') && $request->status_id) {
            $query->where('status_id', $request->status_id);
        }
        
        // Filter by priority
        if ($request->has('priority_id') && $request->priority_id) {
            $query->where('priority_id', $request->priority_id);
        }
        
        // Filter by assignee
        if ($request->has('assigned_to') && $request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        // Filter by due date range
        if ($request->has('due_from') && $request->due_from) {
            $query->where('due_date', '>=', $request->due_from);
        }
        if ($request->has('due_to') && $request->due_to) {
            $query->where('due_date', '<=', $request->due_to);
        }
        
        // Filter by creation date
        if ($request->has('created_from') && $request->created_from) {
            $query->where('created_at', '>=', $request->created_from);
        }
        if ($request->has('created_to') && $request->created_to) {
            $query->where('created_at', '<=', $request->created_to);
        }
        
        // Sort
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $query->orderBy($sortField, $sortDirection);
        
        $tasks = $query->get();
        
        return response()->json([
            'tasks' => $tasks,
            'filters' => $request->all(),
            'total' => $tasks->count(),
        ]);
    }
}