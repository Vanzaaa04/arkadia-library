<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface RentableInterface
 * Kontrak polimorfik untuk setiap item media yang dapat disewa/dipinjam.
 */
interface RentableInterface
{
    public function getId(): string;

    public function getTitle(): string;

    public function getDailyRate(): float;

    public function isAvailable(): bool;

    /**
     * Menghitung biaya dasar sewa berdasarkan durasi hari (Polimorfisme).
     */
    public function calculateBaseRentalCost(int $days): float;

    /**
     * Biaya layanan/administrasi tambahan sesuai jenis media (Polimorfisme).
     */
    public function getServiceFee(): float;

    /**
     * Label tipe media spesifik.
     */
    public function getMediaTypeLabel(): string;
}
