<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kanban_card_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained(config('post.tables.post', 'posts'))->cascadeOnDelete();
            $table->foreignId('depends_on_post_id')->constrained(config('post.tables.post', 'posts'))->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['post_id', 'depends_on_post_id']);
            $table->index('depends_on_post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_card_dependencies');
    }
};
