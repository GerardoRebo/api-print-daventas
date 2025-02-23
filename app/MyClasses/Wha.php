<?php

namespace App\MyClasses;

use Illuminate\Support\Facades\Http;

class Wha
{
    private $apiBaseUrl;
    private $apiKey;

    function __construct()
    {
        $this->apiBaseUrl = config('wha.api_base_url');
        $this->apiKey = config('wha.api_key'); // Your global API key
    }

    public function apiRequest($method, $endpoint, $params = [], $timeout=30)
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
        ])->timeout($timeout)->$method("{$this->apiBaseUrl}{$endpoint}", $params);

        return $response->json();
    }

    public function sendMessage($sessionId, $phone, $content)
    {
        $params = [
            "chatId" => "521" . $phone . "@c.us",
            "contentType" => "string",
            "content" => $content
        ];
        $response = $this->apiRequest('post', "/client/sendMessage/{$sessionId}", $params);
        return response()->json($response);
    }
}
