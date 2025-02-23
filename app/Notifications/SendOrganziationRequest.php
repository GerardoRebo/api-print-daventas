<?php

namespace App\Notifications;

use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class SendOrganziationRequest extends Notification implements ShouldQueue
{
    use Queueable;
    private $organization;
    /**
     * Create a new notification instance.
     */
    public function __construct(public int $orgId)
    {
        $this->organization =Organization::findOrFail($orgId);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
                    ->line('La organización: ' . $this->organization->name . ". Te ha enviado invitación para unirte.")
                    ->action('Aceptar Invitacion', $url)
                    ->line('Gracias!');
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'organization.request',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'orgId' => $this->orgId,
            ]
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
