<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\RentableInterface;
use App\Contracts\SerializableEntityInterface;
use InvalidArgumentException;

/**
 * Abstract Class MediaItem
 * Demonstrasi Encapsulation, Inheritance, dan Polymorphism pada katalog E-Book.
 */
abstract class MediaItem implements RentableInterface, SerializableEntityInterface
{
    private string $id;
    private string $title;
    private string $author;
    private string $category;
    private string $isbn;
    private int $publishYear;
    private int $pages;
    private float $dailyRate;
    private int $stock;
    private int $totalStock;
    private string $synopsis;
    private string $coverTheme;
    private string $fileFormat;
    private float $fileSizeMb;

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
        float $fileSizeMb = 14.5
    ) {
        $this->id = trim($id);
        $this->setTitle($title);
        $this->setAuthor($author);
        $this->category = trim($category);
        $this->isbn = trim($isbn);
        $this->publishYear = $publishYear;
        $this->pages = max(1, $pages);
        $this->setDailyRate($dailyRate);
        $this->totalStock = max(1, $totalStock);
        $this->setStock($stock);
        $this->synopsis = trim($synopsis);
        $this->coverTheme = $coverTheme;
        $this->fileFormat = $fileFormat;
        $this->fileSizeMb = max(0.1, $fileSizeMb);
    }

    // --- ENCAPSULATION: GETTERS & SETTERS DENGAN VALIDASI ---

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $cleaned = trim($title);
        if ($cleaned === '') {
            throw new InvalidArgumentException('Judul e-book tidak boleh kosong.');
        }
        $this->title = $cleaned;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $cleaned = trim($author);
        if ($cleaned === '') {
            throw new InvalidArgumentException('Nama penulis tidak boleh kosong.');
        }
        $this->author = $cleaned;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function getPublishYear(): int
    {
        return $this->publishYear;
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    public function getDailyRate(): float
    {
        return $this->dailyRate;
    }

    public function setDailyRate(float $rate): void
    {
        if ($rate < 0) {
            throw new InvalidArgumentException('Tarif sewa harian tidak boleh negatif.');
        }
        $this->dailyRate = $rate;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getTotalStock(): int
    {
        return $this->totalStock;
    }

    public function setStock(int $stock): void
    {
        if ($stock < 0) {
            throw new InvalidArgumentException('Stok lisensi e-book tidak boleh kurang dari 0.');
        }
        $this->stock = min($stock, $this->totalStock);
    }

    public function decrementStock(): bool
    {
        if ($this->stock <= 0) {
            return false;
        }
        $this->stock--;
        return true;
    }

    public function incrementStock(): void
    {
        if ($this->stock < $this->totalStock) {
            $this->stock++;
        }
    }

    public function isAvailable(): bool
    {
        return $this->stock > 0;
    }

    public function getSynopsis(): string
    {
        return $this->synopsis;
    }

    public function getCoverTheme(): string
    {
        return $this->coverTheme;
    }

    public function getFileFormat(): string
    {
        return $this->fileFormat;
    }

    public function getFileSizeMb(): float
    {
        return $this->fileSizeMb;
    }

    // --- POLYMORPHISM: ABSTRACT METHODS UNTUK SUBCLASS ---

    abstract public function getMediaTypeCode(): string;

    abstract public function getMediaTypeLabel(): string;

    abstract public function calculateBaseRentalCost(int $days): float;

    abstract public function getServiceFee(): float;

    abstract public function getTechnicalMetadata(): string;

    /**
     * Factory Method untuk menghidrasi array JSON menjadi instance subclass yang tepat.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $type = (string) ($data['media_type'] ?? 'standard');

        return match ($type) {
            'academic' => new AcademicMonograph(
                id: (string) $data['id'],
                title: (string) $data['title'],
                author: (string) $data['author'],
                category: (string) $data['category'],
                isbn: (string) $data['isbn'],
                publishYear: (int) $data['publish_year'],
                pages: (int) $data['pages'],
                dailyRate: (float) $data['daily_rate'],
                stock: (int) $data['stock'],
                totalStock: (int) ($data['total_stock'] ?? $data['stock']),
                synopsis: (string) $data['synopsis'],
                coverTheme: (string) ($data['cover_theme'] ?? 'forest'),
                fileFormat: (string) ($data['file_format'] ?? 'PDF/A + BibTeX'),
                fileSizeMb: (float) ($data['file_size_mb'] ?? 22.4),
                doiNumber: (string) ($data['doi_number'] ?? '10.1145/3580305.3599'),
                institutionName: (string) ($data['institution_name'] ?? 'MIT Press & IEEE Digital')
            ),
            'interactive' => new InteractiveMediaBook(
                id: (string) $data['id'],
                title: (string) $data['title'],
                author: (string) $data['author'],
                category: (string) $data['category'],
                isbn: (string) $data['isbn'],
                publishYear: (int) $data['publish_year'],
                pages: (int) $data['pages'],
                dailyRate: (float) $data['daily_rate'],
                stock: (int) $data['stock'],
                totalStock: (int) ($data['total_stock'] ?? $data['stock']),
                synopsis: (string) $data['synopsis'],
                coverTheme: (string) ($data['cover_theme'] ?? 'obsidian'),
                fileFormat: (string) ($data['file_format'] ?? 'EPUB3 + Audio + Sandbox'),
                fileSizeMb: (float) ($data['file_size_mb'] ?? 145.0),
                audioDurationMinutes: (int) ($data['audio_duration_minutes'] ?? 240),
                includesCodeSandbox: (bool) ($data['includes_code_sandbox'] ?? true)
            ),
            default => new StandardEBook(
                id: (string) $data['id'],
                title: (string) $data['title'],
                author: (string) $data['author'],
                category: (string) $data['category'],
                isbn: (string) $data['isbn'],
                publishYear: (int) $data['publish_year'],
                pages: (int) $data['pages'],
                dailyRate: (float) $data['daily_rate'],
                stock: (int) $data['stock'],
                totalStock: (int) ($data['total_stock'] ?? $data['stock']),
                synopsis: (string) $data['synopsis'],
                coverTheme: (string) ($data['cover_theme'] ?? 'terracotta'),
                fileFormat: (string) ($data['file_format'] ?? 'EPUB + PDF'),
                fileSizeMb: (float) ($data['file_size_mb'] ?? 12.8),
                drmProtection: (string) ($data['drm_protection'] ?? 'Adobe Readium LCP')
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'media_type' => $this->getMediaTypeCode(),
            'media_type_label' => $this->getMediaTypeLabel(),
            'title' => $this->title,
            'author' => $this->author,
            'category' => $this->category,
            'isbn' => $this->isbn,
            'publish_year' => $this->publishYear,
            'pages' => $this->pages,
            'daily_rate' => $this->dailyRate,
            'service_fee' => $this->getServiceFee(),
            'stock' => $this->stock,
            'total_stock' => $this->totalStock,
            'synopsis' => $this->synopsis,
            'cover_theme' => $this->coverTheme,
            'file_format' => $this->fileFormat,
            'file_size_mb' => $this->fileSizeMb,
            'technical_metadata' => $this->getTechnicalMetadata(),
        ];
    }
}
