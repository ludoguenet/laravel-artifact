<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use LaravelJutsu\Artifact\Artifact;

beforeEach(function () {
    // Create a test table for our test model
    Schema::create('test_models', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });
});

// Test model with HasArtifacts trait
class TestModel extends \Illuminate\Database\Eloquent\Model
{
    use \LaravelJutsu\Artifact\Concerns\HasArtifacts;

    protected $table = 'test_models';

    protected $fillable = ['*'];

    public function document()
    {
        return $this->singleArtifact('document');
    }

    public function images()
    {
        return $this->manyArtifacts('images');
    }
}

describe('File Retrieval - Private Disk (Local)', function () {
    beforeEach(function () {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
    });

    it('stores files on private disk correctly', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->image('private-document.jpg', 800, 600);

        $artifact = $model->document()->store($file);

        expect($artifact)
            ->toBeInstanceOf(Artifact::class)
            ->and($artifact->disk)->toBe('local')
            ->and($artifact->isPrivateDisk())->toBeTrue()
            ->and($artifact->isPublicDisk())->toBeFalse()
            ->and(Storage::disk('local')->exists($artifact->path))->toBeTrue();
    });

    it('returns null for raw_url on private disk', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->createWithContent('private-doc.pdf', 'sensitive content', 'application/pdf');

        $artifact = $model->document()->store($file);

        expect($artifact->rawUrl())->toBeNull();
    });

    it('generates stream_url for private disk files', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->image('private-image.png', 100, 100);

        $artifact = $model->document()->store($file);
        $streamUrl = $artifact->streamUrl();

        expect($streamUrl)
            ->toBeString()
            ->toContain('/artifacts/')
            ->toContain('/stream')
            ->toContain((string) $artifact->id);
    });

    it('generates signed_url for private disk files', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->createWithContent('confidential.docx', 'confidential data here', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $artifact = $model->document()->store($file);
        $signedUrl = $artifact->signedUrl();

        expect($signedUrl)
            ->toBeString()
            ->toContain('/artifacts/')
            ->toContain('/download')
            ->toContain('signature=')
            ->toContain((string) $artifact->id);
    });

    it('generates temporary_signed_url for private disk files', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->image('temp-access.jpg', 150, 150);

        $artifact = $model->document()->store($file);
        $tempSignedUrl = $artifact->temporarySignedUrl(30); // 30 minutes

        expect($tempSignedUrl)
            ->toBeString()
            ->toContain((string) $artifact->id);

        // Should contain either signature/expires parameters (if routes available) or fallback format
        $hasSignature = str_contains($tempSignedUrl, 'signature=');
        $hasExpiration = str_contains($tempSignedUrl, 'expiration=');
        expect($hasSignature || $hasExpiration)->toBeTrue();
    });

    it('handles multiple files with different URL types on private disk', function () {
        $model = TestModel::create();
        $files = [
            UploadedFile::fake()->image('image1.jpg', 100, 101), // Different dimensions to avoid hash collision
            UploadedFile::fake()->image('image2.png', 200, 201),
            UploadedFile::fake()->createWithContent('doc.pdf', 'unique pdf content here', 'application/pdf'),
        ];

        $artifacts = $model->images()->store($files);

        expect($artifacts)->toHaveCount(3);

        foreach ($artifacts as $artifact) {
            expect($artifact->isPrivateDisk())->toBeTrue()
                ->and($artifact->rawUrl())->toBeNull()
                ->and($artifact->streamUrl())->toBeString()->toContain('/artifacts/')
                ->and($artifact->signedUrl())->toBeString()->toContain('signature=')
                ->and($artifact->temporarySignedUrl())->toBeString();
        }
    });

    it('ensures private disk files never expose direct raw URLs', function () {
        $model = TestModel::create();
        $sensitiveFile = UploadedFile::fake()->createWithContent(
            'sensitive-data.json',
            json_encode(['secret' => 'confidential_info', 'api_key' => 'secret123']),
            'application/json'
        );

        $artifact = $model->document()->store($sensitiveFile);

        // Private disk should NEVER return a raw URL
        expect($artifact->isPrivateDisk())->toBeTrue()
            ->and($artifact->rawUrl())->toBeNull();

        // But should provide secure access methods
        expect($artifact->streamUrl())->toBeString()->toContain('/artifacts/')
            ->and($artifact->signedUrl())->toBeString()->toContain('signature=')
            ->and($artifact->temporarySignedUrl())->toBeString();
    });
});

