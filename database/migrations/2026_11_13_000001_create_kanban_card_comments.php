<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kanban_card_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained(config('post.tables.post', 'posts'))->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['post_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_card_comments');
    }
};
