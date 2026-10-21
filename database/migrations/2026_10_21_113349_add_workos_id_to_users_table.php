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
        Schema::table('users', function (Blueprint $table) {
            $table->string('workos_id')->nullable()->unique()->after('id');
            $table->string('password')->nullable()->change();
            // Players are matched by workos_id, so an address one player has left can't block whoever takes it next.
            $table->dropUnique(['email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['workos_id']);
            $table->dropColumn('workos_id');
            $table->unique('email');
        });
    }
};
