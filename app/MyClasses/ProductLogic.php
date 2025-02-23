<?php

namespace App\MyClasses;

use App\Models\Product;

class ProductLogic
{
    public function agregaPrecios($lista_de_productos, $almacenActualId)
    {
        return $lista_de_productos->map(function (Product $item, $key) use ($almacenActualId) {
            if ($item->es_kit) {
                $item->cantidad_actual = $item->getCantidadActual($almacenActualId);
            }
            $item->precio_sugerido = $item->getPrecioSugerido($almacenActualId);
            return $item;
        });
    }
    public function agregaPreciosConsolidado($lista_de_productos, $organization)
    {
        return $lista_de_productos->map(function (Product $item, $key) use ($organization) {
            $item->cantidad_actual = $item->getCantidadActualConsolidada($organization);
            return $item;
        });
    }
    public function basicQuery($almacenActualId)
    {
        return Product::with('product_components.product_hijo')->leftJoin('precios', function ($join) use ($almacenActualId) {
            $join->on('products.id', '=', 'precios.product_id')
                ->where('precios.almacen_id', '=', $almacenActualId);
        })
            ->leftJoin('inventario_balances', function ($join) use ($almacenActualId) {
                $join->on('products.id', '=', 'inventario_balances.product_id')
                    ->where('inventario_balances.almacen_id', '=', $almacenActualId);
            })->select(
                'products.*',
                'precios.precio',
                'precios.precio_mayoreo',
                'inventario_balances.cantidad_actual',
                'inventario_balances.invmin',
                'inventario_balances.invmax'
            );
    }
    public function basicQueryConsolidado()
    {
        return Product::with('product_components.product_hijo');
    }
    public function exists(string $codigo)
    {
        $orgId = auth()->user()->organization_id;
        return Product::where('codigo', $codigo)->where('organization_id', $orgId)->exists();
    }
}
