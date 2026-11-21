<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SerializableEntityInterface;

/**
 * Class Transaction
 * Entitas transaksi penyewaan/peminjaman e-book yang menghubungkan User dan MediaItem.
 */
class Transaction implements SerializableEntityInterface
{
    private string $id;
    private string $transactionCode;
    private string $memberId;
    private string $memberName;
    private string $memberTier;
    private string $ebookId;
    private string $ebookTitle;
    private string $ebookType;
    private int $durationDays;
    private float $baseCost;
    private float $serviceFee;
    private float $discountAmount;
    private float $totalCost;
    private string $borrowDate;
    private string $dueDate;
    private ?string $returnDate;
    private string $status; // 'active' | 'returned'
    private string $accessToken;

    public function __construct(
        string $id,
        string $transactionCode,
        string $memberId,
        string $memberName,
        string $memberTier,
        string $ebookId,
        string $ebookTitle,
        string $ebookType,
        int $durationDays,
        float $baseCost,
        float $serviceFee,
        float $discountAmount,
        float $totalCost,
        string $borrowDate,
        string $dueDate,
        ?string $returnDate = null,
        string $status = 'active',
        string $accessToken = ''
    ) {
        $this->id = $id;
        $this->transactionCode = $transactionCode;
        $this->memberId = $memberId;
        $this->memberName = $memberName;
        $this->memberTier = $memberTier;
        $this->ebookId = $ebookId;
        $this->ebookTitle = $ebookTitle;
        $this->ebookType = $ebookType;
        $this->durationDays = max(1, $durationDays);
        $this->baseCost = $baseCost;
        $this->serviceFee = $serviceFee;
        $this->discountAmount = $discountAmount;
        $this->totalCost = $totalCost;
        $this->borrowDate = $borrowDate;
        $this->dueDate = $dueDate;
        $this->returnDate = $returnDate;
        $this->status = $status;
        $this->accessToken = $accessToken !== '' ? $accessToken : strtoupper(substr(md5($id . $transactionCode), 0, 10));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTransactionCode(): string
    {
        return $this->transactionCode;
    }

    public function getMemberId(): string
    {
        return $this->memberId;
    }

    public function getMemberName(): string
    {
        return $this->memberName;
    }

    public function getMemberTier(): string
    {
        return $this->memberTier;
    }

    public function getEbookId(): string
    {
        return $this->ebookId;
    }

    public function getEbookTitle(): string
    {
        return $this->ebookTitle;
    }

    public function getEbookType(): string
    {
        return $this->ebookType;
    }

    public function getDurationDays(): int
    {
        return $this->durationDays;
    }

    public function getBaseCost(): float
    {
        return $this->baseCost;
    }

    public function getServiceFee(): float
    {
        return $this->serviceFee;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function getTotalCost(): float
    {
        return $this->totalCost;
    }

    public function getBorrowDate(): string
    {
        return $this->borrowDate;
    }

    public function getDueDate(): string
    {
        return $this->dueDate;
    }

    public function getReturnDate(): ?string
    {
        return $this->returnDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function markAsReturned(string $returnDate): void
    {
        $this->status = 'returned';
        $this->returnDate = $returnDate;
    }

    /**
     * Memperpanjang masa peminjaman e-book dan memperbarui rincian biaya polimorfik.
     */
    public function extend(
        int $newDurationDays,
        string $newDueDate,
        float $newBaseCost,
        float $newServiceFee,
        float $newDiscountAmount,
        float $newTotalCost
    ): void {
        $this->durationDays = max(1, $newDurationDays);
        $this->dueDate = $newDueDate;
        $this->baseCost = $newBaseCost;
        $this->serviceFee = $newServiceFee;
        $this->discountAmount = $newDiscountAmount;
        $this->totalCost = $newTotalCost;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            transactionCode: (string) $data['transaction_code'],
            memberId: (string) $data['member_id'],
            memberName: (string) $data['member_name'],
            memberTier: (string) $data['member_tier'],
            ebookId: (string) $data['ebook_id'],
            ebookTitle: (string) $data['ebook_title'],
            ebookType: (string) $data['ebook_type'],
            durationDays: (int) $data['duration_days'],
            baseCost: (float) $data['base_cost'],
            serviceFee: (float) $data['service_fee'],
            discountAmount: (float) $data['discount_amount'],
            totalCost: (float) $data['total_cost'],
            borrowDate: (string) $data['borrow_date'],
            dueDate: (string) $data['due_date'],
            returnDate: isset($data['return_date']) && $data['return_date'] !== null ? (string) $data['return_date'] : null,
            status: (string) ($data['status'] ?? 'active'),
            accessToken: (string) ($data['access_token'] ?? '')
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'transaction_code' => $this->transactionCode,
            'member_id' => $this->memberId,
            'member_name' => $this->memberName,
            'member_tier' => $this->memberTier,
            'ebook_id' => $this->ebookId,
            'ebook_title' => $this->ebookTitle,
            'ebook_type' => $this->ebookType,
            'duration_days' => $this->durationDays,
            'base_cost' => $this->baseCost,
            'service_fee' => $this->serviceFee,
            'discount_amount' => $this->discountAmount,
            'total_cost' => $this->totalCost,
            'borrow_date' => $this->borrowDate,
            'due_date' => $this->dueDate,
            'return_date' => $this->returnDate,
            'status' => $this->status,
            'access_token' => $this->accessToken,
        ];
    }
}
