<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LaravelJutsu\Artifact\Artifact;
use LaravelJutsu\Artifact\Concerns\HasArtifacts;

class CustomArtifact extends Artifact
{
    use HasUuids;

    protected $table = 'artifacts';
}

class CustomParentModel extends \Illuminate\Database\Eloquent\Model
{
    use HasArtifacts;
    use HasUuids;

    protected $guarded = [];
    protected $table = 'parent_models';

    public function avatar()
    {
        return $this->singleArtifact('avatar');
    }
}

beforeEach(function () {
    Storage::fake('local');

    // Create a test table for our test model
    Schema::create('parent_models', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->timestamps();
    });
});

test('it uses custom artifact model with UUIDs', function () {
    Config::set('artifact.model', CustomArtifact::class);

    Schema::dropIfExists('artifacts');
    Schema::create('artifacts', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuidMorphs('artifactable');
        $table->string('name');
        $table->string('file_name');
        $table->string('mime_type');
        $table->string('path');
        $table->string('disk')->default('local');
        $table->string('file_hash', 64)->unique();
        $table->string('collection')->nullable();
        $table->unsignedBigInteger('size');
        $table->timestamps();
    });

    $model = CustomParentModel::create();
    $file = UploadedFile::fake()->image('avatar.jpg');

    $artifact = $model->avatar()->store($file);

    expect($artifact)->toBeInstanceOf(CustomArtifact::class)
        ->and($artifact->id)->toBeString()
        ->and(Str::isUuid($artifact->id))->toBeTrue();

    $this->assertDatabaseHas('artifacts', [
        'id' => $artifact->id,
        'file_name' => 'avatar.jpg',
    ]);
});

