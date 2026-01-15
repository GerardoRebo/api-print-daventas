<?php

namespace App\Http\Controllers;

use App\Models\TelegramConfig;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;

class TelegramConfigController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramNotificationService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Obtener todas las configuraciones de Telegram
     */
    public function index(Request $request)
    {
        $configs = TelegramConfig::with(['user', 'organization'])->get();
        return response()->json($configs);
    }

    /**
     * Obtener una configuración específica de Telegram
     */
    public function show(Request $request, TelegramConfig $telegramConfig)
    {
        $user = $request->user();

        // Verificar que el usuario tiene acceso a esta configuración
        if ($telegramConfig->user_id !== $user->id && $telegramConfig->organization_id !== $user->active_organization_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return response()->json($telegramConfig);
    }

    /**
     * Crear una nueva configuración de Telegram
     */
    public function store(Request $request)
    {
        $request->validate([
            'telegram_bot_token' => 'required|string',
            'telegram_chat_id' => 'required|string',
            'notify_delivery_today' => 'boolean',
            'notify_delivery_tomorrow' => 'boolean',
        ]);

        $user = $request->user();
        $organization = $user->organization;

        // Validar el token
        if (!$this->telegramService->validateToken($request->telegram_bot_token)) {
            return response()->json(['error' => 'Token de Telegram inválido'], 422);
        }

        $config = TelegramConfig::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'telegram_bot_token' => $request->telegram_bot_token,
            'telegram_chat_id' => $request->telegram_chat_id,
            'notify_delivery_today' => $request->notify_delivery_today ?? true,
            'notify_delivery_tomorrow' => $request->notify_delivery_tomorrow ?? true,
            'is_active' => true,
        ]);

        return response()->json($config, 201);
    }

    /**
     * Actualizar una configuración de Telegram
     */
    public function update(Request $request, TelegramConfig $telegramConfig)
    {
        $user = $request->user();

        // Verificar que el usuario tiene acceso a esta configuración
        if ($telegramConfig->user_id !== $user->id && $telegramConfig->organization_id !== $user->active_organization_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'telegram_bot_token' => 'sometimes|string',
            'telegram_chat_id' => 'sometimes|string',
            'notify_delivery_today' => 'boolean',
            'notify_delivery_tomorrow' => 'boolean',
        ]);

        // Validar el token si se proporciona uno nuevo
        if ($request->has('telegram_bot_token')) {
            if (!$this->telegramService->validateToken($request->telegram_bot_token)) {
                return response()->json(['error' => 'Token de Telegram inválido'], 422);
            }
        }

        $telegramConfig->update($request->only([
            'telegram_bot_token',
            'telegram_chat_id',
            'notify_delivery_today',
            'notify_delivery_tomorrow',
        ]));

        return response()->json($telegramConfig);
    }

    /**
     * Eliminar una configuración de Telegram
     */
    public function destroy(Request $request, TelegramConfig $telegramConfig)
    {
        $user = $request->user();

        // Verificar que el usuario tiene acceso a esta configuración
        if ($telegramConfig->user_id !== $user->id && $telegramConfig->organization_id !== $user->active_organization_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $telegramConfig->delete();

        return response()->json(['message' => 'Configuración eliminada correctamente']);
    }

    /**
     * Enviar un mensaje de prueba
     */
    public function testMessage(Request $request, TelegramConfig $telegramConfig)
    {
        $user = $request->user();

        // Verificar que el usuario tiene acceso a esta configuración
        if ($telegramConfig->user_id !== $user->id && $telegramConfig->organization_id !== $user->active_organization_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $message = "🧪 <b>Mensaje de Prueba</b>\n\n";
        $message .= "Este es un mensaje de prueba de tu configuración de Telegram.\n";
        $message .= "Si recibes este mensaje, la configuración está correcta.";

        $sent = $this->telegramService->sendMessage(
            $telegramConfig->telegram_bot_token,
            $telegramConfig->telegram_chat_id,
            $message
        );

        if ($sent) {
            return response()->json(['success' => true, 'message' => 'Mensaje de prueba enviado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'Error al enviar el mensaje de prueba'], 500);
        }
    }
}
