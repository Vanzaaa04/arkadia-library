<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Class FacultyMember
 * Subclass dari User untuk Dosen / Peneliti Akademik (Diskon 30% + Durasi Pinjam 21 Hari).
 */
class FacultyMember extends User
{
    public function getTierCode(): string
    {
        return 'faculty';
    }

    public function getTierLabel(): string
    {
        return 'Academic Scholar';
    }

    public function getDiscountRate(): float
    {
        return 0.30;
    }

    public function getMaxBorrowDays(): int
    {
        return 21;
    }

    public function getMaxConcurrentLoans(): int
    {
        return 8;
    }

    public function waivesServiceFee(): bool
    {
        return false;
    }

    public function getPrivilegeSummary(): string
    {
        return 'Subsidi Riset 30% • Maks 21 hari sewa • Kuota 8 monograf aktif';
    }
}
