<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSender
{
    public function __construct(protected ?string $channel = null)
    {
        //
    }

    public function send(string $mobile, string $message): void
    {
        Log::channel($this->channel)->info('SMS message (log driver)', [
            'mobile' => $mobile,
            'message' => $message,
        ]);
    }
}
