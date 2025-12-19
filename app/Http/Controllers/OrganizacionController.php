<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationalException;
use App\Models\Almacen;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\PaqueteTimbre;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PreFactura;
use App\Models\PreFacturaGlobal;
use App\Models\Product;
use App\Models\User;
use App\Models\Ventaticket;
use App\Notifications\SendOrganziationRequest;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class OrganizacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Organization::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        return Organization::with(['almacens', 'plan'])
            ->with('users')->where('name', 'like', '%' . $request->keyword . '%')
            ->orderBy('id', 'desc')->paginate(10);
    }
    public function searchAlmacen(Request $request)
    {
        return Almacen::where('name', 'like', '%' . $request->keywordAlmacen . '%')
            ->orderBy('name', 'asc')->get();
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => "required|string|max:70",
            'pais' => "nullable|string|max:70",
            'estado' => "nullable|string|max:70",
            'ciudad' => "nullable|string|max:70",
            'telefono' => "nullable|string|max:70",
        ]);
        $newOrganization = new Organization;
        $newOrganization->name = $request->name;
        $newOrganization->activa = true;
        $newOrganization->pais = $request->pais;
        $newOrganization->estado = $request->estado;
        $newOrganization->ciudad = $request->ciudad;
        $newOrganization->telefono = $request->telefono;
        $newOrganization->save();
        return $newOrganization;;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($organization)
    {
        $organization = Organization::find($organization);
        return $organization;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $organization = Organization::with('facturacion_info')->find($id);
        $request->validate([
            'name' => "nullable|string",
            'email' => "nullable|email",
            'razon_social' => "nullable|string|max:70",
            'regimen_fiscal' => "nullable|string|max:70",
            'codigo_postal' => "nullable|string|max:70",
            'rfc' => "nullable|string|max:70",
            'c_periodicidad' => "nullable|string|max:70",
            'show_fiscal_info' => "nullable|boolean",
        ]);
        $organization->update([
            "name" => $request->name,
            "email" => $request->email,
            "show_fiscal_info" => $request->show_fiscal_info !== null ? $request->show_fiscal_info : $organization->show_fiscal_info,
        ]);
        $razon_social = $request->razon_social;
        $regimen_fiscal = $request->regimen_fiscal;
        $rfc = $request->rfc;
        $codigoPostal = $request->codigo_postal;
        $c_periodicidad = $request->c_periodicidad;
        $organization->updateFacturacionInfo($razon_social, $regimen_fiscal, $rfc, $codigoPostal, $c_periodicidad);
        return $organization;
    }
    public function updateClavePrivadaSat(Request $request, $id)
    {
        $request->validate([
            'value' => 'required|string',
        ]);
        $organization = Organization::findOrFail($id);
        $value = $request->value;
        $organization->updateClavePrivadaSat($value);
        return $organization;
    }
    public function updateClavePrivadaLocal(Request $request, $id)
    {
        $request->validate([
            'value' => 'required|string',
        ]);
        $organization = Organization::findOrFail($id);
        $value = $request->value;
        $organization->updateClavePrivadaLocal($value);
        return $organization;
    }
    public function uploadCer(Request $request)
    {
        $request->validate([
            'file' => 'required|max:1000',
        ]);
        $user = auth()->user();
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $path = $file->store('certificados');

        $organization = Organization::findOrFail($user->organization_id);
        if ($organization?->facturacion_info?->cer_path) {
            Storage::delete($organization->facturacion_info->cer_path);
        }
        $organization->updateCer($path, $fileName);
        return $organization;
    }
    public function uploadKey(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'file' => 'required|max:1000',
        ]);
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $path = $file->store('certificados');

        $organization = Organization::findOrFail($user->organization_id);

        if ($organization?->facturacion_info?->key_path) {
            Storage::delete($organization->facturacion_info->key_path);
        }
        $organization->updateKey($path, $fileName);
        return $organization;
    }
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        $user = auth()->user();
        $organization = $user->organization;


        if ($organization?->image?->path) {
            Storage::delete($organization->image->path);
        }

        $logoPath = $request->file('logo')->store('public/logos');

        if ($organization->image) {
            $organization->image()->update(['path' => $logoPath]);
        } else {
            $organization->image()->create(['path' => $logoPath]);
        }

        return response()->json(['success' => true, 'message' => 'Logo uploaded successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Organization::destroy($id);
    }
    public function getInfo(Request $request)
    {
        $user = $request->user();
        $orgId = $request->input('orgId');
        $date = Carbon::now()->subDays(15);
        $organization = Organization::find($orgId);
        $tickets = Ventaticket::where('organization_id', $orgId)->where('created_at', '>=', $date)->count();
        $uTicket = Ventaticket::where('organization_id', $orgId)->latest()->first();
        $productos = Product::where('organization_id', $orgId)->count();

        $info = [
            'Nombre' => $organization->name,
            'Numero Tickets Ultimos 15 dias' => $tickets,
            'Fecha Ultimo Ticket' => $uTicket?->created_at ?? '',
            'Productos Total' => $productos,
            'Fecha de Creación' => $organization->created_at,
            'Telefono' => $organization->telefono,
            'Ciudad' => $organization->ciudad,
            'Estado' => $organization->estado,
            'Pais' => $organization->pais,
        ];
        return $info;
    }

    public function organizationUsers(Request $request)
    {
        $user = $request->user();
        $org = request()->input('organizacionActualId');
        $users = User::where('organization_id', $org)->get();
        return $users;
    }
    public function misusers(Request $request)
    {
        $user = $request->user();
        $users = User::where('organization_id', $user->organization_id)->get();
        return $users;
    }
    public function misalmacens()
    {
        $organization = request()->input('organizacionActualId');
        $organization = Organization::find($organization);
        return $organization->almacens;
    }
    public function getPlans()
    {
        return Plan::with('plan_prices')->get();
    }
    public function showPlan()
    {
        $planId = request()->input('planId');
        return Plan::with('plan_prices')->find($planId);
    }
    public function showPrecio()
    {
        $planId = request()->input('planId');
        return PlanPrice::find($planId);
    }
    public function updatePrecio(Request $request)
    {
        $validated = $request->validate([
            'meses' => 'required|integer',
            'precio' => 'required|numeric',
        ]);
        $pp = PlanPrice::find($request->id);
        $pp->update($validated);
    }
    public function deletePrecio(Request $request)
    {
        $pp = PlanPrice::destroy($request->id);
    }
    public function registerPlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:plans',
            'max_almacens' => 'required|integer|max:100',
            'max_users' => 'required|integer|max:100',
        ]);
        Plan::create($validated);
    }
    public function deletePlan(Request $request)
    {
        Plan::find($request->id)->delete();
    }
    public function onOff(Request $request)
    {
        $org = Organization::find($request->id);
        $org->activa = !$org->activa;
        $org->save();
    }
    public function asignarPlan(Request $request)
    {
        // Plan::find($request->id)->delete();
        $validated = $request->validate([
            'comments' => 'nullable|string',
            'orgId' => 'required|integer',
            'planId' => 'required|integer',
        ]);
        $org = Organization::find($validated['orgId']);
        $org->plan_id = $validated['planId'];
        $org->save();
    }
    public function updatePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:plans,name,' . $request->id,
            'max_almacens' => 'required|integer|max:100',
            'max_users' => 'required|integer|max:100',
        ]);

        Plan::find($request->id)->update($validated);
    }
    public function getPrecios(Request $request)
    {
        $validated = $request->validate([
            'planId' => 'required|integer',
        ]);

        return PlanPrice::where('plan_id', $request->planId)->get();
    }
    public function registerPrecio(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer',
            'meses' => 'required|integer',
            'precio' => 'required|numeric',
        ]);

        return PlanPrice::create($validated);
    }
    public function setuserorganizationnew(Request $request)
    {
        $user = $request->user()->id;
        $organization = $request->organization;
        $organization = Organization::find($organization);
        $user = User::find($user);
        DB::table('responsables')->insert([
            'user_id' => $user->id,
            'organization_id' => $organization->id
        ]);
        $user->organization_id = $organization->id;
        $user->assignRole('Owner');
        $user->save();
        return $user;
    }
    public function setuserorganization($user, $organization)
    {
        $user = User::find($user);
        $user->organization_id = $organization;
        $user->syncRoles(['Cajero']);
        $user->save();
        return $user;
    }
    public function desvincularUser()
    {
        $user = request()->input('userId');

        $authUser = auth()->user();
        if (!$authUser->hasAnyRole('Owner', 'Admin')) return;
        $orgId = $authUser->organization_id;
        $user = User::find($user);
        if ($user->organization_id != $orgId) return;
        $user->organization_id = null;
        $user->syncRoles([]);
        $user->save();
        return;
    }
    public function toggleUser()
    {
        $user = request()->input('userId');
        $authUser = auth()->user();
        if (!$authUser->hasAnyRole('Owner', 'Admin')) return;
        $orgId = $authUser->organization_id;
        $user = User::find($user);
        if ($user->hasAnyRole('Owner')) {
            throw new OperationalException("No es posible desactivar al dueño de la organizacion", 1);
        }
        if ($user->organization_id != $orgId) return;
        $organization = $authUser->organization;
        $organizationPlan = $organization->latestOrganizationPlan;
        if ($organizationPlan) {
            $activeUsersCount = $organization->get_active_users_count();
            if ($activeUsersCount >= $organizationPlan->plan->max_users && !$user->activo) {
                throw new OperationalException("Has alcanzado el limite de usuarios activos para tu plan actual", 1);
            }
        }
        $user->activo = !$user->activo;
        $user->save();
    }
    public function toggleAlmacen()
    {
        $almacen = Almacen::find(request()->input('almacenId'));
        $authUser = auth()->user();
        if (!$authUser->hasAnyRole('Owner', 'Admin')) throw new OperationalException("No cuentas con el permiso", 1);;
        $orgId = $authUser->organization_id;
        if ($almacen->organization_id != $orgId) throw new OperationalException("No es de tu organizacion", 1);
        $organization = $authUser->organization;
        $organizationPlan = $organization->latestOrganizationPlan;
        if ($organizationPlan) {
            $activeAlmacensCount = $organization->get_active_almacens_count();
            if ($activeAlmacensCount >= $organizationPlan->plan->max_almacens && !$almacen->is_active) {
                throw new OperationalException("Has alcanzado el limite de almacenes activos para tu plan actual", 1);
            }
        }
        $almacen->is_active = !$almacen->is_active;
        $almacen->save();
    }

    public function registrarPago()
    {
        $organization = request()->input('orgId');
        $monto = request()->input('monto');
        $fecha = request()->input('fecha');
        $comments = request()->input('comments');

        $organization = Organization::find($organization);
        $uVencimiento = $organization->vencimiento;
        $organization->update([
            'ultimo_pago_en' => now(),
            'u_vencimiento' => $uVencimiento,
            'vencimiento' => $fecha,
        ]);
    }
    public function registeralmacen()
    {
        $organization = request()->input('organizacionActualId');
        $almacen = request()->input('almacen');
        $organization = Organization::find($organization);
        $organization->almacens()->attach($almacen);
    }
    public function detachuser()
    {
        $organization = request()->input('organizacionActualId');
        $user = request()->input('user');
        $organization = Organization::find($organization);
        $organization->users()->detach($user);
    }
    public function detachalmacen()
    {
        $organization = request()->input('organizacionActualId');
        $almacen = request()->input('almacen');
        $organization = Organization::find($organization);
        $organization->almacens()->detach($almacen);
    }
    public function getorganizations(Request $request)
    {
        $user = $request->user()->id;
        return Invitation::with('organization')->where('user_id', $user)->get();
    }
    public function getmyorganization(Request $request)
    {
        $user = $request->user();
        return Organization::with('facturacion_info', 'image')->find($user->organization_id);
    }
    public function getsolicitudes(Request $request)
    {
        $user = $request->user();
        return Invitation::with('user')
            ->where('organization_id', $user->organization_id)
            ->where('respondida', 0)
            ->get();
    }
    public function gettabulares(Request $request)
    {
        $user = $request->user();
        try {
            $tabulares =  Redis::hgetall($user->organization_id . "tabular");
        } catch (Exception $e) {
            return  $tabulares = null;
        }
        $unsortedData = collect(
            $tabulares
        );

        $sortedData = $unsortedData->sortByDesc("");
        return $sortedData;
    }
    public function enviartabular(Request $request)
    {
        $user = $request->user();
        $index = request()->input('index');
        $value = request()->input('value');
        try {
            Redis::hset($user->organization_id . "tabular", $index, $value);
        } catch (Exception $e) {
            return  "No se pudo conectar a la BD";
        }
    }

    public function eliminartabular(Request $request)
    {
        $user = $request->user();

        $index = request()->input('index');
        try {
            Redis::hdel($user->organization_id . "tabular", $index);
        } catch (Exception $e) {
            return  "No se pudo conectar a la BD";
        }
    }
    public function destroyInvitation(Request $request)
    {
        $ownerUser = $request->user();

        $id = request()->input('id');
        $invitation = Invitation::findOrFail($id);
        if ($invitation->organization_id != $ownerUser->organization_id) return "No tienes permiso";
        User::destroy($invitation->user_id);
        $invitation->delete();
        return;
    }
    public function enviarsolicitud(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8']
        ]);
        $ownerUser = $request->user();
        $organization = $ownerUser->organization_id;
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if ($user->organization_id) {
                return "OtraOrg";
            }
            $invitation = Invitation::where('user_id', $user->id)
                ->where('organization_id', $organization)->first();
            if ($invitation) {
                return 'El Usuario Ya Tiene Invitacion';
            }
            $user->notify(new SendOrganziationRequest($organization));
            return Invitation::create([
                'organization_id' => $organization,
                'user_id' => $user->id,
                'respondida' => 0
            ]);
        } else {
            $user = new User;
            $user->name = $request->email;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->organization_id = $organization;
            $user->assignRole('Cajero');
            $user->save();

            event(new Registered($user));

            $invitation = Invitation::where('user_id', $user->id)
                ->where('organization_id', $organization)->first();
            if ($invitation) {
                return 'El Usuario Ya Tiene Invitacion';
            }
            return Invitation::create([
                'organization_id' => $organization,
                'user_id' => $user->id,
                'respondida' => 0
            ]);
        }
    }
    public function eliminaPrueba()
    {
        $user = User::where('email', 'justsadness@live.com.mx')->first();
        $org = Organization::find($user->organization_id);
        if ($user) {
            $user->delete();
        }
        if ($org) {
            $org->delete();
        }
    }
    function configurations()
    {
        $user = auth()->user();
        return $user->organization->facturacion_info;
    }
    function foliosSaldo()
    {
        $user = auth()->user();
        return response()->json([
            'success' => true,
            'saldo' => $user->organization->getOverallTimbresCount(),
        ]);
    }
    function facturacionData()
    {
        $paquetes = PaqueteTimbre::where('active', true)
            ->orderBy('created_at', 'desc')
            ->get();
        $user = auth()->user();
        return response()->json([
            'success' => true,
            'saldo' => $user->organization->getOverallTimbresCount(),
            'paquetes' => $paquetes,
        ]);
    }
    function global(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date',
        ]);
        $user = auth()->user();
        $organization = $user->organization;
        $desde = $request->desde;
        $hasta = $request->hasta;

        // Convert to Carbon instances
        $desde = Carbon::parse($desde);
        $hasta = Carbon::parse($hasta);

        // Calculate the difference in months
        $differenceInMonths = $desde->diffInMonths($hasta);

        // Check if the difference is less than or equal to 6 months
        if ($differenceInMonths >= 2) {
            throw new OperationalException("El rango de fechas es excesivo", 1);
        }
        return $organization->getVentatickets($desde, $hasta);
    }
    function timbrarFacturaGlobal(Request $request, $facturaId)
    {
        $validated = $request->validate([
            'year' => 'required',
            'mes' => 'required',
            'serie' => 'nullable',
            'c_periodicidad' => 'required',
            'forma_pago' => 'required',
            'clave_privada_local' => 'required',
        ]);
        $year = $request->year;
        $mes = $request->mes;
        $serie = $request->serie;
        // $c_periodicidad = $request->c_periodicidad;
        $clavePrivadaLocal = $request->clave_privada_local;
        $usoCfdi = "S01";
        $formaPago =  $request->forma_pago;

        $user = auth()->user();
        $organization = $user->organization;

        $saldo = $organization->getOverallTimbresCount();
        $saldoScalar = $saldo;
        if (!$saldoScalar) {
            throw new OperationalException("No cuentas con suficientes timbres fiscales, , contacta con la administración para solicitar timbres fiscales", 1);
        }

        $organization->emitirFacturaGlobal($facturaId, $formaPago, $usoCfdi, $serie, $clavePrivadaLocal, $year, $mes);
        return response()->json([
            'success' => true,
        ]);
    }
    function preProcesar(Request $request)
    {
        $validated = $request->validate([
            'ticketIds' => 'required|array',
            'c_periodicidad' => 'required|string',
            'desde' => 'required|date',
            'hasta' => 'required|date',
        ]);
        $user = auth()->user();
        $organization = $user->organization;
        $organization->validatePreFacturaGlobal($validated['ticketIds']);
        $pre_factura_global_id = $organization->createPreFacturaGlobal(
            $user->id,
            $validated['ticketIds'],
            date('Y-m-d H:i:s', strtotime($request->desde)),
            date('Y-m-d H:i:s', strtotime($request->hasta)),
            $request->c_periodicidad
        );
        return response()->json([
            'success' => true,
            'pre_factura_global_id' => $pre_factura_global_id,
        ]);
    }
    function getFacturasGlobales()
    {
        $validated = request()->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date',
        ]);
        $user = auth()->user();
        $organization = $user->organization;
        $desde = $validated['desde'];
        $hasta = $validated['hasta'];
        $desde = Carbon::parse($desde)->utc();
        $hasta = Carbon::parse($hasta)->utc();
        return response()->json([
            'success' => true,
            'facturas' => $organization->getFacturasGlobales($desde, $hasta)
        ]);
    }
    function getFacturas()
    {
        $validated = request()->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date',
        ]);
        $user = auth()->user();
        $organization = $user->organization;
        $desde = $validated['desde'];
        $hasta = $validated['hasta'];
        return response()->json([
            'success' => true,
            'facturas' => $organization->getFacturas($desde, $hasta)
        ]);
    }
    function deleteFacturasGlobales(PreFacturaGlobal $factura)
    {
        $user = auth()->user();
        $organization = $user->organization;

        $factura->delete();
        return response()->json([
            'success' => true,
        ]);
    }
    function facturasGlobalesShow($facturaId)
    {
        $user = auth()->user();
        $organization = $user->organization;
        $factura = PreFacturaGlobal::with('articulos.ventaticket')->findOrFail($facturaId);
        $maxAmount = 0;
        $maxVentaticket = null;
        foreach ($factura->articulos as $preFactura) {
            if ($preFactura->ventaticket->fp_efectivo >= $maxAmount) {
                $maxAmount = $preFactura->fp_efectivo;
                $maxVentaticket = $preFactura->ventaticket_id;
            } elseif ($preFactura->ventaticket->fp_tarjeta_debito >= $maxAmount) {
                $maxAmount = $preFactura->fp_tarjeta_debito;
                $maxVentaticket = $preFactura->ventaticket_id;
            } elseif ($preFactura->ventaticket->fp_tarjeta_credito >= $maxAmount) {
                $maxAmount = $preFactura->fp_tarjeta_credito;
                $maxVentaticket = $preFactura->ventaticket_id;
            } elseif ($preFactura->ventaticket->fp_transferencia >= $maxAmount) {
                $maxAmount = $preFactura->fp_transferencia;
                $maxVentaticket = $preFactura->ventaticket_id;
            } elseif ($preFactura->ventaticket->fp_cheque >= $maxAmount) {
                $maxAmount = $preFactura->fp_cheque;
                $maxVentaticket = $preFactura->ventaticket_id;
            } elseif ($preFactura->ventaticket->fp_vales_de_despensa >= $maxAmount) {
                $maxAmount = $preFactura->fp_vales_de_despensa;
                $maxVentaticket = $preFactura->ventaticket_id;
            }
        }

        return response()->json([
            'success' => true,
            'factura' => $factura,
            'maxVentaticket' => $maxVentaticket,
        ]);
    }
    function facturasShow($facturaId)
    {
        $user = auth()->user();
        $organization = $user->organization;
        $factura = $organization->facturasShow($facturaId, $type);

        return response()->json([
            'success' => true,
            'factura' => $factura,
        ]);
    }
    function descargarXml(Request $request, PreFacturaGlobal $ticket)
    {
        $user = auth()->user();
        if (Storage::exists($ticket->xml_factura_path)) {
            $fileContent = Storage::get($ticket->xml_factura_path);
            return response($fileContent)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', 'attachment; filename="factura_xml_"');
        }
        throw new OperationalException("Archivo no encontrado", 1);
    }
    function descargarPdf(Request $request, PreFacturaGlobal $ticket)
    {
        $user = auth()->user();
        if (Storage::exists($ticket->pdf_factura_path)) {
            $fileContent = Storage::get($ticket->pdf_factura_path);
            return response($fileContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="factura_pdf_"');
        }
        throw new OperationalException("Archivo no encontrado", 1);
    }
}
