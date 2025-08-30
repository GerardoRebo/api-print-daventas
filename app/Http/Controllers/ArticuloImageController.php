<?php

namespace App\Http\Controllers;

use App\Models\ArticuloFile;
use App\Models\VentaticketArticulo;
use Exception;
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
        // $file->url = 'https://api-print-daventas.s3.us-west-1.amazonaws.com/public/articulos/IMG-20160410-WA0005.jpg?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Credential=ASIAZ46LKNEMXMY4CEHO%2F20250716%2Fus-west-1%2Fs3%2Faws4_request&X-Amz-Date=20250716T010013Z&X-Amz-Expires=300&X-Amz-Security-Token=IQoJb3JpZ2luX2VjEDkaCXVzLXdlc3QtMSJGMEQCIBgDDNJcx9w9ej%2Bcw1QXspl7kmUrvOawcj4z3%2Fnm%2BFZMAiBKKqZ6QMLNfBkv3XNLgWQN6ObM%2FYepQs4MxVe7Dfh7dCrtAghSEAAaDDY4MDY0MTg0OTYyNSIMFOs4Z6OZZsyCzNZLKsoCSGXWiO%2FJA5o8Lcg5%2ByXdriurvoYFc%2BVGNQ%2BLJuO8wqnnYEps9zGy211sGu%2BCm%2FVISnV8waD51kE1EJWgwJ%2BgJNXgyurrUVXJMi39BN%2FzFfoIgvMzYt3ykKRcG5IbGwWvLsruNae6E8vc7p1%2Fl3yF2s2bdTbCHhRrvY5NhY4sgKly56Qo8BdsNxArPzDI8Nyw01dGkIDE8z4tdejXB0tyPauDiZpNONFaBbJmuXkjRJ6uXWSyiOfXL2s1W6%2B72Iu2aGjewjeLKoQRUKh9NO%2FT6vSV9%2BStFpcK0mnbb8aG%2B%2Fx8USvL2nqQTl%2Fo%2BjzZ55ZNwu5ssE%2FH3aO56AUU7wiobmpPGu5hCUEAxgIOBkm%2B9dd31E%2BPDPS%2FmbLXUKUMI7clifNzOixM3TFkbuoYGI1lFUw9iQ1qXoPqK62ZR3L%2B2oveuJNLj16Jg2v4MNfy28MGOq4CLubrXrpVMobAf3ikD0zuIk%2FPmTlRRduUBHP5d5Ak0deV1i8sx0LnDS41ALD9J3ChrMnl8VMjRamMsaP7gWp5pXfRt0iYWN0YsasLDfzrWAszucBG%2FcoWbaHMWJrsiPrIrMBimmhV12HQyZscoUIqxv4s9JN2taJ0dpgb5dWlF0NMhdYxya9SwmiRpFdpdn1bdAhF5gmyqhH4fDEf9iqpvW3bUg8F8%2B9yXoW01VKRKUcDd5s8uQfEBHWrx6iykfcnUZ6Df%2BSiV31GgI8NbVCxk6zYwWTw7M6QIiBobTIW7gfPL9wfijD%2FhhmYgWqNgLIylD7UAKs%2FP92hYnY73X9ssqkg%2BOMm5LvwjAhhc4xJU33dmIbZs8HTFl%2BLLY3UBaOFyaXaMqD9H31sYg9yv7o%3D&X-Amz-Signature=e13dc1e92922b2ecd5646d43e674933d090a2c8546c4220dd0a0af3796b06dcc&X-Amz-SignedHeaders=host&response-content-disposition=inline';
        // logger($file->url);
        // return;
        $rawKey = config('d-id.api_key'); // your key like: username:actual_api_key
        $encodedKey = base64_encode($rawKey);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encodedKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.d-id.com/animations', [
            'source_url' => 'https://api-print-daventas.s3.us-west-1.amazonaws.com/public/articulos/IMG_1775.JPG?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Credential=ASIAZ46LKNEM667V6US2%2F20250716%2Fus-west-1%2Fs3%2Faws4_request&X-Amz-Date=20250716T024630Z&X-Amz-Expires=300&X-Amz-Security-Token=IQoJb3JpZ2luX2VjEDsaCXVzLXdlc3QtMSJHMEUCIQDtfn7kFEfwZRPOyitTqXS5yDOrBuYh5AXc98QqnVMk4QIgV3ZG2CZQ1sVcyGB38kjbIYcj0bky29mgZoY90Xelpjkq7QIIVBAAGgw2ODA2NDE4NDk2MjUiDNhWJLtbQQdDX7sK3irKAvH7TfWywcMzRWw3UO3F2yd2s21hKRLZtTr%2Bt7v8Ma%2Ffgbo5j8GY4coWepzwEsbaqLC5w9lTIzFFHCPhU4FB%2BbrKOjsoUAHwXo8szTDePi%2BwFVsS0yppk54s194BtsjsDrotAdJ7tDnXTEeWeVW%2FSjjEzgA1nRepwDGxs1PG5iGS1m6M4DvsQd52qthYSnBfMBS4gm7dNv63nR8PgiDS0YaPi3N9e1rgpOX2FehJpFYp8g7Aj%2BeyYGtHUyIe4xRPfwkom2RPr2B4LyITJ8Jm5GgRkJO9uIN5UNpa08rPVTGibyR6WQ2sLzqikpvre%2BLgMpkLN2HascSdMcyE0%2BpZtACUPQATpbDx4SDqrAeHkAIrsfjByPSfULVPp%2FuoxC%2B7YqY5x%2FvDOHVmAWYMd0o4rCl2Yj8EAtBN%2FWN2Vvj%2BGShEXN66bHh3zkAQXjC3pNzDBjqtAvoWfDtm5rr31oSjCqUYrstfSH9cYbA3RC6H%2FKmSpsJTSMfIsnUn2GnhbLnEMnr6poG0GE3VVMbqBkdF7nP0riL%2FSRV%2FlHz4IKMx9T%2B3vh8qMLMrW%2BTThIKNVia4dctWipbJ9w4xDxI63xf14Ty90RSbm20CBWqGKJf27%2Bwnm5ktVQ3OMdiyOsebp9W7oixjhQxuQOpTdabjmzrrJhNmHIlbq3eUPLB6VwdtsE06%2BEF%2FS%2B93MAlsGpUzHLA54BENJ9Gs207RbdxRoVDyV%2B3gE6aZhJ6Z8L70Kw8SvA%2FiMEZ5wkGysFScG7%2BC9wAUuKTKnJI9B8QJiuYy%2BQhS63RwTSqY2%2BAo3xyuPqwO6lIDNsn0Y0AglW4JerSe94k1vIqTSxvOHMgpDYC8kOizK8E%3D&X-Amz-Signature=1ecf409a58b60f24b4be8358621d50beaf47e7e0d9182aad0b34eb22b60a4743&X-Amz-SignedHeaders=host&response-content-disposition=inline',
            'driver_url' => "bank://nostalgia/",
            "webhook" => config('d-id.webhook_url')
        ]);

        if ($response->successful()) {
            $dIdResponse = $response->json();
            $dIdAnimationId = $dIdResponse['id'] ?? null;

            if ($dIdAnimationId) {
                $ventaticket_articulo = $file->ventaticket_articulo; // Get the parent VentaticketArticulo

                $newArticuloFile = $ventaticket_articulo->files()->create([
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
    function checkStatusAnimations(Request $request, ArticuloFile $file)
    {
        $rawKey = config('d-id.api_key'); // your key like: username:actual_api_key
        $encodedKey = base64_encode($rawKey);
        if ($file->d_id_animation_id === null) {
            throw new Exception("Error Processing Request", 1);
        }
        return $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encodedKey,
            'Content-Type' => 'application/json',
        ])->get('https://api.d-id.com/animations/' . $file->d_id_animation_id);
        if (str_starts_with($file->path, 'pending_animation')) {
            return 'pending';
        } else {
            return 'done';
        }
    }
}
