<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelJutsu\Artifact\Http\Controllers\ArtifactController;

/*
|--------------------------------------------------------------------------
| Artifact Routes
|--------------------------------------------------------------------------
|
| These routes are automatically loaded by the package and provide
| secure access to artifact files through streaming and downloads.
|
*/

Route::prefix('artifacts')->name('artifact.')->group(function () {
    // Stream route - for inline viewing (not signed, but could be rate limited)
    Route::get('{artifact}/stream', [ArtifactController::class, 'stream'])
        ->name('stream')
        ->where('artifact', '[0-9]+');

    // Download route - requires signed URL for security
    Route::get('{artifact}/download', [ArtifactController::class, 'download'])
        ->name('download')
        ->middleware('signed')
        ->where('artifact', '[0-9]+');

    // Show route - for retrieving artifact metadata (useful for APIs)
    Route::get('{artifact}', [ArtifactController::class, 'show'])
        ->name('show')
        ->where('artifact', '[0-9]+');
});
