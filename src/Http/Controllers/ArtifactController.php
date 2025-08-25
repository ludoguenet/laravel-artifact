<?php

declare(strict_types=1);

namespace LaravelJutsu\Artifact\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use LaravelJutsu\Artifact\Artifact;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArtifactController extends Controller
{
    /**
     * Stream an artifact file.
     */
    public function stream(Request $request, Artifact $artifact): StreamedResponse
    {
        $disk = Storage::disk($artifact->disk);

        if (! $disk->exists($artifact->path)) {
            abort(404, 'File not found');
        }

        return $disk->response($artifact->path, $artifact->file_name, [
            'Content-Type' => $artifact->mime_type,
            'Content-Disposition' => 'inline; filename="'.$artifact->file_name.'"',
        ]);
    }

    /**
     * Download an artifact file.
     * This route is protected by signed middleware for security.
     */
    public function download(Request $request, Artifact $artifact): StreamedResponse
    {
        $disk = Storage::disk($artifact->disk);

        if (! $disk->exists($artifact->path)) {
            abort(404, 'File not found');
        }

        return $disk->download($artifact->path, $artifact->file_name, [
            'Content-Type' => $artifact->mime_type,
        ]);
    }

    /**
     * Show artifact metadata (for testing purposes).
     */
    public function show(Request $request, Artifact $artifact): JsonResponse
    {
        return response()->json([
            'id' => $artifact->id,
            'name' => $artifact->name,
            'file_name' => $artifact->file_name,
            'mime_type' => $artifact->mime_type,
            'size' => $artifact->size,
            'disk' => $artifact->disk,
            'collection' => $artifact->collection,
            'is_public' => $artifact->isPublicDisk(),
            'is_private' => $artifact->isPrivateDisk(),
            'raw_url' => $artifact->rawUrl(),
            'stream_url' => $artifact->streamUrl(),
            'signed_url' => $artifact->signedUrl(),
            'temporary_signed_url' => $artifact->temporarySignedUrl(),
        ]);
    }
}
