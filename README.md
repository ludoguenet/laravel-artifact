# Laravel Artifact

<p align="center">
    <img src="art/logo.png" alt="Laravel Artifact Logo" width="300">
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraveljutsu/artifact.svg?style=flat-square)](https://packagist.org/packages/laraveljutsu/artifact)
[![Tests](https://img.shields.io/github/actions/workflow/status/laraveljutsu/artifact/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/laraveljutsu/artifact/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/laraveljutsu/artifact.svg?style=flat-square)](https://packagist.org/packages/laraveljutsu/artifact)

**Artifact** is a lightweight Laravel package that handles basic media management with no fuss. It provides a simple and clean way to manage file uploads, storage, and retrieval with support for both public and private files, signed URLs, and multiple storage disks.

## Features

- 🚀 **Simple file uploads** - Easy file handling with collections
- 📁 **Multiple storage support** - Works with any Laravel filesystem disk
- 🔒 **Secure file access** - Signed URLs and private file support  
- 🎯 **Polymorphic relationships** - Attach files to any model
- 🔗 **Flexible URL generation** - Stream, download, signed, and temporary URLs
- ⚡ **Lightweight** - Minimal dependencies and configuration
- 🧪 **Well tested** - Comprehensive test suite

## Installation

You can install the package via Composer:

```bash
composer require laraveljutsu/artifact
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag="laravel-artifact"
php artisan migrate
```

This will publish the migration file to your application.

## Basic Usage

### 1. Add the trait to your models

```php
use LaravelJutsu\Artifact\Concerns\HasArtifacts;

class User extends Model
{
    use HasArtifacts;
    
    public function avatar()
    {
        return $this->singleArtifact('avatar');
    }
    
    public function documents()
    {
        return $this->manyArtifacts('documents');
    }
}
```

### 2. Upload files

#### Single file upload (one-to-one)

```php
$user = User::find(1);
$file = request()->file('avatar');

// Store a single file
$artifact = $user->avatar()->store($file);
```

#### Multiple files upload (one-to-many)

```php
$user = User::find(1);

// Store multiple files
$files = request()->file('documents'); // Array of files
$artifacts = $user->documents()->store($files);

// Store a single file in a many relationship
$singleFile = request()->file('document');
$artifact = $user->documents()->store($singleFile);

// Store files from a Collection
$fileCollection = collect([
    $uploadedFile1,
    $uploadedFile2,
    $uploadedFile3
]);
$artifacts = $user->documents()->store($fileCollection);
```

### 3. Access uploaded files

#### Get file URLs

```php
$user = User::find(1);
$avatar = $user->avatar;

if ($avatar) {
    // Raw URL (for public disks only)
    $publicUrl = $avatar->rawUrl(); // Returns null for private disks
    
    // Stream URL (works for all disks)
    $streamUrl = $avatar->streamUrl();
    
    // Signed download URL
    $signedUrl = $avatar->signedUrl();
    
    // Temporary signed URL (expires in 60 minutes by default)
    $tempUrl = $avatar->temporarySignedUrl(120); // Expires in 120 minutes
    
    // File path (server-side access)
    $filePath = $avatar->url();
}
```

#### Access file metadata

```php
$artifact = $user->avatar;

echo $artifact->name;         // Generated hash name
echo $artifact->file_name;    // Original filename
echo $artifact->mime_type;    // MIME type
echo $artifact->size;         // File size in bytes
echo $artifact->collection;   // Collection name ('avatar')
echo $artifact->disk;         // Storage disk name
echo $artifact->file_hash;    // SHA256 hash of file content

// Check disk visibility
$artifact->isPublicDisk();    // true/false
$artifact->isPrivateDisk();   // true/false
```

## Advanced Usage

### Collections

Collections allow you to group related files together. They're particularly useful when a model needs different types of files:

```php
class Product extends Model
{
    use HasArtifacts;
    
    public function thumbnail()
    {
        return $this->singleArtifact('thumbnail');
    }
    
    public function gallery()
    {
        return $this->manyArtifacts('gallery');
    }
    
    public function documents()
    {
        return $this->manyArtifacts('documents');
    }
}

// Usage
$product = Product::find(1);

$product->thumbnail()->store($thumbnailFile);
$product->gallery()->store([$image1, $image2, $image3]);
$product->documents()->store($pdfFile);
```

### File Access Patterns

#### Public Files
For files stored on public disks, you can access them directly:

```php
$artifact = $user->avatar;

if ($artifact->isPublicDisk()) {
    $url = $artifact->rawUrl(); // Direct public URL
}
```

#### Private Files
For private files, use signed URLs or streaming routes:

```php
$artifact = $user->document;

if ($artifact->isPrivateDisk()) {
    $signedUrl = $artifact->signedUrl();           // Permanently signed
    $tempUrl = $artifact->temporarySignedUrl(60); // Expires in 60 minutes
    $streamUrl = $artifact->streamUrl();           // Stream content
}
```

### Working with Different Storage Disks

The package works with any Laravel filesystem disk:

```php
// Configure in config/filesystems.php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
    
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'visibility' => 'private',
    ],
];

// Files will be stored on the default disk unless specified
// The disk is automatically determined from your filesystems config
```

## API Endpoints

The package automatically registers several routes for file access:

- `GET /artifacts/{artifact}/stream` - Stream file content (inline viewing)
- `GET /artifacts/{artifact}/download` - Download file (requires signed URL)
- `GET /artifacts/{artifact}` - Get artifact metadata as JSON

### Example API Usage

```php
// Get artifact metadata
$response = Http::get("/artifacts/{$artifact->id}");

// The response includes:
// - id, name, file_name, mime_type, size, disk, collection
// - is_public, is_private
// - raw_url, stream_url, signed_url, temporary_signed_url
```

## File Deduplication

The package automatically handles file deduplication using SHA256 hashes. If the same file is uploaded multiple times, it will reference the same stored file but create separate artifact records.

## Error Handling

The package includes built-in error handling:

```php
try {
    $artifact = $user->documents()->store($file);
} catch (\InvalidArgumentException $e) {
    // Handle invalid file input
    return response()->json(['error' => $e->getMessage()], 400);
}

// For API endpoints, 404 errors are automatically returned for missing files
```

## Testing

The package includes comprehensive tests. Run the test suite:

```bash
composer test
```

You can also run individual test commands:

```bash
composer format  # Format code with Pint
composer analyse # Static analysis with PHPStan  
composer qa      # Run all quality assurance tools
```

### Testing in Your Application

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// In your tests
Storage::fake('local');

$file = UploadedFile::fake()->image('test.jpg');
$artifact = $user->avatar()->store($file);

$this->assertInstanceOf(Artifact::class, $artifact);
$this->assertTrue(Storage::disk('local')->exists($artifact->path));
```



## Requirements

- PHP 8.2+
- Laravel 10.0+ | 11.0+ | 12.0+

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [ludoguenet](https://github.com/ludoguenet)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.