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
            // Índice compuesto optimizado para getVentaticketAlmacenCliente
            // Orden por selectividad: user_id es más específico primero
            $table->index(['user_id', 'organization_id', 'esta_abierto', 'pendiente'], 'idx_user_org_open_pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventatickets', function (Blueprint $table) {
            $table->dropIndex('idx_user_org_open_pending');
            $table->dropIndex('idx_org_user_open_pending');
        });
    }
};
