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
        $request->validate([
            'source_url' => 'required|url',
            'text' => 'required|string',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . config('d-id.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.d-id.com/animations', [
            'source_url' => $file->url,
            'driver_url' => "bank://nostalgia/",
            "webhook" => config('d-id.webhook_url')
        ]);

        if ($response->successful()) {
            $dIdResponse = $response->json();
            $dIdAnimationId = $dIdResponse['id'] ?? null;

            if ($dIdAnimationId) {
                $articulo = $file->articulo; // Get the parent VentaticketArticulo

                $newArticuloFile = $articulo->files()->create([
                    'd_id_animation_id' => $dIdAnimationId,
                    'filename' => 'animated_video_' . $dIdAnimationId . '.mp4', // Placeholder filename
                    'path' => 'pending_animation_' . $dIdAnimationId, // Placeholder path
                    'mime_type' => 'video/mp4',
                    'size' => 0, // Placeholder size
                ]);

                return response()->json([
                    'message' => 'Animation request sent and ArticuloFile created.',
                    'd_id_response' => $dIdResponse,
                    'new_articulo_file' => $newArticuloFile,
                ], 201);
            } else {
                return response()->json([
                    'message' => 'D-ID API response missing animation ID.',
                    'd_id_response' => $dIdResponse,
                ], 500);
            }
        } else {
            return response()->json($response->json(), $response->status());
        }
    }
}
