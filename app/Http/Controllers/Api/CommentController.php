<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use App\Events\UserMentioned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function index($taskId)
    {
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $comments = Comment::where('task_id', $taskId)
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($comments);
    }

    public function store(Request $request, $taskId)
    {
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = Comment::create([
            'task_id' => $taskId,
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        // Handle @mentions
        $this->handleMentions($comment, $request->user());

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment->load(['user', 'replies']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized to edit this comment'], 403);
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update(['content' => $request->content]);

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment->load(['user', 'replies']),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized to delete this comment'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }

    // ===== HANDLE @MENTIONS =====
    private function handleMentions($comment, $commentAuthor)
    {
        // Match @username (letters, numbers, underscores, AND SPACES)
        preg_match_all('/@([a-zA-Z0-9_\s]+)/', $comment->content, $matches);

        Log::info('🔍 Found @mentions: ' . json_encode($matches[1] ?? []));

        if (!empty($matches[1])) {
            foreach ($matches[1] as $username) {
                $username = trim($username); // Remove trailing spaces

                $user = User::where('name', $username)->first();

                if ($user && $user->id !== $commentAuthor->id) {
                    $preference = $user->notificationPreference;
                    if ($preference && !$preference->mention) {
                        Log::info('⚠️ User ' . $user->name . ' disabled mention notifications');
                        continue;
                    }

                    // FIRE THE EVENT
                    event(new UserMentioned($user, $comment, $commentAuthor));
                    Log::info('🔔 UserMentioned event fired for ' . $user->name);
                } else {
                    Log::info('⚠️ No user found for: ' . $username);
                }
            }
        }
    }
}