<?php

namespace Tests\Fakes;

use App\Contracts\SmsSender;

class FakeSmsSender implements SmsSender
{
    /**
     * @var array<int, array{mobile: string, message: string}>
     */
    public array $sent = [];

    public function send(string $mobile, string $message): void
    {
        $this->sent[] = ['mobile' => $mobile, 'message' => $message];
    }

    /**
     * @return array<int, string>
     */
    public function messagesTo(string $mobile): array
    {
        $messages = [];

        foreach ($this->sent as $sms) {
            if ($sms['mobile'] === $mobile) {
                $messages[] = $sms['message'];
            }
        }

        return $messages;
    }
}
