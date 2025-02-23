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
        Schema::table('taxes', function (Blueprint $table) {
            try {
                $table->decimal('tasa_cuota', 8, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('departamentos', function (Blueprint $table) {
            try {
                $table->decimal('porcentaje', 8, 2)->nullable()->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('clientes', function (Blueprint $table) {
            try {
                $table->decimal('total_ventas', 12, 2)->default(0)->change();
                $table->decimal('total_ganancias', 12, 2)->default(0)->change();
                $table->decimal('saldo_actual', 12, 2)->default(0)->change();
                $table->decimal('limite_credito', 10, 2)->nullable()->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('products', function (Blueprint $table) {
            try {
                $table->decimal('pcosto', 10, 2)->default(0)->change();
                $table->decimal('ucosto', 10, 2)->default(0)->change();
                $table->decimal('costoPromedio', 10, 2)->default(0)->change();
                $table->decimal('porcentaje_ganancia', 8, 2)->nullable()->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('turnos', function (Blueprint $table) {
            try {
                $table->decimal('dinero_inicial', 10, 2)->default(0)->change();
                $table->decimal('acumulado_ventas', 10, 2)->default(0)->change();
                $table->decimal('acumulado_entradas', 10, 2)->default(0)->change();
                $table->decimal('acumulado_salidas', 10, 2)->default(0)->change();
                $table->decimal('acumulado_ganancias', 10, 2)->default(0)->change();
                $table->decimal('compras', 10, 2)->default(0)->change();
                $table->decimal('ventas_efectivo', 10, 2)->default(0)->change();
                $table->decimal('ventas_tarjeta', 10, 2)->default(0)->change();
                $table->decimal('ventas_credito', 10, 2)->default(0)->change();
                $table->decimal('efectivo_al_cierre', 10, 2)->default(0)->change();
                $table->decimal('diferencia', 10, 2)->default(0)->change();
                $table->tinyText('comments')->change();
                $table->decimal('devoluciones_ventas_efectivo', 10, 2)->default(0)->change();
                $table->decimal('devoluciones_ventas_credito', 10, 2)->default(0)->change();
                $table->decimal('abonos_tarjeta', 10, 2)->default(0)->change();
                $table->decimal('comisiones_tarjeta', 10, 2)->default(0)->change();
                $table->decimal('devoluciones_ventas', 10, 2)->default(0)->change();
                $table->decimal('abonos_efectivo', 10, 2)->default(0)->change();
                $table->decimal('devoluciones_abonos_efectivo', 10, 2)->default(0)->change();

            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('orden_compras', function (Blueprint $table) {
            try {
                $table->decimal('utilidad_enviado', 12, 2)->default(0)->change();
                $table->decimal('impuestos_enviado', 12, 2)->default(0)->change();
                $table->decimal('subtotal_enviado', 12, 2)->default(0)->change();
                $table->decimal('total_enviado', 12, 2)->default(0)->change();
                $table->decimal('utilidad_recibido', 12, 2)->default(0)->change();
                $table->decimal('impuestos_recibido', 12, 2)->default(0)->change();
                $table->decimal('subtotal_recibido', 12, 2)->default(0)->change();
                $table->decimal('total_recibido', 12, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('articulos_ocs', function (Blueprint $table) {
            try {
                $table->decimal('cantidad_ordenada', 10, 3)->default(0)->change();
                $table->decimal('cantidad_recibida', 10, 3)->default(0)->change();
                $table->decimal('costo_al_ordenar', 10, 2)->default(0)->change();
                $table->decimal('costo_al_recibir', 10, 2)->default(0)->change();
                $table->decimal('utilidad_estimada_al_ordenar', 10, 2)->default(0)->change();
                $table->decimal('utilidad_estimada_al_recibir', 10, 2)->default(0)->change();
                $table->decimal('impuestos_al_recibir', 10, 2)->default(0)->change();
                $table->decimal('subtotal_al_recibir', 10, 2)->default(0)->change();
                $table->decimal('total_al_ordenar', 10, 2)->default(0)->change();
                $table->decimal('total_al_recibir', 10, 2)->default(0)->change();
                $table->decimal('subtotal_al_recibir', 10, 2)->default(0)->change();
                $table->decimal('precio_sin_impuestos', 10, 2)->default(0)->change();
                $table->decimal('precio_con_impuestos', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('inventario_balances', function (Blueprint $table) {
            try {
                $table->decimal('invmin', 10, 3)->nullable()->change();
                $table->decimal('invmax', 10, 3)->nullable()->change();
                $table->decimal('cantidad_actual', 10, 3)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('precios', function (Blueprint $table) {
            try {
                $table->decimal('precio', 10, 2)->default(0)->change();
                $table->decimal('precio_mayoreo', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('inventario_ajustes', function (Blueprint $table) {
            try {
                $table->decimal('cantidad', 10, 3)->default(0)->change();
                $table->decimal('costo_unitario', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('ventatickets', function (Blueprint $table) {
            try {
                $table->decimal('subtotal', 12, 2)->default(0)->change();
                $table->decimal('impuestos', 12, 2)->default(0)->change();
                $table->decimal('total', 12, 2)->default(0)->change();
                $table->decimal('ganancia', 12, 2)->default(0)->change();
                $table->decimal('pago_con', 12, 2)->default(0)->change();
                $table->decimal('total_devuelto', 12, 2)->default(0)->change();
                $table->decimal('total_ahorrado', 12, 2)->default(0)->change();
                $table->decimal('total_credito', 12, 2)->default(0)->change();
                $table->decimal('descuento', 12, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('ventaticket_articulos', function (Blueprint $table) {
            try {
                $table->decimal('precio_final', 10, 2)->default(0)->change();
                $table->decimal('cantidad', 10, 3)->default(0)->change();
                $table->decimal('ganancia', 10, 2)->default(0)->change();
                $table->decimal('impuesto_unitario', 10, 2)->default(0)->change();
                $table->decimal('precio_usado', 10, 2)->default(0)->change();
                $table->decimal('cantidad_devuelta', 10, 3)->default(0)->change();
                $table->decimal('porcentaje_pagado', 10, 2)->default(0)->change();
                $table->decimal('importe_devuelto', 10, 2)->default(0)->change();
                $table->decimal('descuento', 10, 2)->default(0)->change();
                $table->decimal('importe_descuento', 10, 2)->default(0)->change();

            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('deudas', function (Blueprint $table) {
            try {
                $table->decimal('deuda', 12, 2)->default(0)->change();
                $table->decimal('saldo', 12, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('abonos', function (Blueprint $table) {
            try {
                $table->decimal('abono', 10, 2)->default(0)->change();
                $table->decimal('saldo', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('devoluciones', function (Blueprint $table) {
            try {
                $table->decimal('total_devuelto', 12, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('devoluciones_articulos', function (Blueprint $table) {
            try {
                $table->decimal('cantidad_devuelta', 10, 3)->default(0)->change();
                $table->decimal('dinero_devuelto', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('inventario_historials', function (Blueprint $table) {
            try {
                $table->decimal('costo_despues', 10, 2)->default(0)->change();
                $table->decimal('cantidad_anterior', 10, 3)->default(0)->change();
                $table->decimal('cantidad', 10, 3)->default(0)->change();
                $table->decimal('costo_unitario', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('histories', function (Blueprint $table) {
            try {
                $table->decimal('costo_anterior', 10, 2)->default(0)->change();
                $table->decimal('costo_despues', 10, 2)->default(0)->change();
                $table->decimal('cantidad', 10, 3)->default(0)->change();
                $table->decimal('cantidad_anterior', 10, 3)->default(0)->change();
                $table->decimal('cantidad_posterior', 10, 3)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            try {
                $table->decimal('cantidad', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('articulo_taxes', function (Blueprint $table) {
            try {
                $table->decimal('base', 10, 2)->default(0)->change();
                $table->decimal('importe', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('descuentos', function (Blueprint $table) {
            try {
                $table->decimal('desde', 8, 3)->default(0)->change();
                $table->decimal('hasta', 8, 3)->default(0)->change();
                $table->decimal('porcentaje_descuento', 8, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('articulo_oc_taxes', function (Blueprint $table) {
            try {
                $table->decimal('cantidad', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('invent_historials', function (Blueprint $table) {
            try {
                $table->decimal('cantidad_anterior', 10, 3)->default(0)->change();
                $table->decimal('cantidad', 10, 3)->default(0)->change();
                $table->decimal('cantidad_despues', 10, 3)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('costo_historials', function (Blueprint $table) {
            try {
                $table->integer('costo_anterior')->default(0)->change();
                $table->integer('costo_despues')->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('precio_historials', function (Blueprint $table) {
            try {
                $table->decimal('precio_anterior', 10, 2)->default(0)->change();
                $table->decimal('precio_despues', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('plan_prices', function (Blueprint $table) {
            try {
                $table->decimal('precio', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('pre_facturas', function (Blueprint $table) {
            try {
                $table->decimal('subtotal', 12, 2)->default(0)->change();
                $table->decimal('descuento', 12, 2)->default(0)->change();
                $table->decimal('impuesto', 12, 2)->default(0)->change();
                $table->decimal('total', 12, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('pre_factura_articulos', function (Blueprint $table) {
            try {
                $table->decimal('cantidad', 10, 3)->default(0)->change();
                $table->decimal('precio', 10, 2)->default(0)->change();
                $table->decimal('descuento', 10, 2)->default(0)->change();
                $table->decimal('impuesto', 10, 2)->default(0)->change();
                $table->decimal('importe', 10, 2)->default(0)->change();
            } catch (\Throwable $th) {
                logger($th);
            }
        });
        Schema::table('pre_factura_articulo_taxes', function (Blueprint $table) {
            try {
                $table->decimal('base', 10, 2)->default(0)->change();
                $table->decimal('importe', 10, 2)->default(0)->change();
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
