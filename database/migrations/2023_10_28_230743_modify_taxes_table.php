<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('taxes', function (Blueprint $table) {
                $table->renameColumn('name', 'descripcion');
            });
            Schema::table('taxes', function (Blueprint $table) {
                $table->string("tipo_factor");
                $table->string("c_impuesto");
            });
            Schema::table('taxes', function (Blueprint $table) {
                $table->renameColumn('porcentaje', 'tasa_cuota');
            });
            Schema::table('taxes', function (Blueprint $table) {
                $table->dropColumn("base_gravable");
            });
            return;
        }
        Schema::table('taxes', function (Blueprint $table) {
            $table->renameColumn('name', 'descripcion');
            $table->renameColumn('porcentaje', 'tasa_cuota');
            $table->string("c_impuesto");
            $table->string("tipo_factor");
            $table->dropColumn("base_gravable");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
        });
    }
};
