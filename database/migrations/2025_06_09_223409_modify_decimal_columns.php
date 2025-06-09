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
        Schema::table('invent_historials', function (Blueprint $table) {
            try {
                $table->decimal('cantidad_anterior', 12, 3)->default(0)->change();
                $table->decimal('cantidad', 12, 3)->default(0)->change();
                $table->decimal('cantidad_despues', 12, 3)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
