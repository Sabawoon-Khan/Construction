<?php

namespace App\Services\Sms;

use App\Services\Sms\Contracts\SmsGateway;
use App\Services\Sms\Gateways\FakeSmsGateway;
use InvalidArgumentException;

final class SmsGatewayManager
{
    public function driver(?string $driver = null): SmsGateway
    {
        $driver ??= (string) config('sms.default', 'fake');

        return match ($driver) {
            'fake', 'log' => app(FakeSmsGateway::class),
            default => throw new InvalidArgumentException("Unsupported SMS driver [{$driver}]."),
        };
    }

    public function send(string $phone, string $message): void
    {
        $this->driver()->send($phone, $message);
    }
}
