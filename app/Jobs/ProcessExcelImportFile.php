<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

class ProcessExcelImportFile implements ShouldQueue
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
        public string $newPath,
        public string $path,
        public int $orgId,
        public int $almacen
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $fileStream = Storage::get($this->newPath);
        $reader = Reader::createFromString($fileStream);  // Use fromString for in-memory reading
        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();
        $misEncabezados = [
            'Codigo',
            'Nombre',
            'Tipo de Venta',
            'Costo',
            'Precio',
            'Existencia',
            'Es Kit',
            'Minimo',
            'Maximo',
        ];

        $diff = array_diff($reader->getHeader(), $misEncabezados);
        if (!empty($diff)) {
            return Storage::put('excel/reports/' . $this->orgId, 'Los encabezados de las columnas son incorrecto' . implode(",", $reader->getHeader())  . now());
        }
        //Comprobación de campos
        $contents = '';
        $errors = 0;

        foreach ($records as $key => $record) {
            $record['Costo'] = preg_replace('/[^\d.]/', '', $record['Costo']);
            $record['Minimo'] = preg_replace('/[^\d.]/', '', $record['Minimo']);
            $record['Maximo'] = preg_replace('/[^\d.]/', '', $record['Maximo']);
            $record['Precio'] = preg_replace('/[^\d.]/', '', $record['Precio']);
            $validator = Validator::make($record, [
                'Codigo' => 'required|max:155|string',
                'Nombre' => 'required|max:155|regex:/[^"\'`]*/',
                'Costo' => 'nullable|numeric',
                'Tipo de Venta' => 'in:G,U',
                'Es Kit' => 'nullable|boolean',
                'Minimo' => 'nullable|numeric',
                'Maximo' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                $errors++;
                $contents .= $key . ' ';
                foreach ($validator->errors()->getMessages() as $key => $value) {
                    $contents .= $key . ': ' . implode(',', $value) . ' ';
                }
                $contents .= "\n";
            }

            //Check if its json encodable
            $isJsonEncodable = json_encode($record) !== false;
            if (!$isJsonEncodable) {
                $errors++;
                $contents .= 'Este registro:' . $key . ' no puede ser incluido, probablemente  debido a un caracter extraño';
                $contents .= "\n";
            }
        }

        if ($errors > 0) {
            $contentsHeader = "No será posible subir este archivo, encontramos las siguientes fallas: \n";
            $contents .= "" . now();
            $contentsHeader .= $contents;
            return Storage::put('excel/reports/' . $this->orgId, $contentsHeader);
        }
        Storage::put('excel/reports/' . $this->orgId, 'No se ha encontrado fallas en la estructura de tu archivo, los registros serán cargados en breve, ' . now());

        foreach ($records as $key => $record) {
            $record['Costo'] = preg_replace('/[^\d.]/', '', $record['Costo']);
            $record['Minimo'] = preg_replace('/[^\d.]/', '', $record['Minimo']);
            $record['Maximo'] = preg_replace('/[^\d.]/', '', $record['Maximo']);
            $record['Precio'] = preg_replace('/[^\d.]/', '', $record['Precio']);
            ProcessExcelImportRecord::dispatch($record, $this->orgId, $this->almacen)->delay(now()->addSeconds(2));
        }
        if (Storage::exists($this->path)) {
            Storage::delete($this->path);
        }
    }
    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error($exception->getMessage());
    }
}
