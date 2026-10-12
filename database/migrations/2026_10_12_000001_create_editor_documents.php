<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editor_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_type', 150);
            $table->unsignedBigInteger('owner_id');
            $table->string('field', 80);
            $table->string('locale', 35);
            $table->json('content');
            $table->timestamps();
            $table->unique([
                'owner_type',
                'owner_id',
                'field',
                'locale',
            ], 'editor_documents_owner_field_locale_unique');
        });

        Schema::create('editor_document_revisions', function (Blueprint $table): void {
            $table->id();
            $table->string('revision_type', 150);
            $table->unsignedBigInteger('revision_id');
            $table->json('documents');
            $table->timestamps();
            $table->unique([
                'revision_type',
                'revision_id',
            ], 'editor_documents_revision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_document_revisions');
        Schema::dropIfExists('editor_documents');
    }
};
