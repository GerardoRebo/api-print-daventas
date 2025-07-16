<?php

namespace App\Http\Controllers;

use App\Models\ArticuloFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('D-ID Webhook received:', $request->all());

        $animationId = $request->input('id');
        $resultUrl = $request->input('result_url');
        $status = $request->input('status');

        if ($status === 'done' && $animationId && $resultUrl) {
            $articuloFile = ArticuloFile::where('d_id_animation_id', $animationId)->first();

            if ($articuloFile) {
                $articuloFile->path = $resultUrl;
                $articuloFile->mime_type = 'video/mp4';
                $articuloFile->save();
                Log::info('ArticuloFile updated successfully for animation ID: ' . $animationId);
                return response()->json(['status' => 'success', 'message' => 'ArticuloFile updated.']);
            } else {
                Log::warning('ArticuloFile not found for animation ID: ' . $animationId);
                return response()->json(['status' => 'error', 'message' => 'ArticuloFile not found.'], 404);
            }
        } else {
            Log::warning('D-ID Webhook: Missing animation ID or result URL, or status not "done".');
            return response()->json(['status' => 'error', 'message' => 'Invalid webhook payload or animation not done.'], 400);
        }
    }
}
