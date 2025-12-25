<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->nullable()
                ->default(null)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove user favorites (they don't have post_id) before making post_id NOT NULL
        \Illuminate\Support\Facades\DB::table('favorites')
            ->whereNull('post_id')
            ->delete();

        Schema::table('favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->nullable(false)->change();
        });
    }
};
