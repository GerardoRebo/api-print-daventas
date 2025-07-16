<?php

namespace App\Http\Controllers;

use App\Models\ArticuloFile;
use App\Models\VentaticketArticulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ArticuloImageController extends Controller
{
    //
    function index(Request $request, VentaticketArticulo $articulo)
    {
        return response()->json(['images' => $articulo->files]);
    }
    public function attachFiles(Request $request, VentaticketArticulo $articulo)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf,psd|max:10240', // max 10MB
        ]);
        $disk = App::environment('production') ? 's3' : 'public';

        foreach ($request->file('files') as $uploadedFile) {
            if (app()->isLocal()) {
                $path = $uploadedFile->store('articulos', $disk);
            } else {
                $path = $uploadedFile->store('public/articulos', $disk);
            }
            $articulo->files()->create([
                'filename' => $uploadedFile->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
            ]);
        }

        return response()->json(['message' => 'Files uploaded successfully.'], 201);
    }
    function articuloFilesDelete(Request $request, ArticuloFile $articuloFile)
    {
        Storage::delete($articuloFile->path);
        $articuloFile->delete();
        return response()->json(['message' => 'File deleted successfully.'], 200);
    }
    function download(ArticuloFile  $file)
    {
        //path articulos/z8uKdTnrDwX4ZI93EuAWy0uYKS6liG6KEjqOhhKf.jpg
        $disk = App::environment('production') ? 's3' : 'public';
        if (Storage::disk($disk)->exists($file->path)) {
            return Storage::disk($disk)->download($file->path, $file->filename, ['Content-Type' => $file->mime_type]);
        }
        return response()->json(['message' => 'File not found.'], 404);
    }

    function animate(Request $request, ArticuloFile $file)
    {
        return $file;
        $request->validate([
            'source_url' => 'required|url',
            'text' => 'required|string',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . env('DID_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.d-id.com/animations', [
            'source_url' => $file->url,
            'driver_url' => "bank://nostalgia/",
        ]);

        return response()->json($response->json(), $response->status());
    }
}
