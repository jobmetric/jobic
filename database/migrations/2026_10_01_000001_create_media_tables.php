<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $media = config('media.tables.media', 'media');
        if (! Schema::hasTable($media)) {
            Schema::create($media, function (Blueprint $table) use ($media): void {
                $table->id();
                $table->string('name')->index();
                $table->foreignId('parent_id')->nullable()->constrained($media)->restrictOnDelete()->cascadeOnUpdate();
                $table->enum('type', ['c', 'f'])->index();
                $table->string('mime_type')->nullable()->index();
                $table->unsignedBigInteger('size')->default(0)->index();
                $table->string('content_id', 40)->nullable()->unique();
                $table->json('info')->nullable();
                $table->string('disk')->nullable()->index();
                $table->string('collection')->nullable()->default('public')->index();
                $table->uuid()->nullable()->unique();
                $table->string('extension')->nullable()->index();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        $paths = config('media.tables.media_path', 'media_paths');
        if (! Schema::hasTable($paths)) {
            Schema::create($paths, function (Blueprint $table) use ($media): void {
                $table->id();
                $table->foreignId('media_id')->index()->constrained($media)->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('path_id')->index()->constrained($media)->restrictOnDelete()->cascadeOnUpdate();
                $table->integer('level')->default(0);
                $table->unique(['media_id', 'path_id'], 'MEDIA_PATH_UNIQUE');
            });
        }

        $relations = config('media.tables.media_relation', 'media_relations');
        if (! Schema::hasTable($relations)) {
            Schema::create($relations, function (Blueprint $table) use ($media): void {
                $table->foreignId('media_id')->nullable()->index()->constrained($media)->cascadeOnUpdate()->cascadeOnDelete();
                $table->morphs('mediaable');
                $table->string('collection')->index();
                $table->dateTime('created_at')->index()->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->unique(['media_id', 'mediaable_type', 'mediaable_id', 'collection'], 'FILE_RELATION_UNIQUE');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(config('media.tables.media_relation', 'media_relations'));
        Schema::dropIfExists(config('media.tables.media_path', 'media_paths'));
        Schema::dropIfExists(config('media.tables.media', 'media'));
    }
};
