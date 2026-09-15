<?php

namespace App\Console\Commands;

use App\Enums\TelegramDestination;
use App\Exceptions\TelegramException;
use App\Services\TelegramClient;
use App\Services\TelegramDestinationConfig;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pkl:telegram-test {destination : absensi, tkj, tsm, dpb, atau mp}')]
#[Description('Send a safe E-PKL test message to a configured Telegram topic')]
class TelegramTestCommand extends Command
{
    public function handle(TelegramClient $telegram, TelegramDestinationConfig $destinationConfig): int
    {
        $destination = TelegramDestination::tryFrom(mb_strtolower(trim((string) $this->argument('destination'))));

        if ($destination === null) {
            $this->error('Tujuan tidak valid. Gunakan: absensi, tkj, tsm, dpb, atau mp.');

            return self::INVALID;
        }

        $chatId = $destinationConfig->chatId();
        $threadId = $destinationConfig->threadId($destination);

        if ($chatId === null || $threadId === null) {
            $this->error('Konfigurasi tujuan Telegram belum lengkap.');

            return self::FAILURE;
        }

        $message = implode("\n", [
            '✅ Tes Telegram E-PKL',
            'Topik: '.$destination->label(),
            'Waktu: '.now()->locale('id')->translatedFormat('d F Y H:i:s'),
        ]);

        try {
            $telegram->sendMessage($chatId, $threadId, $message);
        } catch (TelegramException) {
            $this->error('Pesan uji gagal dikirim. Periksa konfigurasi dan log antrean.');

            return self::FAILURE;
        }

        $this->info("Pesan uji berhasil dikirim ke topik {$destination->label()}.");

        return self::SUCCESS;
    }
}
