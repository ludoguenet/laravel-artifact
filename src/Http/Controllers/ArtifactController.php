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
    public function stream(Request $request, string $artifact): StreamedResponse
    {
        $artifactModel = Artifact::findOrFail($artifact);

        $disk = Storage::disk($artifactModel->disk);

        if (! $disk->exists($artifactModel->path)) {
            abort(404, 'File not found');
        }

        return $disk->response($artifactModel->path, $artifactModel->file_name, [
            'Content-Type' => $artifactModel->mime_type,
            'Content-Disposition' => 'inline; filename="'.$artifactModel->file_name.'"',
        ]);
    }

    /**
     * Download an artifact file.
     * This route is protected by signed middleware for security.
     */
    public function download(Request $request, string $artifact): StreamedResponse
    {
        $artifactModel = Artifact::findOrFail($artifact);

        $disk = Storage::disk($artifactModel->disk);

        if (! $disk->exists($artifactModel->path)) {
            abort(404, 'File not found');
        }

        return $disk->download($artifactModel->path, $artifactModel->file_name, [
            'Content-Type' => $artifactModel->mime_type,
        ]);
    }

    /**
     * Show artifact metadata (for testing purposes).
     */
    public function show(Request $request, string $artifact): JsonResponse
    {
        $artifactModel = Artifact::findOrFail($artifact);

        return response()->json([
            'id' => $artifactModel->id,
            'name' => $artifactModel->name,
            'file_name' => $artifactModel->file_name,
            'mime_type' => $artifactModel->mime_type,
            'size' => $artifactModel->size,
            'disk' => $artifactModel->disk,
            'collection' => $artifactModel->collection,
            'is_public' => $artifactModel->isPublicDisk(),
            'is_private' => $artifactModel->isPrivateDisk(),
            'raw_url' => $artifactModel->rawUrl(),
            'stream_url' => $artifactModel->streamUrl(),
            'signed_url' => $artifactModel->signedUrl(),
            'temporary_signed_url' => $artifactModel->temporarySignedUrl(),
        ]);
    }
}
