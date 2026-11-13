<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kanban_card_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained(config('post.tables.post', 'posts'))->cascadeOnDelete();
            $table->unsignedBigInteger('revision_id');
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 12)->default('pending');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['post_id', 'revision_id', 'reviewer_id'], 'kanban_review_unique');
            $table->index(['reviewer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_card_reviews');
    }
};