describe('File Retrieval - Public Disk', function () {
    beforeEach(function () {
        // Configure public disk
        Storage::fake('public');
        config()->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ]);
        config()->set('filesystems.default', 'public');
    });

    it('stores files on public disk correctly', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->image('public-avatar.jpg', 300, 300);

        $artifact = $model->document()->store($file);

        expect($artifact)
            ->toBeInstanceOf(Artifact::class)
            ->and($artifact->disk)->toBe('public')
            ->and($artifact->isPublicDisk())->toBeTrue()
            ->and($artifact->isPrivateDisk())->toBeFalse()
            ->and(Storage::disk('public')->exists($artifact->path))->toBeTrue();
    });

    it('generates raw_url for public disk files', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->image('public-logo.png', 250, 250);

        $artifact = $model->document()->store($file);
        $rawUrl = $artifact->rawUrl();

        expect($rawUrl)
            ->toBeString()
            ->not()->toBeNull()
            ->toContain('/storage/')
            ->toContain($artifact->path);
    });

    it('generates stream_url for public disk files', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->createWithContent('public-video.mp4', 'video content here for testing', 'video/mp4');

        $artifact = $model->document()->store($file);
        $streamUrl = $artifact->streamUrl();

        expect($streamUrl)
            ->toBeString()
            ->toContain('/artifacts/')
            ->toContain('/stream')
            ->toContain((string) $artifact->id);
    });

    it('generates signed_url for public disk files', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->image('public-image.gif', 180, 180);

        $artifact = $model->document()->store($file);
        $signedUrl = $artifact->signedUrl();

        expect($signedUrl)
            ->toBeString()
            ->toContain('/artifacts/')
            ->toContain('/download')
            ->toContain('signature=')
            ->toContain((string) $artifact->id);
    });

    it('generates temporary_signed_url for public disk files', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->createWithContent('public-archive.zip', 'archive content for public testing', 'application/zip');

        $artifact = $model->document()->store($file);
        $tempSignedUrl = $artifact->temporarySignedUrl(15); // 15 minutes

        expect($tempSignedUrl)
            ->toBeString()
            ->toContain((string) $artifact->id);

        // Should contain either signature/expires parameters or fallback format
        $hasSignature = str_contains($tempSignedUrl, 'signature=');
        $hasExpiration = str_contains($tempSignedUrl, 'expiration=');
        expect($hasSignature || $hasExpiration)->toBeTrue();
    });

    it('handles public disk files with all URL types available', function () {
        $model = TestModel::create();
        $files = [
            UploadedFile::fake()->image('gallery1.jpg', 220, 221), // Different dimensions to avoid hash collision
            UploadedFile::fake()->image('gallery2.webp', 240, 241),
        ];

        $artifacts = $model->images()->store($files);

        expect($artifacts)->toHaveCount(2);

        foreach ($artifacts as $artifact) {
            expect($artifact->isPublicDisk())->toBeTrue()
                ->and($artifact->rawUrl())->toBeString()->not()->toBeNull()
                ->and($artifact->streamUrl())->toBeString()->toContain('/artifacts/')
                ->and($artifact->signedUrl())->toBeString()->toContain('signature=')
                ->and($artifact->temporarySignedUrl())->toBeString();
        }
    });
});

