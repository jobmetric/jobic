<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_board_columns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->constrained('content_boards')->cascadeOnDelete();
            $table->string('type', 80);
            $table->string('status', 80);
            $table->unsignedBigInteger('version')->default(0);
            $table->unique(['board_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_board_columns');
    }
};
