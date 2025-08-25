<?php

declare(strict_types=1);

namespace LaravelJutsu\Artifact\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use LaravelJutsu\Artifact\Artifact;

class RelationMacros
{
    public static function register(): void
    {
        Relation::macro('store', function ($files) {
            /** @var Relation<Model, Model, mixed> $this */
            $parent = $this->getParent();

            // Determine collection
            $collection = $this->collectionName ?? 'default';

            if ($collection === 'default') {
                foreach ($this->getQuery()->getQuery()->wheres as $where) {
                    if (isset($where['column']) && $where['column'] === 'collection') {
                        $collection = $where['value'];
                        break;
                    }
                }
            }

            // Handle MorphOne
            if ($this instanceof MorphOne) {
                Artifact::deletePrevious($parent, $collection);

                $artifact = Artifact::fromUpload($files, $collection);

                if (! method_exists($parent, 'singleArtifact')) {
                    abort(500, get_class($parent).' must define a singleArtifact() relationship to use MorphOne::store()');
                }

                $parent->singleArtifact($collection)->save($artifact);

                return $artifact;
            }

            // Handle MorphMany
            if ($this instanceof MorphMany) {
                if (! method_exists($parent, 'manyArtifacts')) {
                    abort(500, get_class($parent).' must define a manyArtifacts() relationship to use MorphMany::store()');
                }

                // Normalize input to a collection
                if ($files instanceof UploadedFile) {
                    $fileCollection = collect([$files]);
                } elseif (is_array($files)) {
                    $fileCollection = collect($files);
                } elseif ($files instanceof Collection) {
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

                return $files instanceof UploadedFile ? $artifacts->first() : $artifacts;
            }

            throw new \BadMethodCallException('store() is only supported on MorphOne or MorphMany relations.');
        });
    }
}
