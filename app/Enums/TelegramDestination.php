<?php

namespace App\Enums;

enum TelegramDestination: string
{
    case Absensi = 'absensi';
    case Tkj = 'tkj';
    case Tsm = 'tsm';
    case Dpb = 'dpb';
    case Mp = 'mp';

    public static function fromMajorCode(?string $majorCode): ?self
    {
        return match (mb_strtoupper(trim((string) $majorCode))) {
            'TKJ' => self::Tkj,
            'TSM' => self::Tsm,
            'DPB' => self::Dpb,
            'MP' => self::Mp,
            default => null,
        };
    }

    public function label(): string
    {
        return mb_strtoupper($this->value);
    }
}
