<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SerializableEntityInterface;
use InvalidArgumentException;

/**
 * Abstract Class User
 * Representasi entitas pengguna/member perpustakaan dengan Encapsulation, Inheritance, & Polymorphism.
 */
abstract class User implements SerializableEntityInterface
{
    private string $id;
    private string $memberCode;
    private string $name;
    private string $email;
    private string $institution;
    private string $joinedDate;
    private int $activeLoans;

    public function __construct(
        string $id,
        string $memberCode,
        string $name,
        string $email,
        string $institution,
        string $joinedDate,
        int $activeLoans = 0
    ) {
        $this->id = trim($id);
        $this->memberCode = trim($memberCode);
        $this->setName($name);
        $this->setEmail($email);
        $this->institution = trim($institution);
        $this->joinedDate = $joinedDate;
        $this->activeLoans = max(0, $activeLoans);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMemberCode(): string
    {
        return $this->memberCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $cleaned = trim($name);
        if (strlen($cleaned) < 3) {
            throw new InvalidArgumentException('Nama member minimal 3 karakter.');
        }
        $this->name = $cleaned;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $cleaned = trim($email);
        if (!filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Format alamat email tidak valid.');
        }
        $this->email = $cleaned;
    }

    public function getInstitution(): string
    {
        return $this->institution;
    }

    public function getJoinedDate(): string
    {
        return $this->joinedDate;
    }

    public function getActiveLoans(): int
    {
        return $this->activeLoans;
    }

    public function incrementActiveLoans(): void
    {
        $this->activeLoans++;
    }

    public function decrementActiveLoans(): void
    {
        if ($this->activeLoans > 0) {
            $this->activeLoans--;
        }
    }

    public function canBorrowMore(): bool
    {
        return $this->activeLoans < $this->getMaxConcurrentLoans();
    }

    // --- POLYMORPHIC ABSTRACT METHODS ---

    abstract public function getTierCode(): string;

    abstract public function getTierLabel(): string;

    abstract public function getDiscountRate(): float;

    abstract public function getMaxBorrowDays(): int;

    abstract public function getMaxConcurrentLoans(): int;

    abstract public function waivesServiceFee(): bool;

    abstract public function getPrivilegeSummary(): string;

    /**
     * Menghitung nominal diskon member berdasarkan biaya dasar sewa.
     */
    public function calculateDiscountAmount(float $baseCost): float
    {
        return round($baseCost * $this->getDiscountRate(), 2);
    }

    /**
     * Factory Method untuk menghidrasi array JSON menjadi instance subclass User yang sesuai.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $tier = (string) ($data['tier'] ?? 'regular');

        return match ($tier) {
            'premium' => new PremiumMember(
                id: (string) $data['id'],
                memberCode: (string) $data['member_code'],
                name: (string) $data['name'],
                email: (string) $data['email'],
                institution: (string) $data['institution'],
                joinedDate: (string) $data['joined_date'],
                activeLoans: (int) ($data['active_loans'] ?? 0)
            ),
            'faculty' => new FacultyMember(
                id: (string) $data['id'],
                memberCode: (string) $data['member_code'],
                name: (string) $data['name'],
                email: (string) $data['email'],
                institution: (string) $data['institution'],
                joinedDate: (string) $data['joined_date'],
                activeLoans: (int) ($data['active_loans'] ?? 0)
            ),
            default => new RegularMember(
                id: (string) $data['id'],
                memberCode: (string) $data['member_code'],
                name: (string) $data['name'],
                email: (string) $data['email'],
                institution: (string) $data['institution'],
                joinedDate: (string) $data['joined_date'],
                activeLoans: (int) ($data['active_loans'] ?? 0)
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
            'member_code' => $this->memberCode,
            'name' => $this->name,
            'email' => $this->email,
            'institution' => $this->institution,
            'joined_date' => $this->joinedDate,
            'active_loans' => $this->activeLoans,
            'tier' => $this->getTierCode(),
            'tier_label' => $this->getTierLabel(),
            'discount_rate' => $this->getDiscountRate(),
            'max_borrow_days' => $this->getMaxBorrowDays(),
            'max_concurrent_loans' => $this->getMaxConcurrentLoans(),
            'waives_service_fee' => $this->waivesServiceFee(),
            'privilege_summary' => $this->getPrivilegeSummary(),
        ];
    }
}
