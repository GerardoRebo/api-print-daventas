<?php

namespace App\Http\Controllers;

use App\MyClasses\Wha;
use Illuminate\Support\Facades\Log;

class WhaSessionController extends Controller
{
    function __construct(private Wha $wha) {
        
    }

    public function getStatus($sessionId)
    {
        $response = $this->wha->apiRequest('get', "/session/status/{$sessionId}");
        return response()->json($response);
    }

    public function startSession($sessionId)
    {
        $response = $this->wha->apiRequest('get', "/session/start/{$sessionId}");
        return response()->json($response);
    }

    public function getQRCode($sessionId)
    {
        $response = $this->wha->apiRequest('get', "/session/qr/{$sessionId}");
        return response()->json($response);
    }

    public function getQRCodeImage($sessionId)
    {
        $response = $this->wha->apiRequest('get', "/session/qr/{$sessionId}/image");
        if ($response->successful()) {
            // Return the image as a response
            return response($response->body(), 200)
                ->header('Content-Type', 'image/png');
        } else {
            Log::error('Failed to get QR code image', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to get QR code image'], $response->status());
        }
        // return response()->json($response); // Adjust based on the image handling
    }

    public function restartSession($sessionId)
    {
        $response = $this->wha->apiRequest('get', "/session/restart/{$sessionId}");
        return response()->json($response);
    }

    public function terminateSession($sessionId)
    {
        $response = $this->wha->apiRequest('get', "/session/terminate/{$sessionId}", [], 200);
        return response()->json($response);
    }

    public function terminateInactiveSessions()
    {
        $response = $this->wha->apiRequest('get', "/session/terminateInactive");
        return response()->json($response);
    }

    public function terminateAllSessions()
    {
        $response = $this->wha->apiRequest('get', "/session/terminateAll");
        return response()->json($response);
    }
}
