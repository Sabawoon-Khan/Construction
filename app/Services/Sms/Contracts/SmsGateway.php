<?php

namespace App\Services\Sms\Contracts;

interface SmsGateway
{
    /**
     * @throws \RuntimeException
     */
    public function send(string $phone, string $message): void;
}
