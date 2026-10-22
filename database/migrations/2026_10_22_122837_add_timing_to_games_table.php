<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Unix timestamps rather than datetimes: the app runs on Sydney time, whose repeated hour when daylight saving ends would make a Game's time ambiguous.
        Schema::table('games', function (Blueprint $table) {
            $table->unsignedInteger('started_at')->nullable()->after('guesses');
            $table->unsignedInteger('finished_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'finished_at']);
        });
    }
};
