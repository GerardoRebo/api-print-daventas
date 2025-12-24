<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * IMPORTANTE: Ejecuta esta migration SOLO después de confirmar que:
     * 1. Los nuevos índices compuestos están funcionando correctamente
     * 2. Todas las queries usan los índices nuevos
     * 3. Has revisado TODAS las queries en la aplicación que filtren por user_id u organization_id
     * 
     * Para verificar antes de eliminar, ejecuta:
     * SHOW INDEXES FROM ventatickets WHERE Column_name IN ('user_id', 'organization_id');
     */
    public function up(): void
    {
        Schema::table('ventatickets', function (Blueprint $table) {
            // Estos índices serán reemplazados por los índices compuestos:
            // - idx_user_org_open_pending
            // - idx_org_user_open_pending

            // Solo descomenta cuando estés 100% seguro
            // $table->dropIndex('ventatickets_user_id_foreign');
            // $table->dropIndex('ventatickets_organization_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventatickets', function (Blueprint $table) {
            // Recrear los índices si es necesario
            // $table->index('user_id', 'ventatickets_user_id_foreign');
            // $table->index('organization_id', 'ventatickets_organization_id_foreign');
        });
    }
};
