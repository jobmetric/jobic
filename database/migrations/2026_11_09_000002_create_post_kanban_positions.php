<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('post_kanban_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('column_id')->constrained('post_kanban_columns')->cascadeOnDelete();
            $table->unsignedBigInteger('post_id')->unique();
            $table->bigInteger('rank');
            $table->index(['column_id', 'rank', 'post_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('post_kanban_positions'); }
};
