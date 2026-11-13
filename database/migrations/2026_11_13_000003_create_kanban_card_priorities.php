<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kanban_card_priorities', function (Blueprint $table): void {
            $table->foreignId('post_id')->primary()->constrained(config('post.tables.post', 'posts'))->cascadeOnDelete();
            $table->string('priority', 12)->default('normal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_card_priorities');
    }
};
