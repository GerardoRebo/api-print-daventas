<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\InventarioBalance;
use App\Models\Organization;
use App\Models\Precio;
use App\Models\Product;
use App\Models\User;
use App\MyClasses\Services\AlmacenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class AlmacenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        return Almacen::where('organization_id', $user->active_organization_id)->get();
    }
    public function myalmacens(Request $request)
    {
        $user = $request->user();
        $almacenService = new AlmacenService;
        return $almacenService->getMyAlmacens($user);
    }
    public function useralmacens(Request $request)
    {
        //$user = $request->user();
        $user = $request->userId;
        $user = User::find($user);
        $orgId = $request->input('organization_id') ?? auth()->user()->active_organization_id;
        return $user->getAlmacenesByOrganization($orgId);
    }
    public function attachalmacen(Request $request, AlmacenService $almacenService)
    {
        $attacher = $request->user();
        $almacenId = request()->input('params.almacenId');
        $userEnviadoId = request()->input('params.userId');

        if (!$almacenService->attachAlmacen($attacher, $userEnviadoId, $almacenId)) abort(500, 'No fue posible');
    }
    public function detachalmacen(Request $request)
    {
        $user = $request->user();
        $userEnviado = $request->input('params.userId');
        $usuarios = User::whereHas('organizations', function ($query) use ($user) {
            $query->where('organization_id', $user->active_organization_id);
        })->get();
        $almacenId = request()->input('params.almacenId');

        if (!$usuarios->contains('id', $userEnviado)) return;
        $user = User::find($userEnviado);

        $user->almacens()->detach([$almacenId]);
        $user->refresh();
        Cache::tags(['orgAlmacens:' . $user->active_organization_id])->put('userAlmacens:' . $user->id, $user->getAlmacenesByOrganization());
    }
    public function search(Request $request)
    {
        $user = $request->user();
        return Almacen::where('name', 'like', '%' . $request->keyword . '%')
            ->where('organization_id', $user->active_organization_id)
            ->orderBy('name', 'asc')->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, AlmacenService $almacenService)
    {
        /** @var User $user */
        $user = auth()->user();
        $orgId = $user->active_organization_id;
        $organization = Organization::with('almacens')->find($orgId);
        $almacens = $organization->almacens->pluck('id')->toArray();
        $validator = Validator::make($request->all(), [
            'name' => "required|string|max:70",
            'direccion' => "nullable|string|max:70",
            'rfc' => "nullable|string|max:70",
            'telefono' => "nullable|string|max:70",
            'almacen_copia' => "nullable",
        ]);


        $validator->validate();
        $almacenCopia = $request->almacen_copia;
        $almacenCopiaExistencia = $request->almacen_copia_existencia;
        $name = $request->name;
        $validator->after(function (ValidationValidator $validator) use ($name, $orgId) {
            $exist = Almacen::where('name', $name)->where('organization_id', $orgId)->exists();
            if ($exist) {
                $validator->errors()->add(
                    'name',
                    'El nombre del almacén ya existe, elige otro'
                );
            }
        });
        $validator->validate();

        $newAlmacen = Almacen::withTrashed()->where('name', $request->name)->where('organization_id', $orgId)->first();
        if ($newAlmacen) {
            $newAlmacen->restore();
        }
        if (!$newAlmacen) {
            $newAlmacen = new Almacen;
            $newAlmacen->name = $request->name;
            $newAlmacen->save();
        }
        Precio::where('almacen_id', $newAlmacen->id)->delete();
        InventarioBalance::where('almacen_id', $newAlmacen->id)->delete();
        if ($almacenCopia && in_array($almacenCopia, $almacens)) {
            Precio::where('almacen_id', $almacenCopia)->chunk(1000, function ($prices) use ($newAlmacen) {
                $pricesData = [];
                foreach ($prices as $price) {
                    $pricesData[] = [
                        'product_id' => $price->product_id,
                        'almacen_id' => $newAlmacen->id,
                        'precio' => $price->precio,
                        'precio_mayoreo' => $price->precio_mayoreo,
                    ];
                }
                DB::table('precios')->insert($pricesData);
            });
        }
        if ($almacenCopiaExistencia && in_array($almacenCopiaExistencia, $almacens)) {
            InventarioBalance::where('almacen_id', $almacenCopiaExistencia)->chunk(1000, function ($balances) use ($newAlmacen) {
                $balancesData = [];
                foreach ($balances as $balance) {
                    $balancesData[] = [
                        'product_id' => $balance->product_id,
                        'almacen_id' => $newAlmacen->id,
                        'cantidad_actual' => $balance->cantidad_actual,
                        'invmin' => $balance->invmin,
                        'invmax' => $balance->invmax,
                    ];
                }
                DB::table('inventario_balances')->insert($balancesData);
            });
        } else {
            Product::where('organization_id', $user->active_organization_id)->chunk(1000, function ($products) use ($newAlmacen) {
                $balancesData = [];
                foreach ($products as $product) {
                    $balancesData[] = [
                        'product_id' => $product->id,
                        'almacen_id' => $newAlmacen->id,
                    ];
                }
                DB::table('inventario_balances')->insert($balancesData);
            });
        }
        $newAlmacen->telefono = $request->telefono;
        $newAlmacen->direccion = $request->direccion;
        $newAlmacen->rfc = $request->rfc;
        $newAlmacen->organization_id = $orgId;
        $newAlmacen->save();

        if (!$almacenService->attachAlmacenToTeamMembers($user, $newAlmacen->id)) abort(500, 'No fue posible');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Almacen $almacen)
    {

        return $almacen;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Almacen $almacen)
    {
        $orgId = auth()->user()->active_organization_id;
        $validator = Validator::make($request->all(), [
            'name' => "required|string|max:70",
        ]);
        $validator->validate();
        $name = $request->name;
        $validator->after(function (ValidationValidator $validator) use ($name, $orgId) {
            $exist = Almacen::where('name', $name)->where('organization_id', $orgId)->exists();
            if ($exist) {
                $validator->errors()->add(
                    'name',
                    'El nombre del almacén ya existe, elige otro'
                );
            }
        });
        $validator->validate();
        Cache::tags('orgAlmacens:' . $orgId)->flush();
        $almacen->update($request->except(['almacen_copia', 'almacen_copia_existencia']));
        return $almacen;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Almacen $almacen)
    {
        $user = auth()->user();
        Cache::tags('orgAlmacens:' . $user->active_organization_id)->flush();
        Almacen::destroy($almacen->id);
        DB::table('almacen_user')->where('almacen_id', $almacen->id)->delete();
    }
}
