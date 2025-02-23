<?php

use App\Http\Controllers\ExcelFileController;
use App\Http\Requests\EmailVerificationRequest;
use App\Models\Devolucione;
use App\Models\Invitation;
use App\Models\OrdenCompra;
use App\Models\Product;
use App\Models\User;
use App\Models\Ventaticket;
use App\Pdf\Translators\PlatesHtmlTranslator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
Route::get('/email/organization_request/{id}/{orgId}', function () {
    $user = User::findOrFail(request('id'));
    $invitation = Invitation::where('user_id', $user->id)->first();
    if (!$invitation) return;
    $invitation->respondida = 1;
    $invitation->save();
    $user->organization_id = request('orgId');
    $user->save();
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
    // // $cfdifile = 'datafiles/cfdi.xml';
    // // $xml = file_get_contents($cfdifile);
    // $xml = Storage::get('xml_factura/129/467959.xml');

    // // clean cfdi
    // $xml = \PhpCfdi\CfdiCleaner\Cleaner::staticClean($xml);

    // // create the main node structure
    // $comprobante = \CfdiUtils\Nodes\XmlNodeUtils::nodeFromXmlString($xml);

    // // create the CfdiData object, it contains all the required information
    // $cfdiData = (new \PhpCfdi\CfdiToPdf\CfdiDataBuilder())
    //     ->build($comprobante);

    // // create the converter
    // $user = User::with('organization.image')->find(8);

    // $htmlTranslator = new PlatesHtmlTranslator(
    //     base_path('app/Pdf/Templates'),
    //     'generic',
    //     $user
    // );
    // $converter = new \PhpCfdi\CfdiToPdf\Converter(
    //     new \PhpCfdi\CfdiToPdf\Builders\Html2PdfBuilder($htmlTranslator)
    // );

    // // create the invoice as output.pdf
    // $converter->createPdfAs($cfdiData, Storage::path('/myPdfs/pdf.pdf'));
    // return response()->file(Storage::path('/myPdfs/pdf.pdf'));
});

Route::get('/excelfile/reports/', [ExcelFileController::class, 'downloadReport'])->name('excelfile.downloadReport');
