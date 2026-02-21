<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleAttachment;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class VehicleAttachmentController extends Controller
{
    /**
     * Display a listing of the resource for a specific vehicle.
     */
    public function index(Vehicle $vehicle): JsonResponse
    {
        $attachments = $vehicle->vehicleAttachments()->orderBy('created_at', 'desc')->get();
        return response()->json($attachments);
    }

    /**
     * Store a newly created attachment.
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse
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
        $filePath = $file->store('vehicle_attachments/' . $vehicle->id, 'private');

        $attachment = $vehicle->vehicleAttachments()->create([
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
    public function download(Vehicle $vehicle, VehicleAttachment $attachment): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Verify attachment belongs to vehicle
        if ($attachment->vehicle_id !== $vehicle->id) {
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
     * Update the specified attachment (metadata and optionally file).
     */
    public function update(Request $request, Vehicle $vehicle, VehicleAttachment $attachment): JsonResponse
    {
        // Verify attachment belongs to vehicle
        if ($attachment->vehicle_id !== $vehicle->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'attachment_type' => 'sometimes|string',
            'expiration_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
        ]);

        // Handle file replacement
        if ($request->hasFile('file')) {
            // Delete old file
            if ($attachment->file_path && Storage::disk('private')->exists($attachment->file_path)) {
                Storage::disk('private')->delete($attachment->file_path);
            }

            // Store new file
            $file = $request->file('file');
            $filePath = $file->store('vehicle_attachments/' . $vehicle->id, 'private');

            $validated['file_path'] = $filePath;
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_mime_type'] = $file->getMimeType();
            $validated['file_size'] = $file->getSize();
        }

        unset($validated['file']);
        $attachment->update($validated);

        return response()->json($attachment);
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(Vehicle $vehicle, VehicleAttachment $attachment): JsonResponse
    {
        // Verify attachment belongs to vehicle
        if ($attachment->vehicle_id !== $vehicle->id) {
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
