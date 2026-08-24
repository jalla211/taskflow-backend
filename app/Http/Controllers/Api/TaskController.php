<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskPriority;
use App\Models\Subtask;
use App\Models\TaskDependency;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Events\TaskUpdated;

class TaskController extends Controller
{
    // ========== GET ALL TASKS ==========
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Task::with(['project', 'assignee', 'creator', 'status', 'priority']);

        // Filters
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }
        if ($request->has('priority_id')) {
            $query->where('priority_id', $request->priority_id);
        }
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Role-based filtering:
        // Admin (1) and Project Manager (2) see ALL tasks
        // Others see only tasks assigned to them or created by them
        if (!in_array($user->role_id, [1, 2])) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }

        $tasks = $query->get();
        return response()->json($tasks);
    }

    // ========== CREATE TASK ==========
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => 'required|exists:users,id',
            'priority_id' => 'required|exists:task_priorities,id',
            'due_date' => 'required|date',
            'start_date' => 'nullable|date',
        ]);

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'project_id' => $request->project_id,
            'created_by' => $request->user()->id,
            'assigned_to' => $request->assigned_to,
            'priority_id' => $request->priority_id,
            'status_id' => 1, // Default: To Do
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
            'progress' => 0,
            'is_recurring' => $request->is_recurring ?? false,
            'recurring_pattern' => $request->recurring_pattern ?? null,
        ]);

        // Fire TaskAssigned event
        if ($task->assigned_to) {
            $assignedTo = User::find($task->assigned_to);
            $assignedBy = $request->user();
            event(new \App\Events\TaskAssigned($task, $assignedTo, $assignedBy));
        }

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task->load(['project', 'assignee', 'creator', 'status', 'priority']),
        ], 201);
    }

    // ========== GET SINGLE TASK ==========
    public function show($id)
    {
        $task = Task::with([
            'project', 'assignee', 'creator', 'status', 'priority',
            'subtasks', 'comments', 'attachments', 'tags'
        ])->find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        return response()->json($task);
    }

    // ========== UPDATE TASK ==========
 public function update(Request $request, $id)
{
    $task = Task::find($id);
    if (!$task) {
        return response()->json(['message' => 'Task not found'], 404);
    }

    $request->validate([
        'title' => 'sometimes|string|max:255',
        'description' => 'nullable|string',
        'priority_id' => 'sometimes|exists:task_priorities,id',
        'due_date' => 'sometimes|date',
        'start_date' => 'nullable|date',
        'progress' => 'sometimes|integer|min:0|max:100',
        'assigned_to' => 'sometimes|exists:users,id|nullable',
    ]);

    // Store old data before update
    $oldData = $task->getOriginal();
    $oldAssignedTo = $task->assigned_to;
    $oldUser = $oldAssignedTo ? User::find($oldAssignedTo) : null;

    $task->update($request->all());
if (!empty($changedFields)) {
    event(new \App\Events\TaskUpdated($task, $request->user(), $oldData));
}

    // --- 1. Check if assignee changed ---
    if ($request->has('assigned_to') && $task->assigned_to != $oldAssignedTo) {
        $changedBy = $request->user();

        // Notify NEW assignee
        if ($task->assigned_to) {
            $newUser = User::find($task->assigned_to);
            if ($newUser) {
                $pref = $newUser->notificationPreference;
                if (!$pref || $pref->task_assignment) {
                    event(new \App\Events\TaskAssigned($task, $newUser, $changedBy));
                }
            }
        }

        // Notify OLD assignee (removal)
        if ($oldUser) {
            $pref = $oldUser->notificationPreference;
            if (!$pref || $pref->task_assignment) {
                \App\Models\Notification::create([
                    'user_id' => $oldUser->id,
                    'type' => 'task_unassigned',
                    'title' => 'Task Unassigned',
                    'message' => "{$changedBy->name} removed you from the task: {$task->title}",
                    'data' => json_encode([
                        'task_id' => $task->id,
                        'project_id' => $task->project_id,
                        'removed_by' => $changedBy->id,
                    ]),
                    'is_read' => false,
                ]);
            }
        }
    }

    // --- 2. Fire TaskUpdated for non-assignee changes ---
    if (!$request->has('assigned_to') || $request->assigned_to == $oldAssignedTo) {
        $changedFields = [];
        $fieldLabels = [
            'title' => 'title',
            'description' => 'description',
            'priority_id' => 'priority',
            'due_date' => 'due date',
            'start_date' => 'start date',
            'progress' => 'progress',
        ];
        
        foreach ($fieldLabels as $field => $label) {
            if ($request->has($field) && $task->{$field} != $oldData[$field]) {
                $changedFields[] = $label;
            }
        }
        
        if (!empty($changedFields)) {
            event(new \App\Events\TaskUpdated($task, $request->user(), $oldData));
        }
    }

    return response()->json([
        'message' => 'Task updated successfully',
        'task' => $task->load(['project', 'assignee', 'creator', 'status', 'priority']),
    ]);
}

    // ========== DELETE TASK ==========
    public function destroy($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        event(new \App\Events\TaskDeleted($task, request()->user()));
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    // ========== ASSIGN TASK ==========
    public function assign(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $user = User::findOrFail($request->user_id);

        Log::info('=== ASSIGN METHOD CALLED ===');
        Log::info('Task ID: ' . $task->id);
        Log::info('Assigned To User ID: ' . $user->id);
        Log::info('Assigned By User ID: ' . $request->user()->id);

        $task->assigned_to = $request->user_id;
        $task->save();

        event(new \App\Events\TaskAssigned($task, $user, $request->user()));

        return response()->json([
            'message' => 'Task assigned successfully',
            'task' => $task->load(['assignee', 'project', 'status', 'priority']),
        ]);
    }

    // ========== UPDATE TASK STATUS ==========
    public function updateStatus(Request $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $request->validate([
            'status_id' => 'required|exists:task_statuses,id',
            'blocked_reason' => 'nullable|string|required_if:status_id,3',
        ]);

        $oldStatusId = $task->status_id;
        $oldStatus = TaskStatus::find($oldStatusId);
        $newStatus = TaskStatus::find($request->status_id);

        Log::info('=== UPDATE STATUS METHOD CALLED ===');
        Log::info('Task ID: ' . $task->id);
        Log::info('Old Status ID: ' . $oldStatusId);
        Log::info('New Status ID: ' . $request->status_id);

        $task->update([
            'status_id' => $request->status_id,
            'blocked_reason' => $request->blocked_reason,
            'completed_at' => $request->status_id == 5 ? now() : null,
        ]);

        if ($oldStatusId != $request->status_id) {
            Log::info('=== STATUS CHANGED, FIRING EVENT ===');
            event(new \App\Events\TaskStatusChanged($task, $request->user(), $oldStatus, $newStatus));
            Log::info('=== EVENT FIRED ===');
        }

        return response()->json([
            'message' => 'Task status updated successfully',
            'task' => $task->load(['project', 'assignee', 'creator', 'status', 'priority']),
        ]);
    }

    // ========== GET SUBTASKS ==========
    public function getSubtasks($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        return response()->json($task->subtasks);
    }

    // ========== CREATE SUBTASK ==========
    public function createSubtask(Request $request, $id)
    {
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'required|date',
        ]);

        $subtask = Subtask::create([
            'task_id' => $id,
            'title' => $request->title,
            'description' => $request->description,
            'assigned_to' => $request->assigned_to,
            'status_id' => 1,
            'due_date' => $request->due_date,
            'is_completed' => false,
        ]);

        return response()->json([
            'message' => 'Subtask created successfully',
            'subtask' => $subtask->load(['assignee', 'status']),
        ], 201);
    }

    // ========== UPDATE SUBTASK ==========
    public function updateSubtask(Request $request, $id)
    {
        $subtask = Subtask::find($id);
        if (!$subtask) {
            return response()->json(['message' => 'Subtask not found'], 404);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'sometimes|exists:users,id',
            'status_id' => 'sometimes|exists:task_statuses,id',
            'due_date' => 'sometimes|date',
            'is_completed' => 'sometimes|boolean',
        ]);

        $subtask->update($request->all());

        return response()->json([
            'message' => 'Subtask updated successfully',
            'subtask' => $subtask->load(['assignee', 'status']),
        ]);
    }

    // ========== DELETE SUBTASK ==========
    public function deleteSubtask($id)
    {
        $subtask = Subtask::find($id);
        if (!$subtask) {
            return response()->json(['message' => 'Subtask not found'], 404);
        }

        $subtask->delete();

        return response()->json(['message' => 'Subtask deleted successfully']);
    }

    // ========== DEPENDENCIES ==========
    public function getDependencies($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        return response()->json($task->dependencies);
    }

    public function addDependency(Request $request, $id)
    {
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $request->validate([
            'depends_on_task_id' => 'required|exists:tasks,id|different:task_id',
        ]);

        $dependency = TaskDependency::create([
            'task_id' => $id,
            'depends_on_task_id' => $request->depends_on_task_id,
        ]);

        return response()->json([
            'message' => 'Dependency added successfully',
            'dependency' => $dependency,
        ], 201);
    }

    public function removeDependency($id, $dependencyId)
    {
        $dependency = TaskDependency::where('task_id', $id)
            ->where('id', $dependencyId)
            ->first();

        if (!$dependency) {
            return response()->json(['message' => 'Dependency not found'], 404);
        }

        $dependency->delete();

        return response()->json(['message' => 'Dependency removed successfully']);
    }
}