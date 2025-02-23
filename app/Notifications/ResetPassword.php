<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as NotificationsResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPassword extends NotificationsResetPassword implements ShouldQueue
{
    use Queueable;
}
