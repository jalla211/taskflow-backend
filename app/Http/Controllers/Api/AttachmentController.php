<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    // Get all attachments for a task
    public function index($taskId)
    {
        $task = Task::find($taskId);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $attachments = Attachment::where('task_id', $taskId)
            ->with('uploader')
            ->get();

        return response()->json($attachments);
    }

    // Upload a file to a task
    public function store(Request $request, $taskId)
    {
        $task = Task::find($taskId);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Check file type (images, PDFs, Excel, ZIP)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 
                        'application/pdf', 'application/vnd.ms-excel', 
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/zip', 'application/x-zip-compressed'];

        if (!in_array($mimeType, $allowedTypes)) {
            return response()->json([
                'message' => 'File type not allowed. Allowed: images, PDF, Excel, ZIP'
            ], 400);
        }

        // Generate unique filename
        $filename = Str::uuid() . '_' . $originalName;
        $path = $file->storeAs('attachments', $filename, 'public');

        $attachment = Attachment::create([
            'task_id' => $taskId,
            'uploaded_by' => $request->user()->id,
            'filename' => $filename,
            'original_filename' => $originalName,
            'file_path' => $path,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'attachment' => $attachment->load('uploader'),
        ], 201);
    }

    // Download a file
public function download($id)
{
    $attachment = Attachment::find($id);

    if (!$attachment) {
        return response()->json(['message' => 'File not found'], 404);
    }

    $path = storage_path('app/public/' . $attachment->file_path);

    if (!file_exists($path)) {
        return response()->json(['message' => 'File not found on server'], 404);
    }

    // Return file with proper headers for download
    return response()->download($path, $attachment->original_filename, [
        'Content-Type' => $attachment->mime_type,
        'Content-Disposition' => 'attachment; filename="' . $attachment->original_filename . '"',
    ]);
}

    // Delete an attachment
    public function destroy($id)
    {
        $attachment = Attachment::find($id);

        if (!$attachment) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted successfully',
        ]);
    }
}