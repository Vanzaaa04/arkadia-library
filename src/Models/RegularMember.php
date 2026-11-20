<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Class RegularMember
 * Subclass dari User untuk anggota pembaca reguler.
 */
class RegularMember extends User
{
    public function getTierCode(): string
    {
        return 'regular';
    }

    public function getTierLabel(): string
    {
        return 'Regular Reader';
    }

    public function getDiscountRate(): float
    {
        return 0.0;
    }

    public function getMaxBorrowDays(): int
    {
        return 7;
    }

    public function getMaxConcurrentLoans(): int
    {
        return 2;
    }

    public function waivesServiceFee(): bool
    {
        return false;
    }

    public function getPrivilegeSummary(): string
    {
        return 'Maks 7 hari sewa • Kuota 2 buku aktif • Tarif standar';
    }
}
