<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Class PremiumMember
 * Subclass dari User untuk anggota berlangganan VIP/Premium (Diskon 20% + Bebas Biaya Layanan).
 */
class PremiumMember extends User
{
    public function getTierCode(): string
    {
        return 'premium';
    }

    public function getTierLabel(): string
    {
        return 'Arkadia Fellow (VIP)';
    }

    public function getDiscountRate(): float
    {
        return 0.20;
    }

    public function getMaxBorrowDays(): int
    {
        return 14;
    }

    public function getMaxConcurrentLoans(): int
    {
        return 5;
    }

    public function waivesServiceFee(): bool
    {
        return true;
    }

    public function getPrivilegeSummary(): string
    {
        return 'Diskon 20% • Bebas Biaya Layanan • Maks 14 hari • Kuota 5 buku';
    }
}
