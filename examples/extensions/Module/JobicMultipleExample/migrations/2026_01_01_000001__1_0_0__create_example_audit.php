<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Own only the removable example audit table.
 */
return new class extends Migration
{
    /**
     * Create the example-owned audit table without changing core tables.
     *
     * @return void
     * @throws Throwable
     */
    public function up(): void
    {
        Schema::create('jobic_example_multiple_audit', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('plugin_id')->nullable();
            $table->string('operation', 20);
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at');
        });
    }

    /**
     * Remove only this example's table during explicit uninstall.
     *
     * @return void
     * @throws Throwable
     */
    public function down(): void
    {
        Schema::dropIfExists('jobic_example_multiple_audit');
    }
};
