<?php

namespace App\Services;

use App\Enums\TelegramDestination;

class TelegramDestinationConfig
{
    public function chatId(): int|string|null
    {
        $chatId = config('services.telegram.chat_id');

        if (! is_int($chatId) && ! is_string($chatId)) {
            return null;
        }

        $chatId = trim((string) $chatId);

        return $chatId === '' ? null : $chatId;
    }

    public function threadId(TelegramDestination $destination): ?int
    {
        $threadId = config("services.telegram.threads.{$destination->value}");
        $validatedThreadId = filter_var($threadId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($validatedThreadId === false) {
            return null;
        }

        return $validatedThreadId;
    }
}
