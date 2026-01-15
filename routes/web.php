<?php

use App\Http\Controllers\ExcelFileController;
use App\Http\Requests\EmailVerificationRequest;
use App\Models\Devolucione;
use App\Models\Invitation;
use App\Models\OrdenCompra;
use App\Models\User;
use App\Models\Ventaticket;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| web routes
|--------------------------------------------------------------------------
|
| here is where you can register web routes for your application. these
| routes are loaded by the routeserviceprovider within a group which
| contains the "web" middleware group. now create something great!
|
*/

Route::get('ventatickets/imprimir/{ventaticket}/{pagocon?}/{total?}', function (Ventaticket $ventaticket, $pagocon, $total) {
    $ventaticket->load('user.configuration');
    if ($pagocon == '~') {
        $pagocon = null;
    }
    if ($total == '~') {
        $total = null;
    }
    if ($pagocon) {
        $ventaticket->pago_con = $pagocon;
    }
    if ($total) {
        $ventaticket->total = $total;
    }
    return view('ventaimprimir', compact('ventaticket', 'pagocon', 'total'));
})->name('ventatickets.imprimir');

Route::get('devoluciones/imprimir/{ticket}', function (Devolucione $ticket) {
    return view('devolucionimprimir', compact('ticket'));
})->name('devolucion.imprimir');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    $invitation = Invitation::where('user_id', auth()->user()->id)->first();
    if ($invitation) {
        $invitation->respondida = 1;
        $invitation->save();
    }
    return redirect('email_verified');
})->middleware(['signed'])->name('verification.verify');
Route::get('/email/organization_request/{invitationId}', function () {
    // Find invitation by ID
    $invitation = Invitation::findOrFail(request('invitationId'));

    // Verify invitation hasn't been answered already
    if ($invitation->respondida) {
        return redirect('email_verified')->with('message', 'Esta invitación ya ha sido procesada');
    }

    $user = $invitation->user;
    $orgId = $invitation->organization_id;

    // Mark invitation as responded
    $invitation->update(['respondida' => 1]);

    // Attach user to organization via M:M relationship if not already attached
    if (!$user->belongsToOrganization($orgId)) {
        $user->organizations()->attach($orgId, [
            'shard_id' => 1,
            'shard_connection' => 'mysql',
            'assigned_by' => null,
            'assigned_at' => now(),
            'active' => true,
            'role_name' => 'Cajero' // Default role
        ]);
    }

    return redirect('email_verified');
})->middleware(['signed'])->name('organization.request');

Route::view('email_verified', 'email_verified');

Route::get('movimientos/imprimir/{movimiento}/{pagocon?}', function (OrdenCompra $movimiento, $pagocon) {
    $movimiento->load('user.configuration');
    if ($pagocon == 'nop') {
        $pagocon == null;
    }
    return view('movimientoimprimir', compact('movimiento', 'pagocon'));
})->name('movimientos.imprimir');


Route::get('/', function () {
    // $organizations = Organization::all();
    // foreach ($organizations as $organization) {
    //     $organization->assignDefaultPlan();
    // }
    // $planPrice = PlanPrice::find(6);
    // $organizations = Organization::whereIn('id', [1, 113, 129])->get();
    // foreach ($organizations as $organization) {
    //     $organization->assignPlan($planPrice);
    // }
});

Route::get('/excelfile/reports/', [ExcelFileController::class, 'downloadReport'])->name('excelfile.downloadReport');
