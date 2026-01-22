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
        Schema::table('ventatickets', function (Blueprint $table) {
            $table->string('cfdi_cancellation_status', 30)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventatickets', function (Blueprint $table) {
            $table->string('cfdi_cancellation_status', 12)->change();
        });
    }
};
