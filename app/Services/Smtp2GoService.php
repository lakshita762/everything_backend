<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class Smtp2GoService
{
    public function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): void
    {
        $apiKey = trim((string) config('services.smtp2go.api_key'));
        $endpoint = (string) config('services.smtp2go.endpoint', 'https://api.smtp2go.com/v3/email/send');
        $fromAddress = trim((string) config('services.smtp2go.from_address'));
        $fromName = trim((string) config('services.smtp2go.from_name'));

        if ($apiKey === '') {
            throw new RuntimeException('SMTP2GO_API_KEY is not configured.');
        }

        if ($fromAddress === '') {
            throw new RuntimeException('SMTP2GO_FROM_ADDRESS (or MAIL_FROM_ADDRESS) is not configured.');
        }

        $sender = $fromName !== ''
            ? sprintf('%s <%s>', $fromName, $fromAddress)
            : $fromAddress;

        $payload = [
            // Include API key in payload as well for maximum SMTP2GO compatibility.
            'api_key' => $apiKey,
            'sender' => $sender,
            'to' => [$to],
            'subject' => $subject,
            'html_body' => $htmlBody,
            'text_body' => $textBody ?? Str::of(strip_tags($htmlBody))->squish()->toString(),
        ];

        $response = Http::asJson()
            ->timeout(20)
            ->withHeaders([
                'X-Smtp2go-Api-Key' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new RuntimeException('SMTP2GO request failed: ' . $response->status() . ' ' . $response->body());
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException('SMTP2GO returned a non-JSON response body: ' . $response->body());
        }

        $result = strtolower((string) ($json['result'] ?? ''));
        if ($result !== '' && $result !== 'success') {
            throw new RuntimeException('SMTP2GO API error: ' . $response->body());
        }

        $failedCount = data_get($json, 'data.failed');
        if (!is_numeric($failedCount)) {
            $failedRecipients = data_get($json, 'data.failed_recipients');
            if (is_array($failedRecipients)) {
                $failedCount = count($failedRecipients);
            } else {
                $failedCount = 0;
            }
        }

        if ((int) $failedCount > 0) {
            throw new RuntimeException('SMTP2GO reported failed recipients: ' . $response->body());
        }

        Log::info('smtp2go.invite_email_sent', [
            'to' => $to,
            'result' => $json['result'] ?? null,
            'succeeded' => data_get($json, 'data.succeeded'),
            'failed' => $failedCount,
            'email_id' => data_get($json, 'data.email_id'),
        ]);
    }
}
