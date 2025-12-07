<?php

declare(strict_types=1);

namespace LaravelJutsu\Artifact\Concerns;

trait HasArtifacts
{
    /**
     * Get all artifacts for this model (used by Artifact::deletePrevious)
     */
    public function artifacts(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(config('artifact.model'), 'artifactable');
    }

    public function singleArtifact(string $collection): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        $relation = $this->morphOne(config('artifact.model'), 'artifactable')
            ->where('collection', $collection);

        // Store the collection on the relation instance for the macro to use
        $relation->collectionName = $collection;

        return $relation;
    }

    public function manyArtifacts(string $collection): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        $relation = $this->morphMany(config('artifact.model'), 'artifactable')
            ->where('collection', $collection);

        // Store the collection on the relation instance for the macro to use
        $relation->collectionName = $collection;

        return $relation;
    }
}
