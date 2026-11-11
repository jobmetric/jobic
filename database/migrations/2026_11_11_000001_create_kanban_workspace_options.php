<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('kanban_workspace_options', function (Blueprint $table): void {
            $table->string('scope', 160)->primary();
            $table->string('type', 80)->index();
            $table->foreignId('board_id')->nullable()->constrained('content_boards')->cascadeOnDelete();
            $table->json('options');
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('kanban_workspace_options');
    }
};
