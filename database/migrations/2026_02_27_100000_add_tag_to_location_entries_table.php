<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_entries', function (Blueprint $table) {
            $table->string('tag', 120)->nullable()->after('title');
            $table->index(['user_id', 'tag']);
        });

        DB::table('location_entries')
            ->whereNull('tag')
            ->update(['tag' => DB::raw('title')]);
    }

    public function down(): void
    {
        Schema::table('location_entries', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'tag']);
            $table->dropColumn('tag');
        });
    }
};
