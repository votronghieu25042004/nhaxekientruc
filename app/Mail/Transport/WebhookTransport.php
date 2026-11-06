<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookTransport extends AbstractTransport
{
    protected string $webhookUrl;

    public function __construct(string $webhookUrl)
    {
        parent::__construct();
        $this->webhookUrl = $webhookUrl;
    }

    protected function doSend(SentMessage $message): void
    {
        $rawMessage = $message->getMessage();
        if (!$rawMessage instanceof Email) {
            $email = Email::from($rawMessage);
        } else {
            $email = $rawMessage;
        }

        $toAddresses = array_map(function (Address $addr) {
            return $addr->getAddress();
        }, $email->getTo());

        $to = implode(', ', $toAddresses);
        $subject = (string) $email->getSubject();
        $html = (string) $email->getHtmlBody();
        $text = (string) $email->getTextBody();
        if (empty($text) && !empty($html)) {
            $text = strip_tags($html);
        }

        Log::info("[WEBHOOK_MAIL_SENDING] Đang gửi email qua Google Apps Script Webhook tới [{$to}] | Subject: [{$subject}]");

        try {
            $response = Http::timeout(12)->post($this->webhookUrl, [
                'to' => $to,
                'subject' => $subject,
                'html' => $html,
                'text' => $text,
            ]);

            if ($response->successful()) {
                Log::info("[WEBHOOK_MAIL_SUCCESS] Gửi email thành công qua Webhook tới [{$to}]");
            } else {
                Log::error("[WEBHOOK_MAIL_FAILED] Webhook phản hồi HTTP " . $response->status() . ": " . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error("[WEBHOOK_MAIL_EXCEPTION] Lỗi khi gọi Webhook: " . $e->getMessage());
        }
    }

    public function __toString(): string
    {
        return 'webhook';
    }
}
