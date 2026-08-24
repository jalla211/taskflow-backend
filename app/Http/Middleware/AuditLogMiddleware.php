<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        // Only log for authenticated users
        if (!$request->user()) {
            return;
        }

        // Skip GET requests (only log modifications)
        if ($request->method() === 'GET') {
            return;
        }

        // Skip if no model is being updated (like auth routes)
        if (!$request->route() || !$request->route()->getController()) {
            return;
        }

        $controller = $request->route()->getController();
        $modelType = null;
        $modelId = null;
        $action = $request->method();
        $oldValues = null;
        $newValues = null;

        // Try to get model from route parameters
        $routeParams = $request->route()->parameters();
        
        foreach ($routeParams as $key => $value) {
            if (is_numeric($value) && ($key === 'id' || str_ends_with($key, '_id') || $key === 'taskId' || $key === 'userId')) {
                $modelId = $value;
                break;
            }
        }

        // Determine model type from route
        $routePath = $request->path();
        if (str_contains($routePath, 'project')) {
            $modelType = 'Project';
        } elseif (str_contains($routePath, 'task')) {
            $modelType = 'Task';
        } elseif (str_contains($routePath, 'user')) {
            $modelType = 'User';
        } elseif (str_contains($routePath, 'comment')) {
            $modelType = 'Comment';
        } elseif (str_contains($routePath, 'attachment')) {
            $modelType = 'Attachment';
        } elseif (str_contains($routePath, 'status')) {
            $modelType = 'TaskStatus';
        } elseif (str_contains($routePath, 'priority')) {
            $modelType = 'TaskPriority';
        } else {
            return;
        }

        // Get old values if model exists
        if ($modelType && $modelId) {
            $modelClass = 'App\\Models\\' . $modelType;
            if (class_exists($modelClass)) {
                $model = $modelClass::find($modelId);
                if ($model) {
                    $oldValues = $model->toArray();
                }
            }
        }

        // Get new values from request
        $newValues = $request->all();
        unset($newValues['password']);
        unset($newValues['password_confirmation']);

        // Create audit log
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}