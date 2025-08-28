<p align="center">
    <img src="art/logo.png" alt="Laravel Artifact Logo" height="280px">
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
    <a href="https://packagist.org/packages/laraveljutsu/laravel-artifact">
        <img src="https://img.shields.io/packagist/l/laraveljutsu/laravel-artifact?style=flat-square" alt="License">
    </a>
</p>

---

**Laravel Artifact** is a lightweight package for media management in Laravel applications. It makes file uploads, storage, and retrieval easy — with support for public/private disks, signed URLs, and automatic deduplication.

## ✨ Features

- Simple file upload and storage management
- Support for multiple storage disks (local, S3, etc.)
- Automatic deduplication to prevent duplicate files
- Signed URLs for secure file access
- Clean one-to-one and one-to-many file relationships
- Automatic metadata tracking (filename, MIME type, size)

## 📦 Installation

Install via Composer:

```bash
composer require laraveljutsu/laravel-artifact
```

Publish the configuration and run migrations:

```bash
php artisan vendor:publish --tag="laravel-artifact"
php artisan migrate
```

## 🚀 Usage

### 1. Add the trait to your model

```php
<?php

use LaravelJutsu\Artifact\Concerns\HasArtifacts;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasArtifacts;

    // Single file relationship
    public function avatar()
    {
        return $this->singleArtifact('avatar');
    }

    // Multiple files relationship
    public function documents()
    {
        return $this->manyArtifacts('documents');
    }
}
```

### 2. Store files

```php
$user = User::find(1);
$file = request()->file('avatar');

// Single file (one-to-one)
$artifact = $user->avatar()->store($file);

// Multiple files (one-to-many)
$artifacts = $user->documents()->store(request()->file('documents'));

// Specify storage disk
$artifact = $user->avatar()->store($file); // Uses default disk
$artifacts = $user->documents()->store($files, 'public');
```

### 3. Access files and metadata

```php
$avatar = $user->avatar;

if ($avatar) {
    // Get URLs
    echo $avatar->rawUrl();            // Direct URL (public disks only)
    echo $avatar->streamUrl();         // Streaming URL (works for all disks)
    echo $avatar->signedUrl();         // Permanent signed URL
    echo $avatar->temporarySignedUrl(60); // Expiring signed URL (60 minutes)

    // Access metadata
    echo $avatar->file_name;   // Original filename
    echo $avatar->mime_type;   // File MIME type
    echo $avatar->size;        // File size in bytes
    echo $avatar->disk;        // Storage disk name
}

// Working with multiple files
foreach ($user->documents as $document) {
    echo $document->file_name . ' (' . $document->size . ' bytes)';
    echo $document->streamUrl();
}
```

## 📋 Requirements

- PHP 8.2+
- Laravel 10+

## 📄 License

The MIT License (MIT). Please see [License File](https://opensource.org/license/mit) for more information.