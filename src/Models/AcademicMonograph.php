<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Class AcademicMonograph
 * Subclass dari MediaItem untuk Monograf Riset & Jurnal Akademik (Inheritance & Polymorphism).
 */
class AcademicMonograph extends MediaItem
{
    private string $doiNumber;
    private string $institutionName;

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
        string $coverTheme = 'forest',
        string $fileFormat = 'PDF/A + BibTeX Citation',
        float $fileSizeMb = 24.5,
        string $doiNumber = '10.1145/3580305.3599',
        string $institutionName = 'MIT Press & IEEE Archive'
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
        $this->doiNumber = $doiNumber;
        $this->institutionName = $institutionName;
    }

    public function getDoiNumber(): string
    {
        return $this->doiNumber;
    }

    public function getInstitutionName(): string
    {
        return $this->institutionName;
    }

    public function getMediaTypeCode(): string
    {
        return 'academic';
    }

    public function getMediaTypeLabel(): string
    {
        return 'Academic Monograph';
    }

    /**
     * Polimorfisme: Monograf akademik memiliki lisensi riset (biaya indeksasi + tarif harian).
     */
    public function calculateBaseRentalCost(int $days): float
    {
        $validDays = max(1, $days);
        $citationAccessFee = 2500.0;
        return round(($this->getDailyRate() * $validDays) + $citationAccessFee, 2);
    }

    public function getServiceFee(): float
    {
        return 3000.0;
    }

    public function getTechnicalMetadata(): string
    {
        return sprintf('DOI: %s • Publisher: %s', $this->doiNumber, $this->institutionName);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'doi_number' => $this->doiNumber,
            'institution_name' => $this->institutionName,
        ]);
    }
}