describe('File Retrieval - Custom Disk Configurations', function () {
    it('handles disk with custom visibility settings', function () {
        // Set up a custom disk configuration
        Storage::fake('custom');
        config()->set('filesystems.disks.custom', [
            'driver' => 'local',
            'root' => storage_path('app/custom'),
            'visibility' => 'private',
        ]);
        config()->set('filesystems.default', 'custom');

        $model = TestModel::create();
        $file = UploadedFile::fake()->createWithContent('custom-file.txt', 'custom content for testing', 'text/plain');

        $artifact = $model->document()->store($file);

        expect($artifact->disk)->toBe('custom')
            ->and($artifact->isPrivateDisk())->toBeTrue()
            ->and($artifact->rawUrl())->toBeNull();
    });

    it('handles s3-like disk configuration', function () {
        // Mock an S3-like disk (would be public)
        Storage::fake('s3-public');
        config()->set('filesystems.disks.s3-public', [
            'driver' => 'local', // Using local for testing, but simulating S3
            'root' => storage_path('app/s3-public'),
            'url' => 'https://mybucket.s3.amazonaws.com',
            'visibility' => 'public',
        ]);
        config()->set('filesystems.default', 's3-public');

        $model = TestModel::create();
        $file = UploadedFile::fake()->image('s3-image.jpg', 350, 350);

        $artifact = $model->document()->store($file);

        expect($artifact->disk)->toBe('s3-public')
            ->and($artifact->isPublicDisk())->toBeTrue()
            ->and($artifact->rawUrl())->toBeString()->not()->toBeNull();
    });

    it('handles mixed disk types in same application', function () {
        // Set up multiple disks
        Storage::fake('private');
        Storage::fake('public');

        config()->set('filesystems.disks.private', [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'visibility' => 'private',
        ]);

        config()->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ]);

        $model = TestModel::create();

        // Create artifact on private disk
        config()->set('filesystems.default', 'private');
        $privateFile = UploadedFile::fake()->image('private.jpg', 320, 321);
        $privateArtifact = $model->images()->store($privateFile);

        // Create artifact on public disk
        config()->set('filesystems.default', 'public');
        $publicFile = UploadedFile::fake()->image('public.jpg', 340, 341);
        $publicArtifact = $model->images()->store($publicFile);

        // Verify behavior differences
        expect($privateArtifact->disk)->toBe('private')
            ->and($privateArtifact->isPrivateDisk())->toBeTrue()
            ->and($privateArtifact->rawUrl())->toBeNull()
            ->and($publicArtifact->disk)->toBe('public')
            ->and($publicArtifact->isPublicDisk())->toBeTrue()
            ->and($publicArtifact->rawUrl())->toBeString()->not()->toBeNull();

        // Both should have secure access methods
        expect($privateArtifact->signedUrl())->toBeString()->toContain('signature=')
            ->and($publicArtifact->signedUrl())->toBeString()->toContain('signature=')
            ->and($privateArtifact->temporarySignedUrl())->toBeString()
            ->and($publicArtifact->temporarySignedUrl())->toBeString();
    });
});

