<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_board_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('column_id')->constrained('content_board_columns')->cascadeOnDelete();
            $table->unsignedBigInteger('post_id');
            $table->bigInteger('rank');
            $table->unique(['column_id', 'post_id']);
            $table->index(['column_id', 'rank', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_board_positions');
    }
};
