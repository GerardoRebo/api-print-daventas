<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return Proveedor::where('organization_id', $user->active_organization_id)->orderBy('name', 'asc')->get();;
    }
    public function search(Request $request)
    {
        $user = $request->user();
        return Proveedor::where('name', 'like', '%' . $request->keyword . '%')
            ->where('organization_id', $user->active_organization_id)
            ->orderBy('name', 'asc')->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validateAfter($request);
        $user = $request->user();
        $newProveedor = new Proveedor;

        $newProveedor->name = $request->name;

        $newProveedor->organization_id = $user->active_organization_id;
        $newProveedor->telefono = $request->telefono;
        $newProveedor->notas = $request->notas;
        $newProveedor->direccion = $request->direccion;
        $newProveedor->email = $request->email;
        $newProveedor->save();
        return $newProveedor;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        $this->validateAfter($request, $proveedor->id);
        $proveedor->update($request->all());
        return $proveedor;
    }

    private function validateAfter($request, $value = null)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => "required|string|max:70",
                'telefono' => 'nullable|string|max:15',
                'notas' => 'nullable|string|max:200',
                'direccion' => 'nullable|string|max:100',
                'email' => 'nullable|string|max:50',
            ],
            [],
            [
                'name' => 'Nombre',
            ]
        );
        $validator->validate();
        $name = $validator->validated()['name'];
        $validator->after(function ($validator) use ($name, $value) {
            $orgId = auth()->user()->organization_id;
            $proveedor = Proveedor::where('name', $name)->where('organization_id', $orgId)->select('id')->first()?->id;
            if ($value) {
                if ($proveedor && $proveedor != $value) {
                    $validator->errors()->add(
                        'name',
                        'El nombre proporcionado ya existe, elige otro'
                    );
                }
            } else {
                if ($proveedor) {
                    $validator->errors()->add(
                        'name',
                        'El nombre proporcionado ya existe, elige otro'
                    );
                }
            }
        });
        $validator->validate();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Proveedor $proveedor)
    {

        return $proveedor;
    }
    public function showpp($productActualId)
    {
        $product = Product::find($productActualId);
        return $product->proveedors;
    }
    public function agregarp($proveedorActualId, $productActualId)
    {

        $product = Product::find($productActualId);
        $product->proveedors()->attach([$proveedorActualId]);
        return 'exitoso';
    }
    public function quitarP($proveedorActualId, $productActualId)
    {

        $product = Product::find($productActualId);
        $product->proveedors()->detach([$proveedorActualId]);
        return;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Proveedor $proveedor)
    {
        Proveedor::destroy($proveedor->id);
    }
}
