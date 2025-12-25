<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('favorites')
            ->whereNotNull('post_id')
            ->update([
                'favoritable_type' => 'App\\Models\\Post',
                'favoritable_id' => DB::raw('post_id')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as we'd lose the polymorphic data
        // The post_id column will be removed in a separate migration
    }
};
