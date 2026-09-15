<?php

namespace App\Services;

use App\Exceptions\TelegramException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TelegramClient
{
    public function sendMessage(int|string $chatId, int $messageThreadId, string $text): void
    {
        $response = $this->send(function (PendingRequest $request) use ($chatId, $messageThreadId, $text): Response {
            return $request->asForm()->post($this->endpoint('sendMessage'), [
                'chat_id' => $chatId,
                'message_thread_id' => $messageThreadId,
                'text' => Str::limit($text, 4096, '…'),
            ]);
        });

        $this->ensureSuccessful($response);
    }

    public function sendPhoto(int|string $chatId, int $messageThreadId, string $caption, string $storagePath): void
    {
        try {
            $stream = Storage::disk('local')->readStream($storagePath);
        } catch (Throwable) {
            throw new TelegramException('Telegram photo could not be read.');
        }

        if ($stream === false) {
            throw new TelegramException('Telegram photo could not be read.');
        }

        try {
            $response = $this->send(function (PendingRequest $request) use ($caption, $chatId, $messageThreadId, $storagePath, $stream): Response {
                return $request
                    ->attach('photo', $stream, basename($storagePath))
                    ->post($this->endpoint('sendPhoto'), [
                        'chat_id' => $chatId,
                        'message_thread_id' => $messageThreadId,
                        'caption' => Str::limit($caption, 1024, '…'),
                    ]);
            });
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->ensureSuccessful($response);
    }

    public function sendDocument(int|string $chatId, int $messageThreadId, string $caption, string $storagePath): void
    {
        try {
            $stream = Storage::disk('local')->readStream($storagePath);
        } catch (Throwable) {
            throw new TelegramException('Telegram document could not be read.');
        }

        if ($stream === false) {
            throw new TelegramException('Telegram document could not be read.');
        }

        try {
            $response = $this->send(function (PendingRequest $request) use ($caption, $chatId, $messageThreadId, $storagePath, $stream): Response {
                return $request
                    ->attach('document', $stream, basename($storagePath))
                    ->post($this->endpoint('sendDocument'), [
                        'chat_id' => $chatId,
                        'message_thread_id' => $messageThreadId,
                        'caption' => Str::limit($caption, 1024, '…'),
                    ]);
            });
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->ensureSuccessful($response);
    }

    private function request(): PendingRequest
    {
        return Http::connectTimeout((int) config('services.telegram.connect_timeout', 3))
            ->timeout((int) config('services.telegram.timeout', 15));
    }

    private function endpoint(string $method): string
    {
        $token = config('services.telegram.bot_token');

        if (! is_string($token) || trim($token) === '') {
            throw new TelegramException('Telegram is not configured.');
        }

        return 'https://api.telegram.org/bot'.trim($token).'/'.$method;
    }

    /** @param callable(PendingRequest): Response $callback */
    private function send(callable $callback): Response
    {
        try {
            return $callback($this->request());
        } catch (TelegramException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new TelegramException('Telegram request failed.');
        }
    }

    private function ensureSuccessful(Response $response): void
    {
        if (! $response->successful() || $response->json('ok') !== true) {
            throw new TelegramException('Telegram request failed.');
        }
    }
}
