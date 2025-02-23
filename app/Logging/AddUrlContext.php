<?php

namespace App\Logging;

use Illuminate\Support\Facades\Request;

class AddUrlContext
{
    public function __invoke($logger)
    {
        $logger->pushProcessor(function ($record) {
            // Only add URL and payload if there is an HTTP request
            if (app()->runningInConsole() === false && app()->bound('request')) {
                // Add URL
                $record['extra']['url'] = Request::fullUrl();

                // Add POST payload if the request method is POST
                if (Request::isMethod('post')) {
                    $record['extra']['payload'] = Request::except(['password', 'credit_card']);
                }
            }

            return $record;
        });
    }
}
