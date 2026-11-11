<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_boards', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 80)->index();
            $table->string('name', 160);
            $table->json('roles');
            $table->json('users');
            $table->json('states');
            $table->json('filters');
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_boards');
    }
};
