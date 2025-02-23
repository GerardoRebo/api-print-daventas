<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AjusteMInventario extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $user, $product, $precio, $tipo, $cantidad, $almacen;
    public function __construct($user, $product, $cantidad, $tipo, $almacen)
    {
        $this->user = $user;
        $this->product = $product;
        $this->precio = $cantidad;
        $this->tipo = $tipo;
        $this->cantidad = $cantidad;
        $this->almacen = $almacen;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
            'data' => 'Usuario ' . $this->user .
                ', realizo '.$this->tipo. ' (' .
                $this->cantidad . ') del producto ' . $this->product .
                '. Almacen '. $this->almacen->name
                
        ];
    }
}
