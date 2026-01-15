<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationalException;
use App\Models\Cliente;
use App\Models\Ventaticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    public function getallclients(Request $request)
    {
        $user = $request->user();
        $keycliente = request()->input('keycliente');
        $clientes = Cliente::where('name', 'like', '%' . $keycliente . '%')
            ->where('organization_id', $user->active_organization_id)->get();
        return $clientes;
    }
    public function allclients(Request $request)
    {
        $user = $request->user();
        $clientes = Cliente::where('organization_id', $user->active_organization_id)->get();
        return $clientes;
    }
    public function setcliente()
    {
        $user = request()->user();
        $ventaticket = Ventaticket::find(request()->input('params.ventaticket'));
        if ($ventaticket->retention) {
            throw new OperationalException("La venta tiene cliente con  retención asignado, no es posible cambiar al cliente.", 1);
        }
        $clienteId = request()->input('params.cliente');
        $cliente = Cliente::findOrFail($clienteId);
        $ventaticket->update([
            'cliente_id' => $clienteId
        ]);
        if (!$cliente->regimen_fiscal) return;
        // if ($ventaticket->ventaticket_articulos->count()) return;

        $organization = $user->getActiveOrganization();
        return response()->json([
            'success' => true,
            'retentionRules' => $organization->getClientRetentionRulesString($cliente->regimen_fiscal)
        ]);
    }
    public function search(Request $request)
    {
        $user = $request->user();
        return Cliente::where('name', 'like', '%' . $request->keyword . '%')
            ->where('organization_id', $user->active_organization_id)
            ->orderBy('name', 'asc')->get();
    }

    public function store(Request $request)
    {
        $this->validateAfter($request);
        $user = $request->user();
        $cliente = Cliente::create($request->all());
        $cliente->organization_id = $user->active_organization_id;
        $cliente->save();
    }
    public function update(Request $request, Cliente $cliente)
    {
        $this->validateAfter($request, $cliente->id);
        $cliente->update($request->all());
        return $cliente;
    }

    function validateAfter($request, $value = null)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => "required|string|max:70",
                'telefono' => 'nullable|string|max:15',
                'limite_credito' => 'nullable|numeric|max:200',
                'domicilio' => 'nullable|string|max:200',
                'email' => 'nullable|string|max:100',
                'rfc' => 'nullable|string|max:50',
                'regimen_fiscal' => 'nullable|string|max:50',
                'razon_social' => 'nullable|string|max:100',
                'codigo_postal' => 'nullable|string|max:50',
            ],
            [],
            [
                'name' => 'Nombre',
            ]
        );
        $validator->validate();
        $name = $validator->validated()['name'];
        $validator->after(function ($validator) use ($name, $value) {
            $orgId = auth()->user()->active_organization_id;
            $cliente = Cliente::where('name', $name)->where('organization_id', $orgId)->select('id')->first()?->id;
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

    public function show()
    {
        $cliente = Cliente::find(request()->input('clienteId'));
        return $cliente;
    }


    public function delete()
    {
        $cliente = request()->input('clienteId');
        Cliente::destroy($cliente);
    }
}
