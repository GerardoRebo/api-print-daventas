<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationalException;
use App\Jobs\ProcessExcelExportFile;
use App\Jobs\ProcessExcelImportFile;
use App\Models\ExcelFile;
use App\MyClasses\ProductLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExcelFileController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:1000',
            'almacen' => 'required|int',
        ]);

        $user = auth()->user();
        $orgId = $user->active_organization_id;
        $almacen = $request->input('almacen');
        $file = $request->file('file');
        $almacens = $user->almacens;

        if (!$almacens->contains($almacen)) return "No cuentas con ese almacen";

        $path = $file->store('excel/imports');
        ExcelFile::create([
            'organization_id' => $orgId,
            'tipo' => 'Cliente:Export',
            'url' => $path,
            'estado' => 'Procesando...',
        ]);
        ProcessExcelImportFile::dispatch($path, $path, $orgId, $almacen);
        return "Exitoso";
    }
    public function export(Request $request)
    {
        $validated = $request->validate([
            'desde' => 'required|alpha_num',
            'hasta' => 'required|alpha_num',
            'almacen' => 'nullable|int',
        ]);
        $desde = $validated['desde'];
        $hasta = $validated['hasta'];
        $almacen = $validated['almacen'];

        $user = auth()->user();

        $filesCount = ExcelFile::where('organization_id', $user->active_organization_id)->where('estado', 'Terminado')->count();
        if ($filesCount > 2) return "TooMany";

        $almacens = $user->almacens;

        if ($almacen) {
            if (!$almacens->contains($almacen)) throw new OperationalException("No cuentas con este almacen", 1);
        }

        $productLogic = new ProductLogic;
        $basicQuery = $productLogic->basicQuery($almacen);

        if ($desde == "a") {
            $desde = "0";
        }
        if ($hasta == "z") {
            $count = $basicQuery
                ->where('organization_id', $user->active_organization_id)
                ->where('name', '>=', $desde)->count();
        } else {
            ++$hasta;
            $count = $basicQuery
                ->where('organization_id', $user->active_organization_id)
                ->whereBetween('name', [$desde, $hasta])->count();
        }

        if ($count > 4000) return throw new OperationalException("Demasiados registros, por favor divide la descarga  por letras", 1);

        ProcessExcelExportFile::dispatch($user, $almacen, $desde, $hasta);
        return "Exitoso";
    }
    public function deleteFile(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|int',
        ]);
        $file = $validated['file'];
        $file = ExcelFile::find($file);
        $storage = str_replace("/var/www/html/storage/app/", "", $file->url);
        $file->delete();
        Storage::delete($storage);
        return;
    }
    public function downloadExported(ExcelFile $file)
    {
        // return response()->download($file->url, 'MisDatos.csv');
        if (Storage::exists($file->url)) {
            $fileContent = Storage::get($file->url);
            return response($fileContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="factura_pdf_"' . now());
        }
    }
    public function fetchFiles()
    {
        $user = auth()->user();
        return ExcelFile::where('organization_id', $user->active_organization_id)
            ->where('tipo', 'Cliente:Import')
            ->get();
    }
    public function getReport()
    {
        $user = auth()->user();
        if (Storage::exists('excel/reports/' . $user->active_organization_id)) {
            return Storage::get('excel/reports/' . $user->active_organization_id);
        }
        return "Aun no se genera ningun reporte";
    }
}
