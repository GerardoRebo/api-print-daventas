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
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn('ventas_tarjeta');
        });
        Schema::table('turnos', function (Blueprint $table) {
            $table->decimal('ventas_tarjeta_debito', 12, 2)->default(0);
            $table->decimal('ventas_tarjeta_credito', 12, 2)->default(0);
            $table->decimal('ventas_transferencia', 12, 2)->default(0);
            $table->decimal('ventas_cheque', 12, 2)->default(0);
            $table->decimal('ventas_vales_de_despensa', 12, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->decimal('ventas_tarjeta');
            $table->dropColumn('ventas_tarjeta_debito');
            $table->dropColumn('ventas_tarjeta_credito');
            $table->dropColumn('ventas_transferencia');
            $table->dropColumn('ventas_cheque');
            $table->dropColumn('ventas_vales_de_despensa');
        });
    }
};
