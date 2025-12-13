<?php

namespace App\Console\Commands;

use App\Models\TelegramConfig;
use App\Models\Ventaticket;
use App\Services\TelegramNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDeliveryNotifications extends Command
{
    protected $signature = 'notifications:send-delivery {type : today|tomorrow}';
    protected $description = 'Envía notificaciones de entregas por Telegram. Tipos: today (10 AM) o tomorrow (7 PM)';

    protected $telegramService;

    public function __construct(TelegramNotificationService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle()
    {
        $type = $this->argument('type');

        if ($type === 'today') {
            $this->sendTodayDeliveries();
        } elseif ($type === 'tomorrow') {
            $this->sendTomorrowDeliveries();
        } else {
            $this->error('Tipo inválido. Use: today o tomorrow');
            return 1;
        }
        logger()->info("Notificaciones de entregas '{$type}' enviadas correctamente.");

        return 0;
    }

    /**
     * Enviar notificaciones de trabajos que se entregan hoy (10 AM)
     */
    protected function sendTodayDeliveries()
    {
        $today = Carbon::today();

        // Obtener todos los tickets que se entregan hoy
        $tickets = Ventaticket::whereDate('fecha_entrega', $today)
            ->with(['user.configuration', 'organization', 'cliente', 'ventaticket_articulos'])
            ->get();

        $this->info("Procesando " . $tickets->count() . " entregas para hoy...");

        foreach ($tickets as $ticket) {
            $this->notifyUser($ticket, 'today');
        }

        $this->info('Notificaciones de entregas hoy enviadas correctamente.');
    }

    /**
     * Enviar notificaciones de trabajos que se entregan mañana (7 PM)
     */
    protected function sendTomorrowDeliveries()
    {
        $tomorrow = Carbon::tomorrow();

        // Obtener todos los tickets que se entregan mañana
        $tickets = Ventaticket::whereDate('fecha_entrega', $tomorrow)
            ->with(['user.configuration', 'organization', 'cliente', 'ventaticket_articulos'])
            ->get();

        $this->info("Procesando " . $tickets->count() . " entregas para mañana...");

        foreach ($tickets as $ticket) {
            $this->notifyUser($ticket, 'tomorrow');
        }

        $this->info('Notificaciones de entregas mañana enviadas correctamente.');
    }

    /**
     * Notificar al usuario sobre una entrega
     */
    protected function notifyUser(Ventaticket $ticket, string $type)
    {
        try {
            $user = $ticket->user;
            $organization = $ticket->organization;

            // Buscar configuración de Telegram del usuario o de la organización
            $telegramConfig = TelegramConfig::where('is_active', true)
                ->where(function ($query) use ($user, $organization) {
                    $query->where('user_id', $user->id)
                        ->orWhere('organization_id', $organization->id);
                })
                ->first();

            if (!$telegramConfig) {
                $this->warn("No hay configuración de Telegram para el usuario {$user->name}");
                return;
            }

            // Verificar si el usuario quiere recibir notificaciones para este tipo
            if ($type === 'today' && !$telegramConfig->notify_delivery_today) {
                return;
            }
            if ($type === 'tomorrow' && !$telegramConfig->notify_delivery_tomorrow) {
                return;
            }

            $message = $this->buildNotificationMessage($ticket, $type);

            $sent = $this->telegramService->sendMessage(
                $telegramConfig->telegram_bot_token,
                $telegramConfig->telegram_chat_id,
                $message
            );

            if ($sent) {
                $this->info("Notificación enviada a {$user->name} para ticket #{$ticket->consecutivo}");
            } else {
                $this->error("Error al enviar notificación a {$user->name} para ticket #{$ticket->consecutivo}");
            }
        } catch (\Exception $e) {
            $this->error("Error procesando ticket #{$ticket->consecutivo}: " . $e->getMessage());
        }
    }

    /**
     * Construir el mensaje de notificación
     */
    protected function buildNotificationMessage(Ventaticket $ticket, string $type): string
    {
        $clientName = $ticket->cliente->name ?? 'Cliente desconocido';
        $consecutivo = $ticket->consecutivo;
        $fechaEntrega = Carbon::parse($ticket->fecha_entrega)->format('d/m/Y H:i');
        $totalArticulos = $ticket->ventaticket_articulos->count();
        $organizationName = $ticket->organization->name ?? 'Daventas';

        if ($type === 'today') {
            $message = "📦 <b>ENTREGA PARA HOY</b>\n\n";
            $message .= "Recordatorio: Tienes un trabajo programado para entregar <b>hoy</b>\n\n";
        } else {
            $message = "📅 <b>ENTREGA MAÑANA</b>\n\n";
            $message .= "Recordatorio: Tienes un trabajo programado para entregar <b>mañana</b>\n\n";
        }

        $message .= "<b>Detalles:</b>\n";
        $message .= "👤 Cliente: <code>{$clientName}</code>\n";
        $message .= "🏷️ Orden: #<code>{$consecutivo}</code>\n";
        $message .= "⏰ Entrega: <code>{$fechaEntrega}</code>\n";
        $message .= "📋 Artículos: <code>{$totalArticulos}</code>\n";
        $message .= "🏢 Sistema: <code>{$organizationName}</code>\n\n";

        if ($ticket->nombre) {
            $message .= "<b>Descripción:</b> <code>{$ticket->nombre}</code>\n\n";
        }

        $message .= "⚠️ Por favor, asegúrate de completar esta entrega a tiempo.";

        return $message;
    }
}
