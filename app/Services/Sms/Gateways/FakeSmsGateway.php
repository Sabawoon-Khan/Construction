<?php

namespace App\Services\Sms\Gateways;

use App\Services\Sms\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

final class FakeSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        Log::channel(config('sms.log_channel', 'stack'))->info('FakeSmsGateway send', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }
}
