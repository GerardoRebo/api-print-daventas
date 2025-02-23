<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationalException;
use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Code;
use App\Models\CostoHistorial;
use App\Models\Descuento;
use App\Models\InventarioBalance;
use App\Models\InventHistorial;
use App\Models\Precio;
use App\Models\PrecioHistorial;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\User;
use App\MyClasses\ProductLogic;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //BuscaEnVistaProductos /DepartamentoQuery
    public function search(Request $request)
    {
        $user = $request->user();

        $keyword = '';
        if ($request->input('keyword') != '') {
            $keyword = $request->input('keyword');
        }
        $prioritario = null;
        if ($request->prioritario != 'false') {
            $prioritario = $request->input('prioritario');
        }
        $bajostock = null;
        if ($request->bajostock != 'false') {
            $bajostock = $request->input('bajostock');
        }

        $proveedorActualId = null;
        if ($request->input('proveedorActualId') != '0') {
            $proveedorActualId = $request->input('proveedorActualId');
        }
        $departamentoActualId = null;
        if ($request->input('departamentoActualId') != '0') {
            $departamentoActualId = $request->input('departamentoActualId');
        }
        $productosPaginados = Product::with('product_components')->leftJoin('departamento_product', 'products.id', '=', 'departamento_product.product_id')
            ->leftJoin('product_proveedor',  'products.id', '=', 'product_proveedor.product_id')
            ->leftJoin('precios', function ($join) use ($request) {
                $join->on('products.id', '=', 'precios.product_id')
                    ->where('precios.almacen_id', '=', $request->input("almacenActualId"));
            })
            ->leftJoin('inventario_balances', function ($join) use ($request) {
                $join->on('products.id', '=', 'inventario_balances.product_id')
                    ->where('inventario_balances.almacen_id', '=', $request->input("almacenActualId"));
            })->select(
                'products.*',
                'departamento_product.departamento_id',
                'product_proveedor.proveedor_id',
                'precios.precio',
                'precios.precio_mayoreo',
                'inventario_balances.cantidad_actual',
                'inventario_balances.invmin',
                'inventario_balances.invmax'
            )
            ->when($proveedorActualId, function ($query) use ($proveedorActualId) {
                return $query->where('proveedor_id', $proveedorActualId);
            })
            ->when($departamentoActualId, function ($query) use ($departamentoActualId) {
                return $query->where('departamento_id', $departamentoActualId);
            })
            ->when($prioritario, function ($query) {
                return $query->where('prioridad', 1);
            })
            ->when($bajostock, function ($query) {
                return $query->where('es_presentacion_de_compra', 1)
                    ->whereColumn('inventario_balances.cantidad_actual', '<', 'inventario_balances.invmin');
            })
            ->where('products.organization_id', $user->organization_id)
            ->where(function (Builder $query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('codigo', $keyword);
            })
            ->orderBy('name', 'asc')
            ->paginate(10);

        $productines = $productosPaginados->getCollection()->unique()->map(function ($item) {
            if ($item->es_kit) {
                $item->cantidad_actual = $item->getCantidadActual(request()->almacenActualId);
            }
            return $item;
        });
        return $productosPaginados->setCollection($productines);
    }

    //BuscaProducto en Modal /BasicQuery
    public function searchkeyword(Request $request, ProductLogic $productLogic)
    {
        $user = $request->user();
        $org = $user->organization_id;
        $codigo = $request->codigo;
        $almacenActualId = request()->almacenActualId;

        if ($request->codigo == null) {
            $codigo = '';
        }

        $basicQuery = $productLogic->basicQuery($almacenActualId);
        $productos = Cache::remember(
            'Org:' . $org . 'Cod::' . $codigo,
            20,
            function () use ($basicQuery, $codigo, $org) {
                return $basicQuery->where('name', 'like', '%' . $codigo . '%')
                    ->where('products.organization_id', $org)
                    ->orWhere('codigo', $codigo)
                    ->take(80)
                    ->orderBy('name', 'asc')
                    ->get();
            }
        );

        return $productLogic->agregaPrecios($productos, $almacenActualId);
    }
    //BuscaporCodigo PuntoVenta/Movimientos /BasicQuery
    public function searchCode(Request $request, ProductLogic $productLogic)
    {
        $user = $request->user();
        $almacenActualId = request()->almacenActualId;
        $product = Product::where('codigo', $request->codigo)->where('organization_id', $user->organization_id)->first();
        if ($product == null) {
            $product = Code::where('code', $request->codigo)->where('organization_id', $user->organization_id)->first();

            if ($product == null) return 'Producto No Encontrado';

            $product =  Product::find($product->product_id);
        }

        $basicQuery = $productLogic->basicQuery($almacenActualId);
        $products = $basicQuery->where('products.organization_id', $user->organization_id)
            ->where('products.id', $product->id)
            ->get();

        if ($products[0]->porcentaje_ganancia == 0) {
            try {
                $tabulador =  Redis::hgetall($user->organization_id . "tabular");
            } catch (Exception $e) {
                return  $tabulador = [];
            }
            if (empty($tabulador)) {
                $porcentajeG = 0;
            } else {
                asort($tabulador);
                foreach ($tabulador as $key => $value) {
                    $porcentajeG = $value;
                    if ($products[0]->pcosto <= $key) {
                        break;
                    }
                }
            }
        } else {
            $porcentajeG = $products[0]->porcentaje_ganancia;
        }

        $products = $productLogic->agregaPrecios($products, $almacenActualId);
        $products[0]->porcentaje_ganancia = $porcentajeG;
        return $products[0];
    }
    //getProductByIdPuntoVenta/Movimientos/BasicQuery
    public function showextend(Request $request, ProductLogic $productLogic)
    {
        $user = $request->user();
        $almacenActualId = request()->almacenActualId;

        $basicQuery = $productLogic->basicQuery($almacenActualId);
        $products = $basicQuery->where('products.organization_id', $user->organization_id)
            ->where('products.id', $request->product)
            ->get();

        $products = $productLogic->agregaPrecios($products, $almacenActualId);
        return $products[0];
    }
    //minMaxVista
    public function showextended($productId, Request $request)
    {
        $user = $request->user();
        $productines = Product::rightJoin('precios', 'products.id', '=', 'precios.product_id')
            ->rightJoin('inventario_balances', 'products.id', '=', 'inventario_balances.product_id')
            ->join('almacens', 'inventario_balances.almacen_id', '=', 'almacens.id')
            ->select(
                'products.*',
                'almacens.name as nam',
                'precios.precio',
                'precios.precio_mayoreo',
                'precios.almacen_id',
                'inventario_balances.cantidad_actual',
                'inventario_balances.almacen_id',
                'inventario_balances.invmin',
                'inventario_balances.invmax'
            )
            ->where('products.organization_id', $user->organization_id)
            ->where('products.id', $productId)
            ->get();

        $productines = $productines->unique('almacen_id');
        return $productines;
    }
    //componentesKitVista 
    public function searchkeywordsimple(Request $request)
    {
        $user = $request->user();
        $keyword = request()->input('keyword');
        if ($keyword == null) {
            $keyword = '';
        } else {
            $keyword = $keyword;
        }
        $productines = Product::where('name', 'like', '%' . $keyword . '%')
            ->where('es_kit', 0)
            ->where('organization_id', $user->organization_id)
            ->orderBy('name', 'asc')->take(50)->get();

        return $productines;
    }
    public function searchcodesimple(Request $request)
    {
        $user = $request->user();
        $codigo = request()->input('codigo');
        $product = Product::where('codigo', $codigo)->where('organization_id', $user->organization_id)->first();
        if ($product == null) {
            $product = Code::where('code', $codigo)->first();
            if ($product == null) {
                return 'Producto No Encontrado';
            }
            $product =  Product::find($product->product_id);
        }
        return $product;
    }
    public function agregarcomponente(Request $request)
    {
        $productActualId = request()->input('productActualId');
        $productEncontradoId = request()->input('productEncontradoId');
        $cantidad = request()->input('cantidad');
        if ($productActualId == $productEncontradoId) {
            throw new OperationalException("No es posible crear un kit, donde el producto base sea el mismo", 1);
        }
        ProductComponent::updateOrCreate(
            ['product_id' => $productActualId, 'product_hijo_id' => $productEncontradoId],
            ['cantidad' => $cantidad]
        );
    }
    public function store(Request $request, ProductLogic $pl)
    {
        $user = $request->user()->load('almacens');
        $validator = FacadesValidator::make(
            $request->all(),
            [
                'codigo' => "required|string|max:70",
                'name' => 'required|string|max:200',
                'descripcion' => 'nullable|string|max:100',
                'tventa' => 'required|string|max:10',
                'pcosto' => 'required|numeric',
                'porcentaje_ganancia' => 'nullable|numeric',
                'prioridad' => 'nullable|boolean',
                'es_kit' => 'required|boolean',
                'perecedero' => 'nullable|boolean',
            ],
            [],
            [
                'name' => 'Nombre',
                'tventa' => 'Tipo de Venta',
                'pcosto' => 'Costo',
            ]
        );
        $validator->validate();
        $codigo = $request->codigo;
        $validator->after(function (Validator $validator) use ($pl, $codigo) {
            $exist = $pl->exists($codigo);
            if ($exist) {
                $validator->errors()->add(
                    'codigo',
                    'El código proporcionado ya existe, elige otro'
                );
            }
        });
        $validator->validate();
        $newProduct = new Product;
        $newProduct->codigo = $request->codigo;
        $newProduct->name = $request->name;
        $newProduct->organization_id = $user->organization_id;
        $newProduct->descripcion = $request->descripcion;
        $newProduct->tventa = $request->tventa;
        $newProduct->pcosto = $request->pcosto;
        $newProduct->prioridad = $request->prioridad;
        $newProduct->porcentaje_ganancia = $request->porcentaje_ganancia;
        $newProduct->es_kit = $request->es_kit;
        $newProduct->save();

        $precioAlmacens = [];
        foreach ($user->almacens as $almacen) {
            $precioAlmacens[] = ['product_id' => $newProduct->id, 'almacen_id' => $almacen->id, 'precio' => 0, 'precio_mayoreo' => 0];
        }
        DB::table('precios')->insert($precioAlmacens);

        $inventarioAlmacens = [];
        foreach ($user->almacens as $almacen) {
            $inventarioAlmacens[] = ['product_id' => $newProduct->id, 'almacen_id' => $almacen->id, 'cantidad_actual' => 0];
        }
        DB::table('inventario_balances')->insert($inventarioAlmacens);

        return $newProduct;
    }
    public function show(Product $product)
    {
        return $product;
    }
    public function ajustar($product, $almacenActualId, Request $request)
    {
        /** @var User $user */
        $user = $request->user()->load('configuration');
        $request->validate([
            'cantidad' => 'nullable',
            'pcosto' => 'nullable',
            'precio_mayoreo' => 'nullable',
            'p_venta' => 'nullable',
        ]);
        $cantidad = $request->cantidad;
        $pcosto = $request->input('pcosto');
        $precioD = $request->pventa;
        $precioMayoreo = $request->precio_mayoreo;
        /** @var Product $productA */
        $productA = Product::find($product);
        $almacenActual = Almacen::find($almacenActualId);

        $almacenActual->processCambioCantidad($user, $productA, $cantidad);
        $almacenActual->processCambioPrecio($user, $productA, $precioD, true);
        $productA->procesaAjusteCosto($user, $pcosto, "Ajuste Manual");
        if ($precioMayoreo != 0) {
            $precio = $productA->getPrecioModel($almacenActual->id);
            $precio->precio_mayoreo = $precioMayoreo;
            $precio->save();
        }
    }
    public function ajustarGeneral($product, $almacenActualId, Request $request)
    {
        /** @var User $user */
        $user = $request->user()->load('configuration');
        $request->validate([
            'cantidad' => 'nullable',
            'pcosto' => 'nullable',
            'precio_mayoreo' => 'nullable',
            'p_venta' => 'nullable',
        ]);
        $cantidad = $request->cantidad;
        $pcosto = $request->input('pcosto');
        $precioD = $request->pventa;
        $precioMayoreo = $request->precio_mayoreo;
        /** @var Product $productA */
        $productA = Product::find($product);
        $almacenActual = Almacen::find($almacenActualId);

        $almacenActual->processCambioCantidad($user, $productA, $cantidad);

        foreach ($user->getMyOrgAlmacens() as $almacen) {
            $almacen->processCambioPrecio($user, $productA, $precioD, true);
        }
        $productA->procesaAjusteCosto($user, $pcosto, "Ajuste Manual");
        if ($precioMayoreo = !0) {
            $precio = $productA->getPrecioModel($almacenActual->id);
            $precio->precio_mayoreo = $precioMayoreo;
            $precio->save();
        }
    }

    public function ajustarminmax($product, $almacenActualId, Request $request)
    {
        $inventarioBalance = InventarioBalance::where('product_id', $product)
            ->where('almacen_id',  $almacenActualId)
            ->first();

        if (isset($request->min) && isset($inventarioBalance)) {
            $inventarioBalance->invmin = $request->min;
            $inventarioBalance->save();
        }
        if (isset($request->max) && isset($inventarioBalance)) {
            $inventarioBalance->invmax = $request->max;
            $inventarioBalance->save();
        }
    }
    public function getcomponents(Request $request)
    {
        $product = request()->input('productActualId');
        $components = ProductComponent::with('product_hijo')->where('product_id', $product)->get();
        return $components;
    }
    public function eliminarComponente(Request $request)
    {
        $componente = request()->input('componente');
        ProductComponent::destroy($componente);
    }
    public function historials(Request $request)
    {
        $user = $request->user();
        $almacenActualId = request()->input('almacenActualId');
        $productActualId = request()->input('productActualId');
        $dfecha = request()->input('dfecha');
        $hfecha = request()->input('hfecha');

        $fecha = new DateTime($hfecha);
        $fecha->add(new DateInterval('P1D'));

        $history = InventHistorial::with('user:id,name')->where('almacen_id', $almacenActualId)
            ->where('organization_id', $user->organization_id)
            ->where('product_id', $productActualId)
            ->whereBetween('created_at', [$dfecha, $fecha])
            ->orderBy('id', 'desc')
            ->paginate(12);

        return $history;
    }
    public function historialPrecio(Request $request)
    {
        $user = $request->user();
        $almacenActualId = request()->input('almacenActualId');
        $productActualId = request()->input('productActualId');
        $dfecha = request()->input('dfecha');
        $hfecha = request()->input('hfecha');

        $fecha = new DateTime($hfecha);
        $fecha->add(new DateInterval('P1D'));

        $history = PrecioHistorial::with('user')->where('almacen_id', $almacenActualId)
            ->where('organization_id', $user->organization_id)
            ->where('product_id', $productActualId)
            ->whereBetween('created_at', [$dfecha, $fecha])
            ->orderBy('id', 'desc')
            ->paginate(12);

        return $history;
    }
    public function historialCosto(Request $request)
    {
        $user = $request->user();
        $almacenActualId = request()->input('almacenActualId');
        $productActualId = request()->input('productActualId');
        $dfecha = request()->input('dfecha');
        $hfecha = request()->input('hfecha');

        $fecha = new DateTime($hfecha);
        $fecha->add(new DateInterval('P1D'));

        $history = CostoHistorial::with('user')->where('organization_id', $user->organization_id)
            ->where('product_id', $productActualId)
            ->whereBetween('created_at', [$dfecha, $fecha])
            ->orderBy('id', 'desc')
            ->paginate(12);

        return $history;
    }
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'codigo' => "required|string|max:70"/* |unique:products,codigo,$product->id */,
            'name' => 'required|nullable|string|max:200',
            'descripcion' => 'nullable|string|max:100',
            'tventa' => 'nullable|string|max:10',
            'pcosto' => 'nullable',
            'prioridad' => 'nullable|boolean',
            'porcentaje_ganancia' => 'nullable',
            'es_kit' => 'nullable|boolean',
            'perecedero' => 'nullable|boolean',
        ]);

        $product->update($request->all());
        return $product;
    }
    public function generateDesktopProducts()
    {
        $user = auth()->user();
        $org = $user->organization_id;
        $products = Product::select('id', 'organization_id', 'name', 'codigo', 'tventa', 'pcosto')
            ->where('organization_id', $org)->whereNotNull('name')->get();
        $almacens = Almacen::select('id', 'organization_id', 'name')
            ->where('organization_id', $org)->whereNotNull(['organization_id', 'name'])->get();
        $clientes = Cliente::select('id', 'organization_id', 'name')
            ->where('organization_id', $org)->whereNotNull(['organization_id', 'name'])->get();
        $almacenIds = $almacens->pluck('id');
        $precios = Precio::whereIn('almacen_id', $almacenIds)->whereNotNull('product_id')->get();

        $data = [
            'date' => getMysqlTimestamp($user->time_zone),
            'products' => $products->toArray(),
            'precios' => $precios->toArray(),
            'almacens' => $almacens->toArray(),
            'clientes' => $clientes->toArray(),
        ];
        Storage::put('desktop/' . $org . '.json', json_encode($data, JSON_PRETTY_PRINT));
    }
    public function desktopDownload()
    {
        $user = auth()->user();
        $org = $user->organization_id;
        $filePath = 'desktop/' . $org . '.json';
        if (Storage::exists($filePath)) {
            // return Storage::download($filePath);
            return Storage::download($filePath);
        } else {
            // Handle the case where the file does not exist
            return response()->json(['error' => 'Aun no has exportado un archivo, hazlo desde administración'], 404);
        }
    }
    public function destroy(Product $product)
    {
        Product::destroy($product->id);
    }
    public function getDescuentos(Request $request)
    {
        $id = $request->input('id');
        $product = Product::find($id);
        return $product->descuentos;
    }
    public function enviarDescuento(Request $request)
    {
        //todo validate, move logic
        $id = $request->input('params.id');
        $user = $request->user();
        $desde = $request->input('params.desde');
        $hasta = $request->input('params.hasta');
        $porcentaje = $request->input('params.porcentaje');
        $porcentaje_type = $request->input('params.porcentaje_type');

        /** @var Product $product */
        $product = Product::find($id);
        $product->createDescuento($desde, $hasta, $porcentaje, $porcentaje_type);
    }
    public function eliminarDescuento(Request $request)
    {
        $id = $request->input('params.id');
        return Descuento::destroy($id);
    }
}
