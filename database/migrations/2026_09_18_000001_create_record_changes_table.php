<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_changes', function (Blueprint $table): void {
            $table->id();
            $table->string('resource', 80);
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('operation', 30);
            $table->json('changes');
            $table->timestamp('created_at');
            $table->index(['resource', 'record_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_changes');
    }
};
