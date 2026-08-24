<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskStatus;
use App\Models\TaskPriority;
use App\Models\Tag;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // ============ STATUSES ============
    
    public function statuses()
    {
        return response()->json(TaskStatus::orderBy('order')->get());
    }
    
    public function storeStatus(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:task_statuses',
            'color' => 'required|string|max:20',
        ]);
        
        $maxOrder = TaskStatus::max('order') ?? 0;
        
        $status = TaskStatus::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'color' => $request->color,
            'order' => $maxOrder + 1,
            'is_default' => false,
        ]);
        
        $this->logAudit('created', $status);
        
        return response()->json([
            'message' => 'Status created successfully',
            'status' => $status,
        ], 201);
    }
    
    public function updateStatus(Request $request, $id)
    {
        $status = TaskStatus::find($id);
        if (!$status) {
            return response()->json(['message' => 'Status not found'], 404);
        }
        
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:task_statuses,name,' . $id,
            'color' => 'sometimes|string|max:20',
            'order' => 'sometimes|integer',
            'is_default' => 'sometimes|boolean',
        ]);
        
        if ($request->has('name')) {
            $request->merge(['slug' => \Str::slug($request->name)]);
        }
        
        $old = $status->getOriginal();
        $status->update($request->all());
        $this->logAudit('updated', $status, $old);
        
        return response()->json([
            'message' => 'Status updated successfully',
            'status' => $status,
        ]);
    }
    
    public function deleteStatus($id)
    {
        $status = TaskStatus::find($id);
        if (!$status) {
            return response()->json(['message' => 'Status not found'], 404);
        }
        
        if ($status->is_default) {
            return response()->json(['message' => 'Cannot delete default status'], 400);
        }
        
        $this->logAudit('deleted', $status);
        $status->delete();
        
        return response()->json(['message' => 'Status deleted successfully']);
    }
    
    // ============ PRIORITIES ============
    
    public function priorities()
    {
        return response()->json(TaskPriority::orderBy('level')->get());
    }
    
    public function storePriority(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:task_priorities',
            'color' => 'required|string|max:20',
            'level' => 'required|integer|min:1|max:5',
        ]);
        
        $priority = TaskPriority::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'color' => $request->color,
            'level' => $request->level,
        ]);
        
        $this->logAudit('created', $priority);
        
        return response()->json([
            'message' => 'Priority created successfully',
            'priority' => $priority,
        ], 201);
    }
    
    public function updatePriority(Request $request, $id)
    {
        $priority = TaskPriority::find($id);
        if (!$priority) {
            return response()->json(['message' => 'Priority not found'], 404);
        }
        
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:task_priorities,name,' . $id,
            'color' => 'sometimes|string|max:20',
            'level' => 'sometimes|integer|min:1|max:5',
        ]);
        
        if ($request->has('name')) {
            $request->merge(['slug' => \Str::slug($request->name)]);
        }
        
        $old = $priority->getOriginal();
        $priority->update($request->all());
        $this->logAudit('updated', $priority, $old);
        
        return response()->json([
            'message' => 'Priority updated successfully',
            'priority' => $priority,
        ]);
    }
    
    public function deletePriority($id)
    {
        $priority = TaskPriority::find($id);
        if (!$priority) {
            return response()->json(['message' => 'Priority not found'], 404);
        }
        
        $this->logAudit('deleted', $priority);
        $priority->delete();
        
        return response()->json(['message' => 'Priority deleted successfully']);
    }
    
    // ============ TAGS ============
    
    public function tags()
    {
        return response()->json(Tag::all());
    }
    
    public function storeTag(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tags',
            'color' => 'required|string|max:20',
        ]);
        
        $tag = Tag::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'color' => $request->color,
        ]);
        
        $this->logAudit('created', $tag);
        
        return response()->json([
            'message' => 'Tag created successfully',
            'tag' => $tag,
        ], 201);
    }
    
    public function updateTag(Request $request, $id)
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json(['message' => 'Tag not found'], 404);
        }
        
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:tags,name,' . $id,
            'color' => 'sometimes|string|max:20',
        ]);
        
        if ($request->has('name')) {
            $request->merge(['slug' => \Str::slug($request->name)]);
        }
        
        $old = $tag->getOriginal();
        $tag->update($request->all());
        $this->logAudit('updated', $tag, $old);
        
        return response()->json([
            'message' => 'Tag updated successfully',
            'tag' => $tag,
        ]);
    }
    
    public function deleteTag($id)
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json(['message' => 'Tag not found'], 404);
        }
        
        $this->logAudit('deleted', $tag);
        $tag->delete();
        
        return response()->json(['message' => 'Tag deleted successfully']);
    }
    
    // ============ AUDIT LOGS ============
    
    public function auditLogs(Request $request)
    {
        $logs = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        
        $logs = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                ] : null,
                'action' => $log->action,
                'details' => $this->formatAuditDetails($log),
                'created_at' => $log->created_at,
            ];
        });
        
        return response()->json($logs);
    }
    
    // ============ HELPER METHODS ============
    
    private function logAudit($action, $model, $oldValues = null)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues ?? $model->getOriginal(),
            'new_values' => $model->getChanges(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    private function formatAuditDetails($log)
    {
        $details = [];
        
        $old = $log->old_values ? (is_array($log->old_values) ? $log->old_values : json_decode($log->old_values, true)) : [];
        $new = $log->new_values ? (is_array($log->new_values) ? $log->new_values : json_decode($log->new_values, true)) : [];
        
        if (is_array($old) && is_array($new)) {
            foreach ($new as $key => $value) {
                if (isset($old[$key]) && $old[$key] != $value) {
                    $oldVal = is_array($old[$key]) ? json_encode($old[$key]) : ($old[$key] ?? 'null');
                    $newVal = is_array($value) ? json_encode($value) : ($value ?? 'null');
                    $details[] = $key . ': ' . $oldVal . ' → ' . $newVal;
                }
            }
        }
        
        if (empty($details)) {
            $details[] = $log->action . ' performed';
        }
        
        if ($log->model_type && $log->model_id) {
            $modelName = class_basename($log->model_type);
            $details[] = 'On: ' . $modelName . ' #' . $log->model_id;
        }
        
        return implode(', ', $details);
    }
}