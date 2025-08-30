<?php
// app/Services/BrevoService.php

namespace App\Services;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;

class BrevoService
{
    protected $api;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', config('app.brevo_api_key'));
        $this->api = new TransactionalEmailsApi(null, $config);
    }

    /**
     * Envia un email transactional
     *
     * @param string $toEmail
     * @param string $toName
     * @param string $subject
     * @param string $htmlContent
     * @param string|null $textContent
     * @param bool $sandboxMode
     * @return \Brevo\Client\Model\CreateSmtpEmail
     */
    public function sendEmail($toEmail, $toName, $subject, $htmlContent, $textContent = null, $sandboxMode = false)
    {
        $emailData = [
            'to' => [['email' => $toEmail, 'name' => $toName]],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'textContent' => $textContent ?? strip_tags($htmlContent),
            'sender' => ['name' => 'Tu App', 'email' => 'servicioalcliente@daventas.com'],
        ];

        // Si queremos sandbox, agregamos el header
        if ($sandboxMode) {
            $emailData['headers'] = ['X-Sib-Sandbox' => 'drop'];
        }

        $email = new SendSmtpEmail($emailData);

        return $this->api->sendTransacEmail($email);
    }
}
