<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Own the schema marker introduced by this example's 1.1.0 upgrade.
 */
return new class extends Migration
{
    /**
     * Create only the upgrade's independently owned table.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('jobic_example_plugin_upgrade', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Remove only this example's upgrade marker during uninstall.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('jobic_example_plugin_upgrade');
    }
};
