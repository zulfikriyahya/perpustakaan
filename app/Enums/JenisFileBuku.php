<?php

namespace App\Enums;

enum JenisFileBuku: string
{
    case Pdf = 'pdf';
    case Epub = 'epub';
    case AudioMp3 = 'audio_mp3';
    case AudioWav = 'audio_wav';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Epub => 'EPUB',
            self::AudioMp3 => 'Audio (MP3)',
            self::AudioWav => 'Audio (WAV)',
        };
    }

    public function isAudio(): bool
    {
        return in_array($this, [self::AudioMp3, self::AudioWav], true);
    }

    public function isEbook(): bool
    {
        return in_array($this, [self::Pdf, self::Epub], true);
    }
}
