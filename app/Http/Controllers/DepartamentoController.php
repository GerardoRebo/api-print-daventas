<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        return Departamento::where('organization_id', $user->organization_id)->get();
    }
    public function search(Request $request)
    {
        $user = $request->user();
        return Cliente::where('name', 'like', '%' . $request->keyword . '%')

            ->where('organization_id', $user->organization_id)
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
        $newDepartamento = new Departamento;
        $newDepartamento->organization_id = $user->organization_id;
        $newDepartamento->name = $request->name;
        $newDepartamento->porcentaje = $request->porcentaje;
        $newDepartamento->activo = $request->activo;
        $newDepartamento->save();
        return $newDepartamento;
    }

    function validateAfter($request, $value = null)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => "required|string|max:70",
                'porcentaje' => 'between:0,100',
                'activo' => 'required|boolean',
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
            $departamento = Departamento::where('name', $name)->where('organization_id', $orgId)->select('id')->first()?->id;
            if ($value) {
                if ($departamento && $departamento != $value) {
                    $validator->errors()->add(
                        'name',
                        'El nombre proporcionado ya existe, elige otro'
                    );
                }
            } else {
                if ($departamento) {
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
     * @param  \App\Models\Departamento  $departamento
     * @return \Illuminate\Http\Response
     */
    public function show(Departamento $departamento)
    {
        return $departamento;
    }
    public function showpd($productActualId)
    {
        $product = Product::find($productActualId);
        return $product->departamentos;
    }
    public function agregard($departamentoActualId, $productActualId)
    {
        $product = Product::find($productActualId);
        $product->departamentos()->attach([$departamentoActualId]);
        return 'exitoso';
    }
    public function quitarD($departamentoActualId, $productActualId)
    {
        $product = Product::find($productActualId);
        $product->departamentos()->detach([$departamentoActualId]);
        return;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Departamento  $departamento
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Departamento $departamento)
    {
        $this->validateAfter($request, $departamento->id);
        $departamento->update($request->all());
        $departamento = $request->activo;
        return $departamento;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Departamento  $departamento
     * @return \Illuminate\Http\Response
     */
    public function destroy(Departamento $departamento)
    {
        Departamento::destroy($departamento->id);
    }
}
