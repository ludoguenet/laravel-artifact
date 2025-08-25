<p align="center">
    <img src="art/logo.png" alt="Laravel Artifact Logo">
</p>

<p align="center">
    <a href="https://packagist.org/packages/laraveljutsu/laravel-artifact">
        <img src="https://img.shields.io/packagist/v/laraveljutsu/laravel-artifact.svg?style=flat-square" alt="Latest Version on Packagist">
    </a>
    <a href="https://laravel.com">
        <img src="https://img.shields.io/badge/Laravel-10.0%2B-FF2D20?style=flat&logo=laravel" alt="Laravel Version">
    </a>
    <a href="https://packagist.org/packages/laraveljutsu/laravel-artifact">
        <img src="https://img.shields.io/packagist/dt/laraveljutsu/laravel-artifact.svg?style=flat-square" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/ludovicguenet/laravel-artifact">
        <img src="http://poser.pugx.org/ludovicguenet/whizbang/license" alt="License">
    </a>
</p>

---

**Laravel Artifact** is a lightweight package for media management.
It makes file uploads, storage, and retrieval easy — with support for public/private disks, signed URLs, and automatic deduplication.

---

## Installation

```bash
composer require laraveljutsu/laravel-artifact

php artisan vendor:publish --tag="laravel-artifact"
php artisan migrate
```

## Usage
1. Add the trait to your model

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

2. Store files

```php
$user = User::find(1);
$file = request()->file('avatar');

// One-to-one
$artifact = $user->avatar()->store($file);

// One-to-many
$artifacts = $user->documents()->store(request()->file('documents'));

// Working with any storage disk
$artifact = $user->avatar()->store($file); // Default disk used
$artifacts = $user->documents()->store($files, 'public');
```

3. Access files

```php
$avatar = $user->avatar;

// URLs
$avatar->rawUrl();            // Direct (public only)
$avatar->streamUrl();         // Works for all disks
$avatar->signedUrl();         // Permanent signed
$avatar->temporarySignedUrl(60); // Expiring signed

// Metadata
$avatar->file_name;   // Original name
$avatar->mime_type;   // MIME type
$avatar->size;        // File size in bytes
$avatar->disk;        // Disk name
```

## Requirements
- PHP 8.2+
- Laravel 10 / 11 / 12

## License
The MIT License (MIT). See LICENSE