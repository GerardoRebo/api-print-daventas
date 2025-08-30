<?php

namespace App\Services;


use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class RetentionCampaignService
{
    /**
     * Tickets del usuario en un rango.
     */
    public function countTicketsInRange(User $user, Carbon $from, Carbon $to): int
    {
        return $user->tickets()
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }


    /** Usuarios que cumplen X días desde alta */
    public function usersCreatedExactDaysAgo(int $days)
    {
        $date = now()->subDays($days)->startOfDay();
        return User::whereDate('created_at', $date)->get();
    }


    /** Usuarios creados hace >= N días */
    //no se esta usando
    public function usersCreatedAtLeastDaysAgo(int $days)
    {
        return User::where('created_at', '<=', now()->subDays($days))->get();
    }


    /** Usuarios sin tickets en últimos N días */
    //no se esta usando
    public function usersWithNoTicketsInLastNDays(int $days)
    {
        $since = now()->subDays($days);
        return User::whereDoesntHave('tickets', function ($q) use ($since) {
            $q->where('created_at', '>=', $since);
        })->get();
    }
}
