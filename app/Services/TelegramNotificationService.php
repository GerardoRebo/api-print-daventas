<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    protected $client;
    protected $baseUrl = 'https://api.telegram.org';

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Envía un mensaje a través de Telegram Bot
     * 
     * @param string $botToken Token del bot de Telegram
     * @param string $chatId ID del chat/usuario
     * @param string $message Mensaje a enviar
     * @param string $parseMode Formato del mensaje (HTML, Markdown, etc.)
     * @return bool
     */
    public function sendMessage(string $botToken, string $chatId, string $message, string $parseMode = 'HTML'): bool
    {
        try {
            $response = $this->client->post("{$this->baseUrl}/bot{$botToken}/sendMessage", [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => $parseMode,
                ]
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje a Telegram', [
                'error' => $e->getMessage(),
                'bot_token' => substr($botToken, 0, 10) . '...',
                'chat_id' => $chatId
            ]);
            return false;
        }
    }

    /**
     * Envía un documento a través de Telegram Bot
     * 
     * @param string $botToken Token del bot de Telegram
     * @param string $chatId ID del chat/usuario
     * @param string $filePath Ruta del archivo
     * @param string $caption Texto adicional
     * @return bool
     */
    public function sendDocument(string $botToken, string $chatId, string $filePath, string $caption = ''): bool
    {
        try {
            $response = $this->client->post("{$this->baseUrl}/bot{$botToken}/sendDocument", [
                'multipart' => [
                    [
                        'name' => 'chat_id',
                        'contents' => $chatId
                    ],
                    [
                        'name' => 'document',
                        'contents' => fopen($filePath, 'r'),
                    ],
                    [
                        'name' => 'caption',
                        'contents' => $caption
                    ]
                ]
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::error('Error enviando documento a Telegram', [
                'error' => $e->getMessage(),
                'file_path' => $filePath
            ]);
            return false;
        }
    }

    /**
     * Verifica si el token de Telegram es válido
     * 
     * @param string $botToken Token del bot de Telegram
     * @return bool
     */
    public function validateToken(string $botToken): bool
    {
        try {
            $response = $this->client->get("{$this->baseUrl}/bot{$botToken}/getMe");
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::error('Error validando token de Telegram', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
