<?php
// app/Services/TrueListEmailValidator.php

namespace App\Services\Mail;

use GuzzleHttp\Client;

class TrueListEmailValidator
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('TRUELIST_API_KEY');
        $this->client = new Client([
            'base_uri' => 'https://api.truelist.io/api/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    public function validate($email)
    {
        $response = $this->client->post('verify_inline', [
            'json' => ['email' => $email],
        ]);

        return json_decode($response->getBody(), true);
    }
}
