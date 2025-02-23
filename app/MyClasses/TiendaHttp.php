<?php

namespace App\MyClasses;

use Illuminate\Support\Facades\Http;

class TiendaHttp
{
    private $apiBaseUrl;
    private $token;

    function __construct()
    {
        $this->apiBaseUrl = config('app.shop_tienda_base_url');
        $this->token = config('app.shop_tienda_token'); // Your global API key
    }

    public function apiRequest($method, $endpoint, $params = [], $timeout = 50)
    {
        $response = Http::withToken($this->token)->timeout($timeout)->$method("{$this->apiBaseUrl}{$endpoint}", $params);

        return $response->json();
    }
}
