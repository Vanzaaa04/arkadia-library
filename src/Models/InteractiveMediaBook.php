<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Class InteractiveMediaBook
 * Subclass dari MediaItem untuk E-Book Interaktif dengan Narasi Audio & Code Sandbox.
 */
class InteractiveMediaBook extends MediaItem
{
    private int $audioDurationMinutes;
    private bool $includesCodeSandbox;

    public function __construct(
        string $id,
        string $title,
        string $author,
        string $category,
        string $isbn,
        int $publishYear,
        int $pages,
        float $dailyRate,
        int $stock,
        int $totalStock,
        string $synopsis,
        string $coverTheme = 'obsidian',
        string $fileFormat = 'EPUB3 + Lossless Audio + Lab',
        float $fileSizeMb = 165.0,
        int $audioDurationMinutes = 310,
        bool $includesCodeSandbox = true
    ) {
        parent::__construct(
            $id,
            $title,
            $author,
            $category,
            $isbn,
            $publishYear,
            $pages,
            $dailyRate,
            $stock,
            $totalStock,
            $synopsis,
            $coverTheme,
            $fileFormat,
            $fileSizeMb
        );
        $this->audioDurationMinutes = max(0, $audioDurationMinutes);
        $this->includesCodeSandbox = $includesCodeSandbox;
    }

    public function getAudioDurationMinutes(): int
    {
        return $this->audioDurationMinutes;
    }

    public function hasCodeSandbox(): bool
    {
        return $this->includesCodeSandbox;
    }

    public function getMediaTypeCode(): string
    {
        return 'interactive';
    }

    public function getMediaTypeLabel(): string
    {
        return 'Interactive Suite & Audio';
    }

    /**
     * Polimorfisme: Media interaktif menambahkan surcharge cloud sandbox Rp 1.000/hari.
     */
    public function calculateBaseRentalCost(int $days): float
    {
        $validDays = max(1, $days);
        $sandboxSurchargePerDay = $this->includesCodeSandbox ? 1000.0 : 0.0;
        return round(($this->getDailyRate() + $sandboxSurchargePerDay) * $validDays, 2);
    }

    public function getServiceFee(): float
    {
        return 4500.0;
    }

    public function getTechnicalMetadata(): string
    {
        $hours = floor($this->audioDurationMinutes / 60);
        $mins = $this->audioDurationMinutes % 60;
        $sandboxLabel = $this->includesCodeSandbox ? 'Cloud IDE Sandbox Aktif' : 'Audio Narration Only';
        return sprintf('Audio %dj %dm • %s', $hours, $mins, $sandboxLabel);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'audio_duration_minutes' => $this->audioDurationMinutes,
            'includes_code_sandbox' => $this->includesCodeSandbox,
        ]);
    }
}
