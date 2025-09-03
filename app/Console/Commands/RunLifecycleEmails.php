<?php

namespace App\Console\Commands;

use App\Models\LifecycleEmailEvent;
use App\Models\User;
use App\Services\BrevoService;
use App\Services\RetentionCampaignService;
use Illuminate\Console\Command;

class RunLifecycleEmails extends Command
{
    protected $signature = 'lifecycle:run';
    protected $description = 'Envía emails de ciclo de vida/activación según actividad del usuario';

    public function handle(RetentionCampaignService $svc)
    {
        logger("Iniciando envío de emails de ciclo de vida...");
        // // Día 1: bienvenida
        // foreach ($svc->usersCreatedExactDaysAgo(0) as $user) {
        //     // $this->sendOnce($user, 'welcome_day1', fn() => Mail::to($user)->queue(new WelcomeDay1Mail($user)));
        //     $this->sendOnce($user, 'welcome_day1');
        // }

        $tutorials_url = 'https://www.youtube.com/playlist?list=PLM2CXXBc5Sqw_ANFZQUnK9_iBYPmr7FtW';
        $login_url = config('app.spa_url') . '/front/puntoventa?utm_source=email&utm_medium=lifecycle&utm_campaign=onboarding';



        // Día 3 desde alta: onboarding
        foreach ($svc->usersCreatedExactDaysAgo(3) as $user) {
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
            $this->sendOnce($user, 'onboarding', $subject, $html, $text);
        }


        // Semana 1: si <5 tickets en primeros 7 días
        foreach ($svc->usersCreatedExactDaysAgo(7) as $user) {
            $html = view('emails.week1', [
                'user' => $user,
                'tutorials_url' => $tutorials_url,
                'login_url' => $login_url
            ])->render();

            $text = view('emails.week1_text', [
                'user' => $user,
                'tutorials_url' => $tutorials_url,
                'login_url' => $login_url
            ])->render();
            $from = $user->created_at;
            $to = $user->created_at->copy()->addDays(7)->endOfDay();
            $count = $svc->countTicketsInRange($user, $from, $to);
            if ($count < 5) {
                $subject = '¿Puedo ayudarte a arrancar más rápido?';
                $this->sendOnce($user, 'week1', $subject, $html, $text);
            }
        }


        // Mes 1 (día 30): si <10 tickets en los primeros 30 días
        foreach ($svc->usersCreatedExactDaysAgo(30) as $user) {
            $html = view('emails.month1', [
                'user' => $user,
                'tutorials_url' => $tutorials_url,
                'login_url' => $login_url
            ])->render();

            $text = view('emails.month1_text', [
                'user' => $user,
                'tutorials_url' => $tutorials_url,
                'login_url' => $login_url
            ])->render();
            $from = $user->created_at;
            $to = $user->created_at->copy()->addDays(30)->endOfDay();
            $count = $svc->countTicketsInRange($user, $from, $to);
            if ($count < 10) {
                $subject = '¿Cómo va todo en tu primer mes usando Daventas?';
                $this->sendOnce($user, 'month1', $subject, $html, $text);
            }
        }


        // Mes 2 (día 60): si <20 tickets en 60 días
        foreach ($svc->usersCreatedExactDaysAgo(60) as $user) {
            $html = view('emails.month2', [
                'user' => $user,
                'tutorials_url' => $tutorials_url,
                'login_url' => $login_url
            ])->render();

            $text = view('emails.month2_text', [
                'user' => $user,
                'tutorials_url' => $tutorials_url,
                'login_url' => $login_url
            ])->render();
            $from = $user->created_at;
            $to = $user->created_at->copy()->addDays(60)->endOfDay();
            $count = $svc->countTicketsInRange($user, $from, $to);
            if ($count < 20) {
                $subject = '¿Te ayudamos a sacarle más provecho?';
                $this->sendOnce($user, 'month2', $subject, $html, $text);
            }
        }

        $this->info('Lifecycle emails processed.');
        return self::SUCCESS;
    }

    private function sendOnce($user, string $stage, $subject, $html, $text): void
    {
        $brevo = new BrevoService;
        if ($user->hasStageSent($stage)) {
            return;
        }
        $brevo->sendEmail(
            $user->email,
            $user->name,
            $subject,
            $html,
            $text,
            app()->environment('local') // sandbox en local
        );
        LifecycleEmailEvent::create([
            'user_id' => $user->id,
            'stage' => $stage,
            'sent_at' => now(),
        ]);
    }
}
