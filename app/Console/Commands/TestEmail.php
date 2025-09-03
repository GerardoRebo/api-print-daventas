<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TestEmailNotification;
use App\Services\BrevoService;
use Illuminate\Console\Command;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending test emails via Brevo and SMTP...');
        if (app()->environment('production')) {
            $res = $this->sendBrevo();
            logger($res);
        }
        $this->sendSmtp();
        return Command::SUCCESS;
    }
    private function sendSmtp()
    {
        $user = User::find(1); // solo para prueba
        $user->notify(new TestEmailNotification());
    }
    private function sendBrevo()
    {
        $tutorials_url = null;
        $login_url = config('app.spa_url') . '/front/puntoventa?utm_source=email&utm_medium=lifecycle&utm_campaign=onboarding';

        $user = User::find(1); // solo para prueba
        $html = view('emails.onboarding', [
            'user' => $user,
            'tutorials_url' => $tutorials_url,
            'login_url' => $login_url
        ])->render();

        $text = view('emails.onboarding_text', [
            'user' => $user,
            'tutorials_url' => $tutorials_url,
            'login_url' => $login_url
        ])->render();

        $subject = '¿Ya conoces estas funciones clave de Daventas?';

        $brevo = new BrevoService;
        return $brevo->sendEmail(
            $user->email,
            $user->name,
            $subject,
            $html,
            $text,
            app()->environment('local') // sandbox en local
        );
    }
}
