<?php

namespace App\Http\Controllers;

use App\Models\ClaveProdServicio;
use App\Models\ClaveUnidad;
use App\Models\Product;
use App\Models\ProductTax;
use App\Models\Tax;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Tax::where('organization_id', $user->active_organization_id);

        if ($request->has('type')) {
            $query->where('tipo', $request->type);
        }

        return $query->get();
    }
    public function retained(Request $request)
    {
        $user = $request->user();
        return Tax::where('organization_id', $user->active_organization_id)->where('tipo', 'retenido')->get();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function claves(Request $request)
    {
        $user = $request->user();
        // clave_prod_servicios
        return ClaveProdServicio::where('descripcion', 'like', '%' . $request->keyword . '%')
            ->orWhere('c_claveProdServ', 'like', '%' . $request->keyword . '%')->take(50)->get();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function unidades(Request $request)
    {
        $user = $request->user();
        return ClaveUnidad::where('descripcion', 'like', '%' . $request->keyword . '%')
            ->orWhere('c_ClaveUnidad', 'like', '%' . $request->keyword . '%')->take(50)->get();
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user()->load('organization');

        $request->validate([
            'c_impuesto' => "required|string|max:70",
            'tipo' => "required|string|max:70",
            'activo' => 'required|boolean',
            'tasa_cuota' => 'required|numeric',
        ]);
        $tipo = $request->tipo;
        $c_impuesto = $request->c_impuesto;
        $activo = $request->activo;
        $tasaCuota = $request->tasa_cuota;

        /** @var Organization $organization */
        $organization = $user->organization;
        return $organization->createTax($c_impuesto, $activo, $tasaCuota, $tipo);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tax  $departamento
     * @return \Illuminate\Http\Response
     */
    public function show(Tax $impuesto)
    {
        return $impuesto;
    }
    public function showpd($productActualId)
    {
        $product = Product::find($productActualId);
        return $product;
    }
    public function agregard($impuestoActualId, $productActualId)
    {
        $product = Product::find($productActualId);
        $product->taxes()->attach([$impuestoActualId]);
        return 'exitoso';
    }
    public function quitarD($impuestoActualId, $productActualId)
    {
        $product = Product::find($productActualId);
        $product->taxes()->detach([$impuestoActualId]);
        return;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tax  $departamento
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tax $impuesto)
    {
        $request->validate([
            'c_impuesto' => "required|string|max:70",
            'tipo' => "required|string|max:70",
            'activo' => 'required|boolean',
            'tasa_cuota' => 'required|numeric',
        ]);
        $c_impuesto = $request->c_impuesto;
        $tipo = $request->tipo;
        $activo = $request->activo;
        $tasaCuota = $request->tasa_cuota;

        $user = auth()->user();
        $organization = $user->organization;

        return $organization->updateTax($c_impuesto, $activo, $tasaCuota, $tipo, $impuesto);
    }
    public function updateUnidad(Request $request, $productId)
    {
        $request->validate([
            'unidad' => "required|string|max:70",
        ]);
        $user = auth()->user();
        $unidad = $request->unidad;
        $product = Product::findOrFail($productId);
        $unidad = ClaveUnidad::where("c_ClaveUnidad", $unidad)->firstOrFail();
        $product->c_ClaveUnidad = $unidad->c_ClaveUnidad;
        $product->c_ClaveUnidad_descripcion = $unidad->descripcion;
        $product->save();
    }
    public function updateClave(Request $request, $productId)
    {
        $request->validate([
            'clave' => "required|string|max:70",
        ]);
        $user = auth()->user();
        $clave = $request->clave;
        $product = Product::findOrFail($productId);
        $clave = ClaveProdServicio::where("c_claveProdServ", $clave)->firstOrFail();
        $product->c_claveProdServ = $clave->c_claveProdServ;
        $product->c_claveProdServ_descripcion = $clave->descripcion;
        $product->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tax  $departamento
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tax $impuesto)
    {
        Tax::destroy($impuesto->id);
    }
    public function updateTypeOfTax(Request $request, $taxId)
    {
        $request->validate([
            'type' => "required|string|max:70",
            'value' => "required|boolean",
        ]);
        $productTax = ProductTax::find($taxId);
        $user = auth()->user();
        $type = $request->type;
        if ($type == 'compra') {
            $productTax->compra = $request->value;
        } else {
            $productTax->venta = $request->value;
        }
        $productTax->save();
    }
}
