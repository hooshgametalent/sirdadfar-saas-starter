<?php

namespace App\Contracts;

/**
 * Sends a text message to an Iranian mobile number. The default
 * implementation only writes to the application log so the platform works
 * without any external provider. A real provider can be bound later.
 */
interface SmsSender
{
    public function send(string $mobile, string $message): void;
}
