<?php

declare(strict_types=1);

namespace LaravelJutsu\Artifact;

use Illuminate\Support\ServiceProvider;
use LaravelJutsu\Artifact\Support\RelationMacros;

class ArtifactServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'laravel-artifact');

        RelationMacros::register();

        // Load routes automatically
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    public function register(): void
    {
        //
    }
}
