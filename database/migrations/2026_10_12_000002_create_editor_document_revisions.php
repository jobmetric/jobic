<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editor_document_revisions', function (Blueprint $table): void {
            $table->id();
            $table->string('revision_type', 150);
            $table->unsignedBigInteger('revision_id');
            $table->json('documents');
            $table->timestamps();
            $table->unique(['revision_type', 'revision_id'], 'editor_documents_revision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_document_revisions');
    }
};
