<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Mailer;

/**
 * Appointment/booking notifications across email, WhatsApp and SMS.
 *
 * Email is fully implemented (via App\Core\Mailer / PHPMailer).
 *
 * WhatsApp and SMS are "integration-ready" per the project spec, not live:
 * no default provider is configured (see .env.example WHATSAPP_PROVIDER /
 * SMS_PROVIDER = "none"), because doing so would require real third-party
 * credentials this project does not have. What IS implemented and real:
 *   - A stable method signature (sendWhatsApp / sendSms) the rest of the
 *     app calls, so swapping in a real provider later touches only this
 *     class.
 *   - A working HTTP client call against each provider's documented API
 *     shape (Meta WhatsApp Cloud API for WhatsApp; a generic HTTP
 *     GET/POST gateway for SMS, which covers most Indian SMS gateways'
 *     typical integration pattern).
 *   - Config validation and clear logging when a provider is not
 *     configured, instead of silently pretending to send.
 * This has NOT been tested against a live WhatsApp/SMS account (no
 * credentials exist in this environment) — see docs/INSTALL.md for how to
 * configure and verify it against a real provider.
 */
final class NotificationService
{
    public function __construct(private array $config)
    {
    }

    public static function fromConfig(array $config): self
    {
        return new self($config);
    }

    public function sendAppointmentConfirmationEmail(array $appointment, string $toEmail, string $toName): bool
    {
        $mailer = Mailer::fromEnv();
        $subject = 'Appointment Confirmation - ' . $appointment['appointment_number'];
        $html = $this->renderAppointmentEmail($appointment);
        return $mailer->send($toEmail, $toName, $subject, $html);
    }

    private function renderAppointmentEmail(array $appointment): string
    {
        $date = htmlspecialchars((string) $appointment['scheduled_date'], ENT_QUOTES, 'UTF-8');
        $time = htmlspecialchars((string) $appointment['scheduled_time'], ENT_QUOTES, 'UTF-8');
        $number = htmlspecialchars((string) $appointment['appointment_number'], ENT_QUOTES, 'UTF-8');

        return <<<HTML
            <div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;">
                <h2>Your appointment is confirmed</h2>
                <p>Appointment number: <strong>{$number}</strong></p>
                <p>Date: <strong>{$date}</strong><br>Time: <strong>{$time}</strong></p>
                <p>Please arrive 10 minutes early. If you need to reschedule, reply to this email or call the clinic.</p>
            </div>
            HTML;
    }

    /**
     * Send a WhatsApp message via the Meta WhatsApp Cloud API.
     * Returns false (and logs why) if WHATSAPP_PROVIDER is not "meta" or
     * required credentials are missing — it never throws for a disabled
     * provider, since notification failures must not break the primary
     * booking/billing flow that triggered them.
     */
    public function sendWhatsApp(string $toPhoneE164, string $message): bool
    {
        $wa = $this->config['whatsapp'] ?? [];
        if (($wa['provider'] ?? 'none') !== 'meta') {
            Logger::info('WhatsApp notification skipped: no provider configured.', ['to' => $toPhoneE164]);
            return false;
        }
        if (empty($wa['api_url']) || empty($wa['access_token']) || empty($wa['phone_number_id'])) {
            Logger::warning('WhatsApp provider "meta" selected but credentials are incomplete.');
            return false;
        }

        $url = rtrim($wa['api_url'], '/') . '/' . $wa['phone_number_id'] . '/messages';
        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'to' => $toPhoneE164,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);

        return $this->postJson($url, $payload, [
            'Authorization: Bearer ' . $wa['access_token'],
            'Content-Type: application/json',
        ]);
    }

    /**
     * Send an SMS via a generic HTTP gateway (covers the common
     * query-string / JSON POST pattern used by most Indian bulk-SMS
     * gateways). Returns false and logs when SMS_PROVIDER is not
     * "generic_http" or is misconfigured.
     */
    public function sendSms(string $toPhone, string $message): bool
    {
        $sms = $this->config['sms'] ?? [];
        if (($sms['provider'] ?? 'none') !== 'generic_http') {
            Logger::info('SMS notification skipped: no provider configured.', ['to' => $toPhone]);
            return false;
        }
        if (empty($sms['api_url']) || empty($sms['api_key'])) {
            Logger::warning('SMS provider "generic_http" selected but credentials are incomplete.');
            return false;
        }

        $payload = json_encode([
            'to' => $toPhone,
            'message' => $message,
            'sender_id' => $sms['sender_id'] ?? '',
        ]);

        return $this->postJson($sms['api_url'], $payload, [
            'Authorization: Bearer ' . $sms['api_key'],
            'Content-Type: application/json',
        ]);
    }

    private function postJson(string $url, string $payload, array $headers): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 300) {
            Logger::error('Notification HTTP send failed', ['url' => $url, 'http_code' => $httpCode, 'curl_error' => $error]);
            return false;
        }
        return true;
    }
}
