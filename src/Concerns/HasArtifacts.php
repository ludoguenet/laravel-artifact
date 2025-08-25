<?php

declare(strict_types=1);

namespace LaravelJutsu\Artifact\Concerns;

use LaravelJutsu\Artifact\Artifact;

trait HasArtifacts
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<Artifact, $this>
     */
    public function singleArtifact(string $collection): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Artifact::class, 'artifactable')
            ->where('collection', $collection);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Artifact>
     */
    public function manyArtifacts(string $collection): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Artifact::class, 'artifactable')
            ->where('collection', $collection);
    }
}
