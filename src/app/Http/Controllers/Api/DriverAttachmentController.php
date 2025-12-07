<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DriverAttachmentController extends Controller
{
    /**
     * Display a listing of the resource for a specific driver.
     */
    public function index(User $user): JsonResponse
    {
        $attachments = $user->driverAttachments()->orderBy('created_at', 'desc')->get();
        return response()->json($attachments);
    }

    /**
     * Store a newly created attachment.
     */
    public function store(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'attachment_type' => 'required|string',
            'expiration_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        // Handle file upload
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->store('driver_attachments/' . $user->id, 'private');

        $attachment = $user->driverAttachments()->create([
            'attachment_type' => $validated['attachment_type'],
            'expiration_date' => $validated['expiration_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json($attachment, 201);
    }

    /**
     * Download the specified attachment.
     */
    public function download(User $user, DriverAttachment $attachment): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Verify attachment belongs to user
        if ($attachment->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        if (!Storage::disk('private')->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('private')->download(
            $attachment->file_path,
            $attachment->file_name
        );
    }

    /**
     * Update the specified attachment (metadata only).
     */
    public function update(Request $request, User $user, DriverAttachment $attachment): JsonResponse
    {
        // Verify attachment belongs to user
        if ($attachment->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'attachment_type' => 'sometimes|string',
            'expiration_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $attachment->update($validated);

        return response()->json($attachment);
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(User $user, DriverAttachment $attachment): JsonResponse
    {
        // Verify attachment belongs to user
        if ($attachment->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Delete file from storage
        if (Storage::disk('private')->exists($attachment->file_path)) {
            Storage::disk('private')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully'], 200);
    }
}
