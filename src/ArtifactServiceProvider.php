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
            __DIR__.'/../config/artifact.php' => config_path('artifact.php'),
        ], 'laravel-artifact');

        // Load routes automatically
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        RelationMacros::register();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/artifact.php', 'artifact');
    }
}
