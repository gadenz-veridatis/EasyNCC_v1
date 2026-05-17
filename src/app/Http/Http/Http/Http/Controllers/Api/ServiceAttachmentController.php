<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ServiceAttachmentController extends Controller
{
    public function index(Service $service): JsonResponse
    {
        return response()->json($service->attachments()->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:20480',
            'notes' => 'nullable|string',
        ]);

        $file = $request->file('file');

        $attachment = $service->attachments()->create([
            'file_path' => $file->store('service_attachments/' . $service->id, 'private'),
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($attachment, 201);
    }

    public function download(Service $service, ServiceAttachment $attachment)
    {
        if ($attachment->service_id !== $service->id) {
            abort(403, 'Unauthorized');
        }

        if (!$attachment->file_path || !Storage::disk('private')->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('private')->download(
            $attachment->file_path,
            $attachment->file_name
        );
    }

    public function destroy(Service $service, ServiceAttachment $attachment): JsonResponse
    {
        if ($attachment->service_id !== $service->id) {
            abort(403, 'Unauthorized');
        }

        if ($attachment->file_path && Storage::disk('private')->exists($attachment->file_path)) {
            Storage::disk('private')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully']);
    }
}
