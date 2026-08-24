<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // Get all projects
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Admin can see all projects
        if ($user->isAdmin()) {
            $projects = Project::with(['manager', 'members'])->get();
        } else {
            // PM, Team Leader, Member see only their projects
            $projects = Project::where('manager_id', $user->id)
                ->orWhereHas('members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['manager', 'members'])
                ->get();
        }
        
        return response()->json($projects);
    }

    // Create a new project
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'manager_id' => $request->user()->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project->load(['manager', 'members']),
        ], 201);
    }

    // Get single project
    public function show($id)
    {
        $project = Project::with(['manager', 'members', 'tasks'])->find($id);
        
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }
        
        return response()->json($project);
    }

    // Update project
    public function update(Request $request, $id)
    {
        $project = Project::find($id);
        
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:active,archived,closed',
        ]);

        $project->update($request->all());

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project->load(['manager', 'members']),
        ]);
    }

    // Delete project
    public function destroy($id)
    {
        $project = Project::find($id);
        
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    // Add member to project
    public function addMember(Request $request, $id)
    {
        $project = Project::find($id);
        
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Check if user is already a member
        $exists = ProjectMember::where('project_id', $id)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'User is already a member'], 400);
        }

        ProjectMember::create([
            'project_id' => $id,
            'user_id' => $request->user_id,
        ]);

        return response()->json([
            'message' => 'Member added successfully',
            'project' => $project->load(['manager', 'members']),
        ]);
    }

    // Remove member from project
    public function removeMember($id, $userId)
    {
        $project = Project::find($id);
        
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $member = ProjectMember::where('project_id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $member->delete();

        return response()->json([
            'message' => 'Member removed successfully',
            'project' => $project->load(['manager', 'members']),
        ]);
    }

    // Archive project
    public function archive($id)
    {
        $project = Project::find($id);
        
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $project->update(['status' => 'archived']);

        return response()->json([
            'message' => 'Project archived successfully',
            'project' => $project->load(['manager', 'members']),
        ]);
    }

    // Close project
    public function close($id)
    {
        $project = Project::find($id);
        
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $project->update(['status' => 'closed']);

        return response()->json([
            'message' => 'Project closed successfully',
            'project' => $project->load(['manager', 'members']),
        ]);
    }
}