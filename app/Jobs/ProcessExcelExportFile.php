<?php

namespace App\Jobs;

use App\Models\ExcelFile;
use App\MyClasses\ProductLogic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\CannotInsertRecord;
use League\Csv\Writer;

class ProcessExcelExportFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $maxExceptions = 1;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public $user,
        public $almacen,
        public $desde,
        public $hasta,
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ProductLogic $productLogic)
    {
        if ($this->almacen) {
            $basicQuery = $productLogic->basicQuery($this->almacen);
        } else {
            $basicQuery = $productLogic->basicQueryConsolidado();
        }
        $orgId = $this->user->organization_id;
        if ($this->almacen) {
            $columns = [
                'products.id',
                'codigo',
                'name',
                'tventa',
                'pcosto',
                'precio',
                'cantidad_actual',
                'es_kit',
                'invmin',
                'invmax',
            ];
        } else {
            $columns = [
                'products.id',
                'codigo',
                'name',
                'tventa',
                'pcosto',
                'es_kit',
            ];
        }
        if ($this->hasta == "z") {
            $products = $basicQuery->where('name', '>=', $this->desde)
                ->where('organization_id', $orgId)
                ->orderBy('name')
                ->select(...$columns)->get();
        } else {
            ++$this->hasta;
            $products = $basicQuery
                ->where('organization_id', $orgId)
                ->whereBetween('name', [$this->desde, $this->hasta])->orderBy('name')
                ->select(...$columns)->get();
        }
        if ($this->almacen) {
            $headers = [
                'Codigo',
                'Nombre',
                'Tipo de Venta',
                'Costo',
                'Precio',
                'Existencia',
                'Es Kit',
                'Minimo',
                'Maximo'
            ];
        } else {
            $headers = [
                'Codigo',
                'Nombre',
                'Tipo de Venta',
                'Costo',
                'Es Kit',
                'Existencia',
            ];
        }
        $fileId = ExcelFile::create([
            'organization_id' => $orgId,
            'tipo' => 'Cliente:Import',
            'estado' => 'Procesando...',
            'url' => '-',
        ]);
        $stringPath = 'excel/exports/Org_' . $orgId . '_file_' . $fileId->id . '.csv';
        if ($this->almacen) {
            $products = $productLogic->agregaPrecios($products, $this->almacen);
        } else {
            $products = $productLogic->agregaPreciosConsolidado($products, $this->user->organization->load('almacens'));
        }
        $products = $products->map(function ($item) {
            unset($item['id']);
            unset($item['precio_sugerido']);
            unset($item['taxes']);
            unset($item['descuentos']);
            unset($item['product_components']);
            return $item;
        });
        try {
            $fileStream = '';
            $writer = Writer::createFromString($fileStream, 'w');
            $writer->insertOne($headers);
            $writer->insertAll($products->toArray());
            Storage::put($stringPath, $writer->toString());
        } catch (CannotInsertRecord $e) {
            $e->getRecord();
            $fileId->estado = "error";
        }
        $fileId->url = $stringPath;
        $fileId->estado = "Terminado";
        $fileId->save();
    }
    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
