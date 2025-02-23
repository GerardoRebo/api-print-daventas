<?php

namespace App\Jobs;

use App\Models\InventarioBalance;
use App\Models\Precio;
use App\Models\Product;
use App\MyClasses\Excel\ExcelFile;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessExcelImportRecord implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries=1;
    public $maxExceptions = 1;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public $record, public int $orgId, public int $almacen)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $excel = new ExcelFile;
        $this->record = $excel->formatValues($this->record);
        $product = Product::where('codigo', $this->record['Codigo'])
            ->where('organization_id', $this->orgId)->first();
        if ($product) {
            $product->name = $this->record['Nombre'];
            $product->pcosto = $this->record['Costo'];
            $product->tventa = $this->record['Tipo de Venta'];
            $product->es_kit = $this->record['Es Kit'];
            $product->save();
            $product = $product->id;
        } else {
            $product = DB::table('products')->insertGetId(
                [
                    'organization_id' => $this->orgId,
                    'codigo' => $this->record['Codigo'],
                    'name' => $this->record['Nombre'],
                    'pcosto' => $this->record['Costo'],
                    'tventa' => $this->record['Tipo de Venta'],
                    'es_kit' => $this->record['Es Kit'],
                ]
            );
        }
        Precio::updateOrInsert(
            ['product_id' => $product, 'almacen_id' => $this->almacen],
            ['precio' => $this->record['Precio']]
        );
        if ($this->record['Es Kit']) exit;
        InventarioBalance::updateOrInsert(
            ['product_id' => $product, 'almacen_id' => $this->almacen],
            [
                'cantidad_actual' => $this->record['Existencia'],
                'invmin' => $this->record['Minimo'],
                'invmax' => $this->record['Maximo'],
            ]
        );
    }
    public function failed(Throwable $exception)
    {
        Log::error($exception->getMessage());
    }
}