describe('File Retrieval - URL Generation and Security', function () {
    beforeEach(function () {
        Storage::fake('local');
        config()->set('filesystems.default', 'local');
    });

    it('validates URL format consistency', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->image('test-image.jpg', 200, 200);

        $artifact = $model->document()->store($file);

        $streamUrl = $artifact->streamUrl();
        $signedUrl = $artifact->signedUrl();
        $tempSignedUrl = $artifact->temporarySignedUrl();

        // All URLs should contain the artifact ID
        expect($streamUrl)->toContain((string) $artifact->id)
            ->and($signedUrl)->toContain((string) $artifact->id)
            ->and($tempSignedUrl)->toContain((string) $artifact->id);

        // Stream URL should be different from download URLs
        expect($streamUrl)->not()->toBe($signedUrl)
            ->and($streamUrl)->not()->toBe($tempSignedUrl)
            ->and($signedUrl)->not()->toBe($tempSignedUrl);

        // Stream URL should not have signature
        expect($streamUrl)->not()->toContain('signature=');

        // Download URLs should have signatures (if routes available)
        expect($signedUrl)->toContain('signature=');
    });

    it('generates different temporary URLs with different expiration times', function () {
        $model = TestModel::create();
        $file = UploadedFile::fake()->image('temp-test.jpg', 150, 151);

        $artifact = $model->document()->store($file);

        $shortUrl = $artifact->temporarySignedUrl(5);   // 5 minutes
        $longUrl = $artifact->temporarySignedUrl(60);   // 60 minutes

        expect($shortUrl)->not()->toBe($longUrl);

        // Both should be valid URLs
        expect($shortUrl)->toBeString()->toContain((string) $artifact->id)
            ->and($longUrl)->toBeString()->toContain((string) $artifact->id);
    });

    it('handles various mime types correctly', function () {
        $model = TestModel::create();

        $testFiles = [
            ['name' => 'document.pdf', 'content' => 'PDF content for testing', 'mime' => 'application/pdf', 'size' => [100, 100]],
            ['name' => 'image.jpg', 'content' => 'JPEG content for testing', 'mime' => 'image/jpeg', 'size' => [101, 101]],
            ['name' => 'video.mp4', 'content' => 'MP4 content for testing', 'mime' => 'video/mp4', 'size' => [102, 102]],
            ['name' => 'audio.mp3', 'content' => 'MP3 content for testing', 'mime' => 'audio/mpeg', 'size' => [103, 103]],
            ['name' => 'text.txt', 'content' => 'Text content for testing', 'mime' => 'text/plain', 'size' => [104, 104]],
        ];

        foreach ($testFiles as $index => $fileData) {
            if ($fileData['mime'] === 'image/jpeg') {
                $file = UploadedFile::fake()->image($fileData['name'], $fileData['size'][0], $fileData['size'][1]);
            } else {
                $file = UploadedFile::fake()->createWithContent(
                    $fileData['name'],
                    $fileData['content']." - file {$index}",
                    $fileData['mime']
                );
            }

            $artifact = $model->images()->store($file);

            expect($artifact->mime_type)->toBe($fileData['mime'])
                ->and($artifact->streamUrl())->toBeString()->toContain('/artifacts/')
                ->and($artifact->signedUrl())->toBeString()->toContain('signature=')
                ->and($artifact->temporarySignedUrl())->toBeString();
        }
    });

    it('maintains correct disk detection across different configurations', function () {
        $testCases = [
            ['disk' => 'local', 'visibility' => 'private', 'expected_public' => false, 'size' => [100, 100]],
            ['disk' => 'public', 'visibility' => 'public', 'expected_public' => true, 'size' => [101, 101]],
            ['disk' => 's3-public', 'visibility' => 'public', 'expected_public' => true, 'size' => [102, 102]],
        ];

        foreach ($testCases as $testCase) {
            Storage::fake($testCase['disk']);
            config()->set("filesystems.disks.{$testCase['disk']}", [
                'driver' => 'local',
                'root' => storage_path("app/{$testCase['disk']}"),
                'visibility' => $testCase['visibility'],
                'url' => $testCase['expected_public'] ? env('APP_URL').'/storage' : null,
            ]);
            config()->set('filesystems.default', $testCase['disk']);

            $model = TestModel::create();
            $file = UploadedFile::fake()->image("test-{$testCase['disk']}.jpg", $testCase['size'][0], $testCase['size'][1]);
            $artifact = $model->document()->store($file);

            expect($artifact->disk)->toBe($testCase['disk'])
                ->and($artifact->isPublicDisk())->toBe($testCase['expected_public'])
                ->and($artifact->isPrivateDisk())->toBe(! $testCase['expected_public']);

            if ($testCase['expected_public']) {
                expect($artifact->rawUrl())->toBeString()->not()->toBeNull();
            } else {
                expect($artifact->rawUrl())->toBeNull();
            }
        }
    });
});
