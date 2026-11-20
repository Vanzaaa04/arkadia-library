<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Class StandardEBook
 * Subclass dari MediaItem untuk E-Book Komersial/Umum (Inheritance & Polymorphism).
 */
class StandardEBook extends MediaItem
{
    private string $drmProtection;

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
        string $coverTheme = 'terracotta',
        string $fileFormat = 'EPUB + PDF',
        float $fileSizeMb = 12.8,
        string $drmProtection = 'Adobe Readium LCP'
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
        $this->drmProtection = $drmProtection;
    }

    public function getDrmProtection(): string
    {
        return $this->drmProtection;
    }

    public function getMediaTypeCode(): string
    {
        return 'standard';
    }

    public function getMediaTypeLabel(): string
    {
        return 'E-Book Umum';
    }

    /**
     * Polimorfisme: Tarif sewa standar (tarif harian * jumlah hari).
     * Diskon durasi 5% jika menyewa >= 7 hari.
     */
    public function calculateBaseRentalCost(int $days): float
    {
        $validDays = max(1, $days);
        $raw = $this->getDailyRate() * $validDays;
        return $validDays >= 7 ? round($raw * 0.95, 2) : round($raw, 2);
    }

    public function getServiceFee(): float
    {
        return 1500.0;
    }

    public function getTechnicalMetadata(): string
    {
        return sprintf('DRM: %s • %s (%.1f MB)', $this->drmProtection, $this->getFileFormat(), $this->getFileSizeMb());
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'drm_protection' => $this->drmProtection,
        ]);
    }
}
