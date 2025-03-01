<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CodigoController;
use App\Http\Controllers\ConceptoController;
use App\Http\Controllers\CorteController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\DevolucionController;
use App\Http\Controllers\ExcelFileController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\OrganizacionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\ProductTaxController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\PuntoVentaController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RetentionRulesController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\TiendaOrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhaSessionController;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/register', [RegisterController::class, 'register'])->name('auth.register');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

Route::post('/email/verification-notification', function (Request $request) {
    return $request->user()->sendEmailVerificationNotification();
})->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    return $status === Password::RESET_LINK_SENT
        ? response()->json(['status' => __($status)])
        : response()->json(['email' => __($status)]);
})->middleware('guest')->name('password.email');
Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->setRememberToken(Illuminate\Support\Str::random(60));

            $user->save();

            event((new PasswordReset($user)));
        }
    );

    return $status === Password::PASSWORD_RESET
        ? response()->json('status', __($status))
        : response()->json(['email' => [__($status)]]);
})->middleware('guest')->name('password.update');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    // Route::controller(WhaSessionController::class)->prefix('wha_session')->name('session.')->group(function () {
    //     Route::get('/status/{sessionId}', 'getStatus')->name('getStatus');
    //     Route::get('/start/{sessionId}', 'startSession')->name('startSession');
    //     Route::get('/qr/{sessionId}', 'getQRCode')->name('getQRCode');
    //     Route::get('/qr/{sessionId}/image', 'getQRCodeImage')->name('getQRCodeImage');
    //     Route::get('/restart/{sessionId}', 'restartSession')->name('restartSession');
    //     Route::get('/terminate/{sessionId}', 'terminateSession')->name('terminateSession');
    //     Route::get('/terminateInactive', 'terminateInactiveSessions')->name('terminateInactiveSessions');
    //     Route::get('/terminateAll', 'terminateAllSessions')->name('terminateAllSessions');
    // });

    Route::controller(UserController::class)->prefix('user')->name('user.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/getAll', 'getAll')->name('getAll');
        Route::get('/getUserRol', 'getUserRol')->name('getUserRol');
        Route::get('/getAllRoles', 'getAllRoles')->name('getAllRoles');
        Route::get('/getCountNotf', 'getCountNotf')->name('getCountNotf');
        Route::get('/userInfo', 'getUserInfo')->name('getUserInfo');
        Route::get('/getNotifications', 'getNotifications')->name('getNotifications');
        Route::get('/getAllNotifications', 'getAllNotifications')->name('getAllNotifications');
        Route::post('/asignarRol', 'asignarRol')->name('asignarRol');
        Route::post('/actualizarInfo', 'actualizarInfo')->name('actualizarInfo');
        Route::post('/cambiaConstrasena', 'cambiaConstrasena')->name('cambiaConstrasena');
        Route::get('/searchTimezones', 'searchTimezones')->name('searchTimezones');
        Route::post('/setTimezone', 'setTimezone')->name('setTimezone');
        Route::post('/updateFeature', 'updateFeature')->name('updateFeature');
    });
    Route::controller(CodigoController::class)->prefix('codigos')->name('codigos.')->group(function () {
        Route::post('/eliminar', 'eliminar')->name('eliminar');
        Route::get('/{productActualId}', 'show')->name('show');
        Route::get('/agregar/{codigo}/{productActualId}', 'attach')->name('attach');
    });
    Route::controller(ClienteController::class)->prefix('clients')->name('clients.')->group(function () {
        Route::get('', 'getallclients')->name('getallclients');
        Route::get('/show', 'show')->name('show');
        Route::post('/setcliente', 'setcliente')->name('setcliente');
        Route::get('/allclients', 'allclients')->name('allclients');
        Route::post('', 'store')->name('store');
        Route::put('/{cliente}', 'update')->name('update');
        Route::delete('', 'delete')->name('delete');
        Route::get('/search', 'search')->name('search');
    });

    Route::controller(ExcelFileController::class)->prefix('excelfile')->name('excelfile.')->group(function () {
        Route::post('/import', [ExcelFileController::class, 'import'])->name('excelfile.import');
        Route::post('/export', [ExcelFileController::class, 'export'])->name('excelfile.export');
        Route::post('/getReport', [ExcelFileController::class, 'getReport'])->name('excelfile.getReport');
        Route::get('/fetchFiles', [ExcelFileController::class, 'fetchFiles'])->name('excelfile.fetchFiles');
        Route::post('/deleteFile', [ExcelFileController::class, 'deleteFile'])->name('excelfile.deleteFile');
        Route::get('/excelfile/downloadExported/{file}', [ExcelFileController::class, 'downloadExported'])->name('excelfile.downloadExported');
    });
    Route::controller(DevolucionController::class)->prefix('devolucion')->name('devolucion.')->group(function () {
        Route::post('/createDevolucion', 'createDevolucion')->name('createDevolucion');
        Route::get('/specificDevolucion', 'specificDevolucion')->name('specificDevolucion');
        Route::get('/getSpecificTicketForPrinting/{ticket}', 'getSpecificTicketForPrinting')->name('getSpecificTicketForPrinting');
        Route::get('/misDevoluciones', 'misDevoluciones')->name('misDevoluciones');
        Route::post('/eliminarDevolucion', 'eliminarDevolucion')->name('eliminarDevolucion');
        Route::post('/realizardevolucion', 'realizardevolucion')->name('realizardevolucion');
        Route::post('/enviarArticuloDevolucion', 'register')->name('register');
        Route::post('/destroyArticulo', 'destroyArticulo')->name('destroyArticulo');
    });
    Route::controller(PuntoVentaController::class)->prefix('puntoventa')->name('puntoventa.')->group(function () {
        Route::post('/acceptRetentionRules/{ventaticket}', 'acceptRetentionRules')->name('acceptRetentionRules');
        Route::post('/sendVentaToWha/{ticket}', 'sendVentaToWha')->name('sendVentaToWha');
        Route::get('/specific', 'specific')->name('specific');
        Route::get('/lastTicket', 'getLastVentaTicket')->name('lastTicket');
        Route::get('/misventas', 'misventas')->name('misventas');
        Route::get('/setpendiente', 'setpendiente')->name('setpendiente');
        Route::get('/pendientes', 'pendientes')->name('pendientes');
        Route::post('/register', 'register')->name('register');
        Route::get('/ventaticket', 'getVT')->name('getVT');
        Route::post('/destroyarticulo', 'destroyarticulo')->name('destroyarticulo');
        Route::post('/update', 'update')->name('update');
        Route::post('/guardarventa', 'guardarventa')->name('guardarventa');
        Route::post('/borrarticket', 'borrarticket')->name('borrarticket');
        Route::get('/getexistencias', 'getexistencias')->name('getexistencias');
        Route::get('/asignaralmacen', 'asignaralmacen')->name('asignaralmacen');
        Route::get('/getSpecificVTForPrinting/{ventaticket}', 'getSpecificVTForPrinting')->name('getSpecificVTForPrinting');
        Route::post('/setnombreticket', 'setnombreticket')->name('setnombreticket');
        Route::post('/cancelarventa', 'cancelarventa')->name('cancelarventa');
        Route::post('/verificarVentas', 'verificarVentas')->name('verificarVentas');
        Route::post('/syncLocalVentas', 'syncLocalVentas')->name('syncLocalVentas');
        Route::post('/facturar/{ticket}', 'facturar')->name('facturar');
        Route::get('/descargarXml/{ticket}', 'descargarXml')->name('descargarXml');
        Route::get('/descargarPdf/{ticket}', 'descargarPdf')->name('descargarPdf');
    });
    Route::controller(CotizacionController::class)->prefix('cotizacion')->name('cotizacion.')->group(function () {

        Route::post('/sendVentaToWha/{ticket}', 'sendVentaToWha')->name('sendVentaToWha');
        Route::post('/archivar/{cotizacionId}', 'archivar')->name('archivar');
        Route::get('/specific', 'specific')->name('specific');
        Route::get('/misventas', 'misventas')->name('misventas');
        Route::get('/setpendiente', 'setpendiente')->name('setpendiente');
        Route::post('/setcliente', 'setcliente')->name('setcliente');
        Route::get('/pendientes', 'pendientes')->name('pendientes');
        Route::post('/register', 'register')->name('register');
        Route::get('/cotizacion', 'getVT')->name('getVT');
        Route::post('/destroyarticulo', 'destroyarticulo')->name('destroyarticulo');
        Route::post('/update', 'update')->name('update');
        Route::post('/guardarventa', 'guardarventa')->name('guardarventa');
        Route::post('/borrarticket', 'borrarticket')->name('borrarticket');
        Route::get('/getexistencias', 'getexistencias')->name('getexistencias');
        Route::get('/asignaralmacen', 'asignaralmacen')->name('asignaralmacen');
        Route::get('/getSpecificVTForPrinting/{cotizacion}', 'getSpecificVTForPrinting')->name('getSpecificVTForPrinting');
        Route::post('/setnombreticket', 'setnombreticket')->name('setnombreticket');
        Route::post('/cancelarventa', 'cancelarventa')->name('cancelarventa');
        Route::post('/verificarVentas', 'verificarVentas')->name('verificarVentas');
    });
    Route::controller(MovimientoController::class)->prefix('movimientos')->name('movimientos.')->group(function () {
        Route::post('/cambiaPrecioGeneral', 'cambiaPrecioGeneral')->name('cambiaPrecioGeneral');
        Route::get('/specific', 'specific')->name('specific');
        Route::get('/getSpecificTicketForPrinting/{ticket}', 'getSpecificTicketForPrinting')->name('getSpecificTicketForPrinting');
        Route::get('/getOcById', 'getOcById')->name('getOcById');
        Route::get('/mismovimientos', 'mismovimientos')->name('mismovimientos');
        Route::get('/setpendiente', 'setpendiente')->name('setpendiente');
        Route::post('/setproveedor', 'setproveedor')->name('setproveedor');
        Route::post('/setmovimiento', 'setmovimiento')->name('setmovimiento');
        Route::get('/pendientes', 'pendientes')->name('pendientes');
        Route::post('/register', 'register')->name('register');
        Route::get('/movimiento', 'getVT')->name('ventaticket');
        Route::post('/destroyarticulo', 'destroyarticulo')->name('destroyarticulo');
        Route::post('/update', 'update')->name('update');
        Route::post('/guardar', 'guardar')->name('guardar');
        Route::post('/borrarticket', 'borrarticket')->name('borrarticket');
        // Route::get('/articulos', 'articulos')->name('artiulos');
        Route::get('/getexistencias', 'getexistencias')->name('getexistencias');
        Route::get('/asignaralmacen', 'asignaralmacen')->name('asignaralmacen');
        Route::post('/setnombreticket', 'setnombreticket')->name('setnombreticket');
        Route::post('/cancelarmovimiento', 'cancelarmovimiento')->name('cancelarmovimiento');
        Route::post('/cambiaprecio', 'cambiaprecio')->name('cambiaprecio');
    });
    Route::controller(ProductionOrderController::class)->prefix('production_orders')->name('production_orders.')->group(function () {
        Route::get('', 'index')->name('index');
        Route::put('{productionOrder}', 'update')->name('update');
        Route::post('{productionOrder}/storeConsumibleGenerico', 'storeConsumibleGenerico')->name('storeConsumibleGenerico');
    });
    Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function () {

        Route::get('/search/{keyword?}/{almacenActualId?}/{departamentoActualId?}/{proveedorActualId?}/{bajostock?}/{prioritario?}/{todos?}', 'search')->name('search');
        Route::get('/searchkeyword', 'searchkeyword')->name('searchkeyword');
        Route::get('/historials', 'historials')->name('historials');
        Route::get('/historialPrecio', 'historialPrecio')->name('historialPrecio');
        Route::get('/historialCosto', 'historialCosto')->name('historialCosto');
        Route::get('/searchkeywordsimple', 'searchkeywordsimple')->name('searchkeywordsimple');
        Route::get('/searchconsumiblekeywordsimple', 'searchconsumiblekeywordsimple')->name('searchconsumiblekeywordsimple');
        Route::get('/agregarcomponente', 'agregarcomponente')->name('agregarcomponente');
        Route::post('/agregarconsumible', 'agregarconsumible')->name('agregarconsumible');
        Route::get('/eliminarComponente', 'eliminarComponente')->name('eliminarComponente');
        Route::get('/eliminarConsumible', 'eliminarConsumible')->name('eliminarConsumible');
        Route::get('/getcomponents', 'getcomponents')->name('getcomponents');
        Route::get('/getconsumibles', 'getconsumibles')->name('getconsumibles');
        Route::get('/showextend/{product}/{almacenActualId}', 'showextend')->name('showextend');
        Route::get('/showextended/{product}', 'showextended')->name('showextended');
        Route::get('/searchcode/{codigo}/{almacenActualId}', 'searchcode')->name('searchcode');
        Route::get('/searchcodesimple', 'searchcodesimple')->name('searchcodesimple');
        Route::put('/ajustarGeneral/{product}/{almacenActualId}', 'ajustarGeneral')->name('ajustarGeneral');
        Route::put('/ajustar/{product}/{almacenActualId}', 'ajustar')->name('ajustar');
        Route::put('/ajustarminmax/{product}/{almacenActualId}', 'ajustarminmax')->name('ajustarminmax');
        Route::get('/getDescuentos', 'getDescuentos')->name('getDescuentos');
        Route::post('/enviarDescuento', 'enviarDescuento')->name('enviarDescuento');
        Route::post('/eliminarDescuento', 'eliminarDescuento')->name('eliminarDescuento');

        Route::post('/desktop', 'generateDesktopProducts')->name('desktop.generate');
        Route::get('/desktop/download', 'desktopDownload')->name('desktop.download');
    });
    Route::apiResource('products', ProductController::class)->only(['store', 'show', 'update', 'destroy']);

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/{product}/images', [ProductImageController::class, 'index']);
        Route::post('/{product}/images', [ProductImageController::class, 'attach']);
        Route::delete('/{product}/images/{image}', [ProductImageController::class, 'detach']);
        Route::patch('/{product}/images/{image}/featured', [ProductImageController::class, 'setFeatured']);
    });

    Route::prefix('products')->name('products.')->group(function () {
        Route::post('/{product}/taxes/{tax}', [ProductTaxController::class, 'store']);
        Route::delete('/{productId}/taxes/{taxId}', [ProductTaxController::class, 'delete']);
        Route::put('/taxes/{productTax}', [ProductTaxController::class, 'update']);

        // Route::get('/agregard/{impuestoActualId}/{productoActualId}', 'agregard')->name('agregard');
        // Route::get('/quitarD/{departamentoActualId}/{productoActualId}', 'quitarD')->name('quitarD');
    });

    Route::controller(ProveedorController::class)->prefix('proveedors')->name('proveedors.')->group(function () {
        Route::get('/agregarp/{proveedorActualId}/{productoActualId}', 'agregarp')->name('agregarp');
        Route::get('/quitarP/{proveedorActualId}/{productoActualId}', 'quitarP')->name('quitarP');
        Route::get('/showpp/{productoActualId}', 'showpp')->name('showpp');
        Route::get('/search/{keyword?}', 'search')->name('search');
    });
    Route::apiResource('proveedors', ProveedorController::class);

    Route::controller(OrganizacionController::class)->prefix('organizacions')->name('organizacions.')->group(function () {
        Route::get('/global', 'global')->name('global');
        Route::get('/foliosSaldo', 'foliosSaldo')->name('foliosSaldo');
        Route::get('/facturas_globales', 'getFacturasGlobales')->name('facturas_globales.index');
        Route::delete('/facturas_globales/{factura}', 'deleteFacturasGlobales')->name('facturas_globales.delete');
        Route::get('/facturas_globales/{facturaId}', 'facturasGlobalesShow')->name('facturas_globales.show');
        Route::get('/facturas', 'getFacturas')->name('facturas');
        Route::get('/facturas/{facturaId}', 'facturasShow')->name('facturas.show');
        Route::get('/facturas/{facturaId}', 'facturasShow')->name('facturas.show');
        Route::post('/pre_procesar', 'preProcesar')->name('preProcesar');
        Route::post('/timbrarFacturaGlobal/{facturaId}', 'timbrarFacturaGlobal')->name('timbrarFacturaGlobal');
        Route::get('/search', 'search')->name('search');
        Route::get('/searchAlmacen/{keywordAlmacen?}', 'searchAlmacen')->name('searchAlmacen');
        Route::get('/misusers', 'misusers')->name('misuser');
        Route::get('/organizationUsers', 'organizationUsers')->name('organizationUsers');
        Route::get('/misalmacens', 'misalmacens')->name('misalmacens');
        Route::get('/getInfo', 'getInfo')->name('getInfo');
        // Route::get('/registeruser', 'registeruser')->name('registeruser');
        Route::post('/registrarPago', 'registrarPago')->name('registrarPago');
        Route::get('/getPlans', 'getPlans')->name('getPlans');
        Route::get('/getPrecios', 'getPrecios')->name('getPrecios');
        Route::post('/registerPrecio', 'registerPrecio')->name('registerPrecio');
        Route::get('/showPlan', 'showPlan')->name('showPlan');
        Route::get('/showPrecio', 'showPrecio')->name('showPrecio');
        Route::put('/updatePrecio', 'updatePrecio')->name('updatePrecio');
        Route::put('/deletePrecio', 'deletePrecio')->name('deletePrecio');
        Route::post('/registerPlan', 'registerPlan')->name('registerPlan');
        Route::post('/deletePlan', 'deletePlan')->name('deletePlan');
        Route::post('/onOff', 'onOff')->name('onOff');
        Route::put('/asignarPlan', 'asignarPlan')->name('asignarPlan');
        Route::post('/updatePlan', 'updatePlan')->name('updatePlan');
        Route::put('/updateClavePrivadaSat/{id}', 'updateClavePrivadaSat')->name('updateClavePrivadaSat');
        Route::put('/updateClavePrivadaLocal/{id}', 'updateClavePrivadaLocal')->name('updateClavePrivadaLocal');
        Route::get('/setuserorganizationnew', 'setuserorganizationnew')->name('setuserorganizationnew');
        Route::post('/desvincularUser', 'desvincularUser')->name('desvincularUser');
        Route::post('/uploadCer', 'uploadCer')->name('uploadCer');
        Route::post('/uploadKey', 'uploadKey')->name('uploadKey');
        Route::post('/uploadLogo', 'uploadLogo')->name('uploadLogo');
        Route::post('/desvincularUser', 'desvincularUser')->name('desvincularUser');
        Route::get('/getin', 'getin')->name('getin');
        Route::get('/registeralmacen', 'registeralmacen')->name('registeralmacen');
        Route::get('/detachuser', 'detachuser')->name('detachuser');
        Route::get('/detachalmacen', 'detachalmacen')->name('detachalmacen');
        Route::get('/getorganizations', 'getorganizations')->name('getorganizations');
        Route::get('/getmyorganization', 'getmyorganization')->name('getmyorganization');
        Route::get('/getsolicitudes', 'getsolicitudes')->name('getsolicitudes');
        Route::get('/enviartabular', 'enviartabular')->name('enviartabular');
        Route::get('/eliminartabular', 'eliminartabular')->name('eliminartabular');
        Route::get('/gettabulares', 'gettabulares')->name('gettabulares');
        Route::post('/destroyInvitation', 'destroyInvitation')->name('destroyInvitation');
        Route::post('/enviarsolicitud', 'enviarsolicitud')->name('enviarsolicitud');
        Route::get('/configurations', 'configurations')->name('configurations');
        Route::post('/eliminaPrueba', 'eliminaPrueba')->name('eliminaPrueba');
        Route::get('/descargarXml/{ticket}', 'descargarXml')->name('descargarXml');
        Route::get('/descargarPdf/{ticket}', 'descargarPdf')->name('descargarPdf');
    });
    Route::apiResource('organizacions', OrganizacionController::class);

    Route::controller(AlmacenController::class)->prefix('almacens')->name('almacens.')->group(function () {

        Route::get('/search/{keyword?}', [AlmacenController::class, 'search'])->name('almacens.search');
        Route::get('/myalmacens', 'myalmacens')->name('myalmacens');
        Route::get('/useralmacens', 'useralmacens')->name('useralmacens');
        Route::post('/attachalmacen', 'attachalmacen')->name('attachalmacen');
        Route::post('/detachalmacen', 'detachalmacen')->name('detachalmacen');
    });
    Route::apiResource('almacens', AlmacenController::class);

    Route::apiResource('conceptos', ConceptoController::class);

    Route::controller(DepartamentoController::class)->prefix('departamentos')->name('departamentos.')->group(function () {

        Route::get('/agregard/{departamentoActualId}/{productoActualId}', 'agregard')->name('agregard');
        Route::get('/quitarD/{departamentoActualId}/{productoActualId}', 'quitarD')->name('quitarD');
        Route::get('/showpd/{productoActualId}', 'showpd')->name('showpd');
        Route::get('/search/{keyword?}', 'search')->name('search');
    });
    Route::apiResource('departamentos', DepartamentoController::class);

    Route::controller(TaxController::class)->prefix('impuestos')->name('impuestos.')->group(function () {
        Route::get('/retained', 'retained')->name('retained.index');
        Route::get('/agregard/{impuestoActualId}/{productoActualId}', 'agregard')->name('agregard');
        Route::get('/quitarD/{departamentoActualId}/{productoActualId}', 'quitarD')->name('quitarD');
        Route::get('/showpd/{productoActualId}', 'showpd')->name('showpd');
        Route::get('/claves', 'claves')->name('claves');
        Route::get('/unidades', 'unidades')->name('unidades');
        Route::put('/claves/{productId}', 'updateClave')->name('updateClave');
        Route::put('/updateTypeOfTax/{id}', 'updateTypeOfTax')->name('updateTypeOfTax');
        Route::put('/unidades/{productId}', 'updateUnidad')->name('updateUnidad');
    });
    Route::apiResource('impuestos', TaxController::class);

    Route::apiResource('retention_rules', RetentionRulesController::class);

    Route::controller(CorteController::class)->prefix('cortes')->name('cortes.')->group(function () {

        Route::get('/habilitarcaja', 'habilitarcaja')->name('habilitarcaja');
        Route::get('/getturnoactual', 'getturnoactual')->name('getturnoactual');
        Route::get('/getMisCortes', 'getMisCortes')->name('getMisCortes');
        Route::get('/getAcumulados', 'getAcumulados')->name('getAcumulados');
        Route::get('/getCorte/{corte}', 'getCorte')->name('getCorte');
        Route::get('/getMisMovimientos', 'getMisMovimientos')->name('getMisMovimientos');
        Route::get('/getUserMovimientos', 'getUserMovimientos')->name('getUserMovimientos');
        Route::post('/realizarcorte', 'realizarcorte')->name('realizarcorte');
        Route::post('/realizarmovimiento', 'realizarmovimiento')->name('realizarmovimiento');
        Route::post('/getconceptos', 'getconceptos')->name('getconceptos');
        Route::get('/{corte}', 'show')->name('show');
    });

    Route::controller(CreditoController::class)->prefix('creditos')->name('creditos.')->group(function () {
        Route::get('/getcreditos', 'getcreditos')->name('getcreditos');
        Route::get('/getClienteInfo', 'getClienteInfo')->name('getClienteInfo');
        Route::get('/getdeudas', 'getdeudas')->name('getdeudas');
        Route::get('/getalldeudas', 'getalldeudas')->name('getalldeudas');
        Route::post('/realizarabono/{deuda}', 'realizarabono')->name('realizarabono');
        Route::post('/facturarabono/{abono}', 'facturarabono')->name('facturarabono');
        Route::get('/getabonos', 'getabonos')->name('getabonos');
        Route::get('/getsaldo', 'getsaldo')->name('getsaldo');
    });
});
Route::middleware('auth.shop_tienda')->prefix('tienda/')->name('tienda.')->group(function () {
    Route::prefix('orders/')->name('order.')->group(function () {
        Route::post('/', [TiendaOrderController::class, 'store']);
    });
});
