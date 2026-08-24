<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    // Get all audit logs (Admin only)
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Only Admin can view audit logs
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Only administrators can view audit logs.'
            ], 403);
        }

        $limit = $request->limit ?? 50;
        $logs = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($logs);
    }

    // Get audit logs for a specific model (Admin only)
    public function getByModel(Request $request, $modelType, $modelId)
    {
        $user = $request->user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Only administrators can view audit logs.'
            ], 403);
        }

        $logs = AuditLog::with('user')
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    // Get audit logs for a specific task (Admin/PM)
    public function getByTask(Request $request, $taskId)
    {
        $user = $request->user();
        
        // Check if user has access to this task
        $task = \App\Models\Task::find($taskId);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        // Check if user is admin, PM, or assigned to this task
        if (!$user->isAdmin() && !$user->isProjectManager() && $task->assigned_to != $user->id && $task->created_by != $user->id) {
            return response()->json([
                'message' => 'Unauthorized to view this task\'s audit logs.'
            ], 403);
        }

        $logs = AuditLog::with('user')
            ->where('model_type', 'Task')
            ->where('model_id', $taskId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }
}