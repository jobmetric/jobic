<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow the reserved Gravatar value in the avatar preset column.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->smallInteger('avatar_preset')->nullable()->change();
        });
    }

    /**
     * Restore the unsigned preset-only avatar column.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('avatar_preset')->nullable()->change();
        });
    }
};
