<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\MediaItem;
use DateTimeImmutable;

/**
 * Class ViewHelper
 * Kumpulan helper tampilan (escaping HTML, format mata uang/tanggal, dan komponen kartu buku).
 */
final class ViewHelper
{
    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public static function formatTanggal(?string $date): string
    {
        if ($date === null || $date === '') {
            return '-';
        }
        $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $ts = strtotime($date);
        if ($ts === false) {
            return $date;
        }
        return date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }

    public static function daysUntil(string $date): int
    {
        $today = new DateTimeImmutable('today');
        $target = new DateTimeImmutable($date);
        return (int) $today->diff($target)->format('%r%a');
    }

    public static function memberInitials(string $name): string
    {
        $clean = preg_replace('/^(Dr\.|Prof\.|Ir\.)\s+/i', '', trim($name)) ?? $name;
        $clean = preg_replace('/,.*$/', '', $clean) ?? $clean;
        $parts = preg_split('/\s+/', trim($clean)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials !== '' ? $initials : 'A';
    }

    public static function shortAuthor(string $author): string
    {
        $parts = preg_split('/\s*(?:&|,)\s*/', $author) ?: [$author];
        return trim((string) $parts[0]);
    }

    public static function firstName(string $name): string
    {
        $clean = preg_replace('/^(Dr\.|Prof\.|Ir\.)\s+/i', '', trim($name)) ?? $name;
        $clean = preg_replace('/,.*$/', '', $clean) ?? $clean;
        $parts = preg_split('/\s+/', trim($clean)) ?: [$clean];
        return (string) ($parts[0] ?? $clean);
    }

    public static function typeShort(string $code): string
    {
        return match ($code) {
            'academic' => 'Akademik',
            'interactive' => 'Interaktif',
            default => 'Umum',
        };
    }

    public static function navClass(string $tab, string $name): string
    {
        return $tab === $name ? 'nav-link is-active' : 'nav-link';
    }

    public static function renderCover(MediaItem $book, string $extraClass = ''): string
    {
        return sprintf(
            '<div class="cover cover--%s %s" aria-hidden="true">'
            . '<span class="cover__motif"></span><span class="cover__rule"></span>'
            . '<span class="cover__badge">%s</span>'
            . '<span class="cover__title">%s</span>'
            . '<span class="cover__author">%s</span>'
            . '</div>',
            self::e($book->getCoverTheme()),
            self::e($extraClass),
            self::e(self::typeShort($book->getMediaTypeCode())),
            self::e($book->getTitle()),
            self::e(self::shortAuthor($book->getAuthor()))
        );
    }

    public static function renderBookCard(MediaItem $book, int $order = 0): string
    {
        $available = $book->isAvailable();
        ob_start(); ?>
        <article class="book-card"
                 data-search="<?= self::e(strtolower($book->getTitle() . ' ' . $book->getAuthor() . ' ' . $book->getCategory() . ' ' . $book->getIsbn())) ?>"
                 data-category="<?= self::e($book->getCategory()) ?>"
                 data-type="<?= self::e($book->getMediaTypeCode()) ?>"
                 data-available="<?= $available ? '1' : '0' ?>"
                 data-rate="<?= (int) round($book->getDailyRate()) ?>"
                 data-pages="<?= $book->getPages() ?>"
                 data-order="<?= $order ?>">
            <button type="button" class="book-card__cover" data-open-book="<?= self::e($book->getId()) ?>" aria-label="Lihat detail <?= self::e($book->getTitle()) ?>">
                <?= self::renderCover($book) ?>
                <span class="stock-tag <?= $available ? '' : 'stock-tag--out' ?>">
                    <?= $available ? 'Sisa ' . $book->getStock() : 'Habis' ?>
                </span>
            </button>
            <div class="book-card__body">
                <span class="book-card__category"><?= self::e($book->getCategory()) ?></span>
                <h3 class="book-card__title">
                    <button type="button" data-open-book="<?= self::e($book->getId()) ?>"><?= self::e($book->getTitle()) ?></button>
                </h3>
                <p class="book-card__author"><?= self::e(self::shortAuthor($book->getAuthor())) ?> &middot; <?= $book->getPages() ?> hlm</p>
                <p class="book-card__synopsis"><?= self::e($book->getSynopsis()) ?></p>
                <div class="book-card__footer">
                    <p class="price"><strong><?= self::formatRupiah($book->getDailyRate()) ?></strong><span>/hari</span></p>
                    <button type="button" class="btn btn--primary btn--sm" data-rent-book="<?= self::e($book->getId()) ?>" <?= $available ? '' : 'disabled' ?>>
                        <?= $available ? 'Sewa' : 'Habis' ?>
                    </button>
                </div>
            </div>
        </article>
        <?php
        return (string) ob_get_clean();
    }
}
