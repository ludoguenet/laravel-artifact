<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    // Create a test table for our test model
    Schema::create('parent_models', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });
});

class ParentModel extends \Illuminate\Database\Eloquent\Model
{
    use \LaravelJutsu\Artifact\Concerns\HasArtifacts;

    protected $fillable = ['*'];

    protected $table = 'parent_models';

    public function avatar()
    {
        return $this->singleArtifact('avatar');
    }

    public function podcasts()
    {
        return $this->manyArtifacts('podcasts');
    }

    public function audiobooks()
    {
        return $this->manyArtifacts('audiobooks');
    }
}

describe('Macro', function () {
    it('verifies macro is registered', function () {
        expect(\Illuminate\Database\Eloquent\Relations\MorphOne::hasMacro('store'))
            ->toBeTrue('MorphOne store macro should be registered');
    });
});

describe('Upload files', function () {
    it('can store a file for singleArtifact relationship', function () {
        $model = ParentModel::create([]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg');

        $avatarArtifact = $model->avatar()->store($file);

        expect($avatarArtifact)->toBeInstanceOf(\LaravelJutsu\Artifact\Artifact::class)
            ->and($avatarArtifact->mime_type)->toBe('image/jpeg')
            ->and($avatarArtifact->size)->toBe($file->getSize())
            ->and($avatarArtifact->collection)->toBe('avatar')
            ->and(Storage::disk('local')->exists($avatarArtifact->path))->toBeTrue();
    });

    it('can delete previous file for singleArtifact relationship', function () {
        $model = ParentModel::create([]);

        // Upload first file
        $previousFile = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg');
        $avatarArtifact = $model->avatar()->store($previousFile);

        $previousPath = $avatarArtifact->path;

        // Upload second file
        $newFile = \Illuminate\Http\UploadedFile::fake()->image('new-avatar.png');
        $model->avatar()->store($newFile);

        Storage::disk('local')->assertMissing($previousPath);
    });

    it('can store multiple files for singleArtifact relationship', function () {
        $model = ParentModel::create([]);

        // Store first file
        $firstFile = \Illuminate\Http\UploadedFile::fake()->image('avatar1.jpg', 100, 100);
        $firstArtifact = $model->avatar()->store($firstFile);

        expect($firstArtifact)->toBeInstanceOf(\LaravelJutsu\Artifact\Artifact::class)
            ->and($firstArtifact->file_name)->toBe('avatar1.jpg')
            ->and($firstArtifact->collection)->toBe('avatar')
            ->and(Storage::disk('local')->exists($firstArtifact->path))->toBeTrue();

        // Store second file with different dimensions to ensure different hash
        $secondFile = \Illuminate\Http\UploadedFile::fake()->image('avatar2.jpg', 200, 200);
        $secondArtifact = $model->avatar()->store($secondFile);

        expect($secondArtifact)->toBeInstanceOf(\LaravelJutsu\Artifact\Artifact::class)
            ->and($secondArtifact->file_name)->toBe('avatar2.jpg')
            ->and($secondArtifact->collection)->toBe('avatar')
            ->and(Storage::disk('local')->exists($secondArtifact->path))->toBeTrue();
    });

    it('can store audio files for manyArtifacts relationship with array', function () {
        $model = ParentModel::create([]);

        // Create audio files with different content to ensure different hashes
        $podcast = \Illuminate\Http\UploadedFile::fake()->createWithContent('podcast_episode_1.mp3', 'audio content 1', 'audio/mpeg');
        $podcast2 = \Illuminate\Http\UploadedFile::fake()->createWithContent('podcast_episode_2.mp3', 'different audio content 2', 'audio/mpeg');
        $podcast3 = \Illuminate\Http\UploadedFile::fake()->createWithContent('podcast_episode_3.wav', 'unique wav content 3', 'audio/wav');

        $artifacts = $model->podcasts()->store([
            $podcast,
            $podcast2,
            $podcast3,
        ]);

        expect($artifacts)->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($artifacts)->toHaveCount(3)
            ->and($artifacts->first())->toBeInstanceOf(\LaravelJutsu\Artifact\Artifact::class)
            ->and(Storage::disk('local')->exists($artifacts->first()->path))->toBeTrue()
            ->and(Storage::disk('local')->exists($artifacts->get(1)->path))->toBeTrue()
            ->and(Storage::disk('local')->exists($artifacts->get(2)->path))->toBeTrue();
    });

    it('can store a single audio file for manyArtifacts relationship', function () {
        $model = ParentModel::create([]);

        $podcast = \Illuminate\Http\UploadedFile::fake()->createWithContent('single_podcast.mp3', 'single podcast audio content', 'audio/mpeg');

        $artifact = $model->podcasts()->store($podcast);

        expect($artifact)->toBeInstanceOf(\LaravelJutsu\Artifact\Artifact::class)
            ->and($artifact->file_name)->toBe('single_podcast.mp3')
            ->and($artifact->collection)->toBe('podcasts')
            ->and(Storage::disk('local')->exists($artifact->path))->toBeTrue();
    });

    it('can store audio files for manyArtifacts relationship with Collection', function () {
        $model = ParentModel::create([]);

        $files = collect([
            \Illuminate\Http\UploadedFile::fake()->createWithContent('audiobook_chapter_1.mp3', 'chapter 1 audio content', 'audio/mpeg'),
            \Illuminate\Http\UploadedFile::fake()->createWithContent('audiobook_chapter_2.m4a', 'chapter 2 different audio', 'audio/mp4'),
        ]);

        $artifacts = $model->audiobooks()->store($files);

        expect($artifacts)->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($artifacts)->toHaveCount(2)
            ->and($artifacts->first())->toBeInstanceOf(\LaravelJutsu\Artifact\Artifact::class)
            ->and($artifacts->first()->file_name)->toBe('audiobook_chapter_1.mp3')
            ->and($artifacts->get(1)->file_name)->toBe('audiobook_chapter_2.m4a')
            ->and(Storage::disk('local')->exists($artifacts->first()->path))->toBeTrue()
            ->and(Storage::disk('local')->exists($artifacts->get(1)->path))->toBeTrue();
    });

    it('throws exception when storing invalid input for manyArtifacts relationship', function () {
        $model = ParentModel::create([]);

        expect(fn () => $model->audiobooks()->store('invalid'))
            ->toThrow(\InvalidArgumentException::class, 'Files must be an UploadedFile, array of UploadedFiles, or Collection of UploadedFiles');
    });

    it('throws exception when storing array with non-UploadedFile items for manyArtifacts relationship', function () {
        $model = ParentModel::create([]);

        expect(fn () => $model->audiobooks()->store([
            \Illuminate\Http\UploadedFile::fake()->createWithContent('valid_audio.mp3', 'valid audio content', 'audio/mpeg'),
            'invalid_item',
        ]))->toThrow(\InvalidArgumentException::class, 'All items must be UploadedFile instances');
    });
});
