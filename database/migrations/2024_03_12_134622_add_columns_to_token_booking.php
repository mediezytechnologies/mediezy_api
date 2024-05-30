<?php

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
        Schema::table('token_booking', function (Blueprint $table) {
            $table->float('height');
            $table->float('weight');
            $table->integer('temperature');
            $table->integer('spo2');
            $table->integer('sys');
            $table->float('dia');
            $table->integer('heart_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('token_booking', function (Blueprint $table) {
            $table->dropColumn('height');
            $table->dropColumn('weight');
            $table->dropColumn('spo2');
            $table->dropColumn('sys');
            $table->dropColumn('dia');
            $table->dropColumn('heart_rate');
        });
    }
};
