<?php

namespace App\Http\Controllers;

use App\Models\ArticuloFile;
use App\Models\VentaticketArticulo;
use Illuminate\Http\Request;
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

        foreach ($request->file('files') as $uploadedFile) {
            $path = $uploadedFile->store('public/articulos', 'public');

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
        logger($articuloFile);
        Storage::delete($articuloFile->path);
        $articuloFile->delete();
        return response()->json(['message' => 'File deleted successfully.'], 200);
    }
}
