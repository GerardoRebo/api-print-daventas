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
            $table->decimal('fp_efectivo', 12, 2)->default(0);
            $table->string('fp_efectivo_ref', 50)->nullable();
            $table->decimal('fp_tarjeta_debito', 12, 2)->default(0);
            $table->string('fp_tarjeta_debito_ref', 50)->nullable();
            $table->decimal('fp_tarjeta_credito', 12, 2)->default(0);
            $table->string('fp_tarjeta_credito_ref', 50)->nullable();
            $table->decimal('fp_transferencia', 12, 2)->default(0);
            $table->string('fp_transferencia_ref', 50)->nullable();
            $table->decimal('fp_cheque', 12, 2)->default(0);
            $table->string('fp_cheque_ref', 50)->nullable();
            $table->decimal('fp_vales_de_despensa', 12, 2)->default(0);
            $table->string('fp_vales_de_despensa_ref', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventatickets', function (Blueprint $table) {
            $table->dropColumn('fp_efectivo');
            $table->dropColumn('fp_efectivo_ref');
            $table->dropColumn('fp_tarjeta_debito');
            $table->dropColumn('fp_tarjeta_debito_ref');
            $table->dropColumn('fp_tarjeta_credito');
            $table->dropColumn('fp_tarjeta_credito_ref');
            $table->dropColumn('fp_transferencia');
            $table->dropColumn('fp_transferencia_ref');
            $table->dropColumn('fp_cheque');
            $table->dropColumn('fp_cheque_ref');
            $table->dropColumn('fp_vales_de_despensa');
            $table->dropColumn('fp_vales_de_despensa_ref');
        });
    }
};
