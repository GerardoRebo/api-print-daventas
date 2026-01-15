<?php

namespace App\Http\Controllers;

use App\Models\Concepto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConceptoController extends Controller
{
    public function index(Request $request)
    {

        $user = $request->user();
        return Concepto::where('organization_id', $user->active_organization_id)->get();
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $this->validateAfter($request);
        $newConcepto = new Concepto;
        $newConcepto->name = $request->name;
        $newConcepto->tipo = $request->tipo;
        $newConcepto->organization_id = $user->active_organization_id;
        $newConcepto->save();
        return $newConcepto;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Concepto $concepto)
    {

        return $concepto;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Concepto $concepto)
    {
        $this->validateAfter($request, $concepto->id);
        $concepto->update($request->all());
        return $concepto;
    }
    function validateAfter($request, $value = null)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => "required|string|max:70",
                'tipo' => "required|string|max:70",
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
            $cliente = Concepto::where('name', $name)->where('organization_id', $orgId)->select('id')->first()?->id;
            if ($value) {
                if ($cliente && $cliente != $value) {
                    $validator->errors()->add(
                        'name',
                        'El nombre proporcionado ya existe, elige otro'
                    );
                }
            } else {
                if ($cliente) {
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Concepto $concepto)
    {
        Concepto::destroy($concepto->id);
    }
}
