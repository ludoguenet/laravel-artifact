<?php

declare(strict_types=1);

namespace LaravelJutsu\Artifact\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use LaravelJutsu\Artifact\Artifact;

class RelationMacros
{
    public static function register(): void
    {
        MorphOne::macro('store', function ($file) {
            /** @var MorphOne<Model, Model> $this */
            $parent = $this->getParent();
            // Find the collection value from the where clauses
            $collection = 'default';
            foreach ($this->getQuery()->getQuery()->wheres as $where) {
                if (isset($where['column']) && $where['column'] === 'collection') {
                    $collection = $where['value'];
                    break;
                }
            }

            // Note: For MorphOne relationships, ideally we should delete existing artifacts
            // but for now we'll skip that complexity

            Artifact::deletePrevious($parent, $collection);

            $artifact = Artifact::fromUpload($file, $collection);

            if (! method_exists($parent, 'singleArtifact')) {
                abort(500, "get_class($parent) must define a singleArtifact() relationship to use MorphOne::store()");
            }

            $parent->singleArtifact($collection)->save($artifact);

            return $artifact;
        });

        MorphMany::macro('store', function ($files) {
            /** @var MorphMany<Model, Model> $this */
            $parent = $this->getParent();
            // Find the collection value from the where clauses
            $collection = 'default';
            foreach ($this->getQuery()->getQuery()->wheres as $where) {
                if (isset($where['column']) && $where['column'] === 'collection') {
                    $collection = $where['value'];
                    break;
                }
            }

            if (! method_exists($parent, 'manyArtifacts')) {
                abort(500, "get_class($parent) must define a manyArtifacts() relationship to use MorphMany::store()");
            }

            // Normalize input to a collection
            if ($files instanceof UploadedFile) {
                // Single file
                $fileCollection = collect([$files]);
            } elseif (is_array($files)) {
                // Array of files
                $fileCollection = collect($files);
            } elseif ($files instanceof Collection) {
                // Already a collection
                $fileCollection = $files;
            } else {
                throw new \InvalidArgumentException('Files must be an UploadedFile, array of UploadedFiles, or Collection of UploadedFiles');
            }

            $artifacts = collect();

            foreach ($fileCollection as $file) {
                if (! $file instanceof UploadedFile) {
                    throw new \InvalidArgumentException('All items must be UploadedFile instances');
                }

                $artifact = Artifact::fromUpload($file, $collection);
                $parent->manyArtifacts($collection)->save($artifact);
                $artifacts->push($artifact);
            }

            // Return single artifact if single file was passed, collection otherwise
            return $files instanceof UploadedFile ? $artifacts->first() : $artifacts;
        });
    }
}
