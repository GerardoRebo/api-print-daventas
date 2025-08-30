<?php

namespace App\Services\Pagos;

use App\Models\Organization;
use App\Models\PaqueteTimbre;
use App\Models\PaymentIntent;
use App\Models\PlanPrice;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoPlanService
{
    function __construct()
    {
        // Agrega credenciales
        MercadoPagoConfig::setAccessToken(
            config('mercadopago.access_token')
        );
    }
    // Function that will return a request object to be sent to Mercado Pago API
    function createPreferenceRequest($items, $payer, $intent): array
    {
        // $paymentMethods = [
        //     "excluded_payment_methods" => [],
        //     "installments" => 12,
        //     "default_installments" => 1
        // ];

        $backUrls = array(
            'success' => config('app.spa_url') . '/mercadopago_success',
            'failure' =>  config('app.spa_url') . '/mercadopago_failed',
            'pending' =>  config('app.spa_url') . '/mercadopago_pending',
        );
        $request = [
            "items" => $items,
            "payer" => $payer,
            // "payment_methods" => $paymentMethods,
            "back_urls" => $backUrls,
            // "statement_descriptor" => "NAME_DISPLAYED_IN_USER_BILLING",
            "external_reference" => $intent->id,
            "expires" => false,
        ];
        if (app()->environment('production')) {
            $request['auto_return'] = 'approved';
        }

        return $request;
    }
    function createPreference(PlanPrice $planPrice, User $user)
    {
        $plan = $planPrice->plan;
        $intent = $planPrice->paymentIntent()->create([
            'user_id' => $user->id,
            'organization_id' => $user->organization->id,
        ]);
        $payer = array(
            "name" => $user->name,
            "surname" => $user->surname ?? '',
            "email" => $user->email,
        );
        $items = array(
            array(
                "title" => $plan->name,
                "quantity" => intval($planPrice->meses),
                "unit_price" => floatval($planPrice->precio),
            )
        );
        $request = $this->createPreferenceRequest($items, $payer, $intent);

        // Create the request object to be sent to the API when the preference is created
        $client = new PreferenceClient();

        try {
            $preference = $client->create($request);
            return $preference;
        } catch (MPApiException $error) {
            // Handle the error
            // You can log the error or throw an exception
            // For example:
            Log::error('MercadoPago API Error: ' . $error->getMessage());
            Log::error('Response body: ' . json_encode($error->getApiResponse()));
            return null;
        }

        // echo $preference->id;
    }
}
