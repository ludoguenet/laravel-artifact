<?php

declare(strict_types=1);

namespace LaravelJutsu\Artifact\Concerns;

use LaravelJutsu\Artifact\Artifact;

trait HasArtifacts
{
    /**
     * Get all artifacts for this model (used by Artifact::deletePrevious)
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Artifact>
     */
    public function artifacts(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Artifact::class, 'artifactable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<Artifact, $this>
     */
    public function singleArtifact(string $collection): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        $relation = $this->morphOne(Artifact::class, 'artifactable')
            ->where('collection', $collection);

        // Store the collection on the relation instance for the macro to use
        $relation->collectionName = $collection;

        return $relation;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Artifact>
     */
    public function manyArtifacts(string $collection): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        $relation = $this->morphMany(Artifact::class, 'artifactable')
            ->where('collection', $collection);

        // Store the collection on the relation instance for the macro to use
        $relation->collectionName = $collection;

        return $relation;
    }
}
