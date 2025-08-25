<?php

declare(strict_types=1);

namespace LaravelJutsu\Artifact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * @property string $name
 * @property string $file_name
 * @property string $mime_type
 * @property string $path
 * @property string $disk
 * @property string $file_hash
 * @property string $collection
 * @property int $size
 */
class Artifact extends Model
{
    protected $table = 'artifacts';

    protected $fillable = [
        'name', 'file_name', 'mime_type', 'path', 'disk', 'file_hash', 'collection', 'size',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function artifactable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function fromUpload(UploadedFile $file, string $collection, ?string $disk = null): self
    {
        $name = $file->hashName();
        $path = $file->storeAs($collection, $name, ['disk' => $disk ?? config('filesystems.default')]);

        return new self([
            'name' => $name,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'path' => $path,
            'disk' => $disk ?? config('filesystems.default'),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'collection' => $collection,
            'size' => $file->getSize(),
        ]);
    }

    public static function deletePrevious(Model $model, string $collection): void
    {
        if (! method_exists($model, 'artifacts')) {
            abort(500, "get_class($model) must define an artifacts() relationship to use Artifact::deletePrevious()");
        }

        $existingArtifacts = $model->artifacts()->where('collection', $collection)->get();

        foreach ($existingArtifacts as $artifact) {
            // Delete the file from storage
            Storage::disk($artifact->disk)->delete($artifact->path);
            // Delete the database record
            $artifact->delete();
        }
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    /**
     * Get the raw URL to the file (direct storage path).
     * Only works for public disks.
     */
    public function rawUrl(): ?string
    {
        $disk = Storage::disk($this->disk);

        // Check if the disk is public (has a URL method available)
        if (method_exists($disk, 'url') && $this->isPublicDisk()) {
            return $disk->url($this->path);
        }

        return null; // Private disks don't expose raw URLs
    }

    /**
     * Get a stream URL for the file.
     * This would typically be a route that streams the file content.
     */
    public function streamUrl(): string
    {
        return route('artifact.stream', ['artifact' => $this->getKey()]);
    }

    /**
     * Get a signed URL for the file.
     * This provides secure access to files with signature validation.
     */
    public function signedUrl(): string
    {
        $disk = Storage::disk($this->disk);

        // If the disk supports signed URLs (like S3), use that
        if (method_exists($disk, 'signedUrl')) {
            return $disk->signedUrl($this->path);
        }

        // Otherwise, generate a signed route URL
        return URL::signedRoute('artifact.download', ['artifact' => $this->getKey()]);
    }

    /**
     * Get a temporary signed URL for the file with expiration.
     */
    public function temporarySignedUrl(int $expirationMinutes = 60): string
    {
        $disk = Storage::disk($this->disk);

        // If the disk supports temporary signed URLs (like S3), use that
        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($this->path, now()->addMinutes($expirationMinutes));
        }

        // Otherwise, generate a temporary signed route URL
        return URL::temporarySignedRoute(
            'artifact.download',
            now()->addMinutes($expirationMinutes),
            ['artifact' => $this->getKey()]
        );
    }

    /**
     * Check if the disk is configured as public.
     */
    public function isPublicDisk(): bool
    {
        $diskConfig = config("filesystems.disks.{$this->disk}");

        // Check if disk has visibility set to public or if it's a public disk type
        return ($diskConfig['visibility'] ?? 'private') === 'public' ||
               in_array($this->disk, ['public', 's3-public']) ||
               (isset($diskConfig['url']) && $diskConfig['url'] !== null);
    }

    /**
     * Check if the disk is configured as private.
     */
    public function isPrivateDisk(): bool
    {
        return ! $this->isPublicDisk();
    }
}
