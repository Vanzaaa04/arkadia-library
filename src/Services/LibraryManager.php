<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MediaItem;
use App\Models\Transaction;
use App\Models\User;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

/**
 * Class LibraryManager
 * Service utama yang mengorkestrasi katalog MediaItem, manajemen User/Member,
 * serta simulasi dan eksekusi transaksi penyewaan e-book.
 */
class LibraryManager
{
    private JsonStorageService $storage;

    /** @var array<string, MediaItem> */
    private array $catalog = [];

    /** @var array<string, User> */
    private array $members = [];

    /** @var array<string, Transaction> */
    private array $transactions = [];

    public function __construct(?JsonStorageService $storage = null)
    {
        $this->storage = $storage ?? new JsonStorageService();
        $this->storage->ensureSeeded();
        $this->loadState();
    }

    private function loadState(): void
    {
        $this->catalog = [];
        foreach ($this->storage->readJson('ebooks.json') as $row) {
            $item = MediaItem::fromArray($row);
            $this->catalog[$item->getId()] = $item;
        }

        $this->members = [];
        foreach ($this->storage->readJson('members.json') as $row) {
            $member = User::fromArray($row);
            $this->members[$member->getId()] = $member;
        }

        $this->transactions = [];
        foreach ($this->storage->readJson('transactions.json') as $row) {
            $trx = Transaction::fromArray($row);
            $this->transactions[$trx->getId()] = $trx;
        }
    }

    private function persistAll(): void
    {
        $this->storage->writeJson('ebooks.json', array_values($this->catalog));
        $this->storage->writeJson('members.json', array_values($this->members));
        $this->storage->writeJson('transactions.json', array_values($this->transactions));
        $this->storage->exportCatalogToXml(array_values($this->catalog));
    }

    /**
     * @return array<int, MediaItem>
     */
    public function getAllEbooks(): array
    {
        return array_values($this->catalog);
    }

    public function getEbookById(string $id): ?MediaItem
    {
        return $this->catalog[$id] ?? null;
    }

    /**
     * @return array<int, User>
     */
    public function getAllMembers(): array
    {
        return array_values($this->members);
    }

    public function getMemberById(string $id): ?User
    {
        return $this->members[$id] ?? null;
    }

    public function getMemberByEmail(string $email): ?User
    {
        $needle = strtolower(trim($email));
        foreach ($this->members as $member) {
            if (strtolower($member->getEmail()) === $needle) {
                return $member;
            }
        }
        return null;
    }

    /**
     * @return array<int, Transaction>
     */
    public function getAllTransactions(): array
    {
        return array_values($this->transactions);
    }

    /**
     * @return array<int, string>
     */
    public function getDistinctCategories(): array
    {
        $categories = [];
        foreach ($this->catalog as $item) {
            $categories[$item->getCategory()] = true;
        }
        $list = array_keys($categories);
        sort($list);
        return $list;
    }

    /**
     * Simulasi perhitungan biaya sewa polimorfik antara User dan MediaItem.
     *
     * @return array<string, mixed>
     */
    public function simulateRentalQuote(string $memberId, string $ebookId, int $durationDays): array
    {
        $member = $this->getMemberById($memberId);
        if ($member === null) {
            throw new InvalidArgumentException('Member tidak ditemukan di dalam sistem.');
        }

        $ebook = $this->getEbookById($ebookId);
        if ($ebook === null) {
            throw new InvalidArgumentException('E-Book tidak ditemukan di dalam katalog.');
        }

        if ($durationDays < 1) {
            throw new InvalidArgumentException('Durasi sewa minimal adalah 1 hari.');
        }

        $maxDays = $member->getMaxBorrowDays();
        $isDurationValid = $durationDays <= $maxDays;

        // Polimorfisme MediaItem: calculateBaseRentalCost() & getServiceFee()
        $baseCost = $ebook->calculateBaseRentalCost($durationDays);
        $rawServiceFee = $ebook->getServiceFee();

        // Polimorfisme User: waivesServiceFee() & calculateDiscountAmount()
        $appliedServiceFee = $member->waivesServiceFee() ? 0.0 : $rawServiceFee;
        $discountAmount = $member->calculateDiscountAmount($baseCost);
        $totalCost = max(0.0, round($baseCost + $appliedServiceFee - $discountAmount, 2));

        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime("+{$durationDays} days"));

        return [
            'member_id' => $member->getId(),
            'member_name' => $member->getName(),
            'member_tier' => $member->getTierLabel(),
            'member_class' => get_class($member),
            'ebook_id' => $ebook->getId(),
            'ebook_title' => $ebook->getTitle(),
            'ebook_type' => $ebook->getMediaTypeLabel(),
            'ebook_class' => get_class($ebook),
            'duration_days' => $durationDays,
            'max_allowed_days' => $maxDays,
            'is_duration_valid' => $isDurationValid,
            'is_stock_available' => $ebook->isAvailable(),
            'is_quota_available' => $member->canBorrowMore(),
            'daily_rate' => $ebook->getDailyRate(),
            'base_cost' => $baseCost,
            'raw_service_fee' => $rawServiceFee,
            'applied_service_fee' => $appliedServiceFee,
            'discount_rate_percent' => (int) round($member->getDiscountRate() * 100),
            'discount_amount' => $discountAmount,
            'total_cost' => $totalCost,
            'borrow_date' => $borrowDate,
            'due_date' => $dueDate,
        ];
    }

    /**
     * Mengeksekusi transaksi penyewaan/peminjaman e-book baru.
     */
    public function createRentalTransaction(string $memberId, string $ebookId, int $durationDays): Transaction
    {
        $quote = $this->simulateRentalQuote($memberId, $ebookId, $durationDays);
        $member = $this->members[$memberId];
        $ebook = $this->catalog[$ebookId];

        if (!$ebook->isAvailable()) {
            throw new DomainException("Stok lisensi e-book \"{$ebook->getTitle()}\" sedang habis dipinjam.");
        }

        if (!$member->canBorrowMore()) {
            throw new DomainException(
                "Member {$member->getName()} ({$member->getTierLabel()}) telah mencapai batas kuota {$member->getMaxConcurrentLoans()} pinjaman aktif."
            );
        }

        if (!$quote['is_duration_valid']) {
            throw new DomainException(
                "Durasi {$durationDays} hari melebihi batas maksimal {$member->getMaxBorrowDays()} hari untuk tipe {$member->getTierLabel()}."
            );
        }

        $nextNum = 9000 + count($this->transactions) + 1;
        $trxId = 'TRX-' . $nextNum;
        $trxCode = sprintf('INV/%s/%04d', date('Y/m'), $nextNum - 8000);
        $accessToken = 'LCP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $transaction = new Transaction(
            id: $trxId,
            transactionCode: $trxCode,
            memberId: $member->getId(),
            memberName: $member->getName(),
            memberTier: $member->getTierLabel(),
            ebookId: $ebook->getId(),
            ebookTitle: $ebook->getTitle(),
            ebookType: $ebook->getMediaTypeLabel(),
            durationDays: $durationDays,
            baseCost: (float) $quote['base_cost'],
            serviceFee: (float) $quote['applied_service_fee'],
            discountAmount: (float) $quote['discount_amount'],
            totalCost: (float) $quote['total_cost'],
            borrowDate: (string) $quote['borrow_date'],
            dueDate: (string) $quote['due_date'],
            returnDate: null,
            status: 'active',
            accessToken: $accessToken
        );

        // Update state enkapsulasi objek
        $ebook->decrementStock();
        $member->incrementActiveLoans();

        // Prepend ke daftar transaksi terbaru
        $this->transactions = [$trxId => $transaction] + $this->transactions;
        $this->persistAll();

        return $transaction;
    }

    /**
     * Memproses pengembalian e-book dari transaksi aktif.
     */
    public function returnRentalTransaction(string $transactionId): Transaction
    {
        $trx = $this->transactions[$transactionId] ?? null;
        if ($trx === null) {
            throw new InvalidArgumentException('ID Transaksi tidak ditemukan.');
        }

        if (!$trx->isActive()) {
            throw new DomainException('Transaksi ini sudah dikembalikan sebelumnya.');
        }

        $trx->markAsReturned(date('Y-m-d'));

        $ebook = $this->getEbookById($trx->getEbookId());
        if ($ebook !== null) {
            $ebook->incrementStock();
        }

        $member = $this->getMemberById($trx->getMemberId());
        if ($member !== null) {
            $member->decrementActiveLoans();
        }

        $this->persistAll();
        return $trx;
    }

    /**
     * Memperpanjang masa sewa e-book secara polimorfik dengan validasi batas maksimal tier.
     */
    public function extendRentalTransaction(string $transactionId, int $extraDays = 3): Transaction
    {
        $trx = $this->transactions[$transactionId] ?? null;
        if ($trx === null) {
            throw new InvalidArgumentException('ID Transaksi tidak ditemukan.');
        }

        if (!$trx->isActive()) {
            throw new DomainException('Hanya peminjaman aktif yang dapat diperpanjang.');
        }

        if ($extraDays < 1) {
            throw new InvalidArgumentException('Tambahan durasi minimal adalah 1 hari.');
        }

        $member = $this->getMemberById($trx->getMemberId());
        $ebook = $this->getEbookById($trx->getEbookId());
        if ($member === null || $ebook === null) {
            throw new DomainException('Data anggota atau e-book tidak ditemukan.');
        }

        $newDays = $trx->getDurationDays() + $extraDays;
        if ($newDays > $member->getMaxBorrowDays()) {
            throw new DomainException(
                sprintf(
                    'Perpanjangan gagal: total durasi %d hari melebihi batas maksimal %d hari untuk %s.',
                    $newDays,
                    $member->getMaxBorrowDays(),
                    $member->getTierLabel()
                )
            );
        }

        // Hitung ulang tarif & diskon polimorfik untuk total durasi baru
        $newBaseCost = $ebook->calculateBaseRentalCost($newDays);
        $newServiceFee = $member->waivesServiceFee() ? 0.0 : $ebook->getServiceFee();
        $newDiscountAmount = $member->calculateDiscountAmount($newBaseCost);
        $newTotalCost = max(0.0, round($newBaseCost + $newServiceFee - $newDiscountAmount, 2));
        $newDueDate = (new DateTimeImmutable($trx->getBorrowDate()))
            ->modify("+{$newDays} days")
            ->format('Y-m-d');

        $trx->extend(
            $newDays,
            $newDueDate,
            $newBaseCost,
            $newServiceFee,
            $newDiscountAmount,
            $newTotalCost
        );

        $this->persistAll();
        return $trx;
    }

    /**
     * Menambahkan E-Book baru ke katalog berdasarkan tipe polimorfik.
     *
     * @param array<string, mixed> $input
     */
    public function addEbook(array $input): MediaItem
    {
        $nextNum = 100 + count($this->catalog) + 1;
        $id = 'EBK-' . $nextNum;
        $stock = max(1, (int) ($input['stock'] ?? 3));

        $payload = [
            'id' => $id,
            'media_type' => (string) ($input['media_type'] ?? 'standard'),
            'title' => trim((string) ($input['title'] ?? '')),
            'author' => trim((string) ($input['author'] ?? '')),
            'category' => trim((string) ($input['category'] ?? 'Web Engineering')),
            'isbn' => trim((string) ($input['isbn'] ?? '978-1-4920-' . random_int(1000, 9999) . '-2')),
            'publish_year' => (int) ($input['publish_year'] ?? (int) date('Y')),
            'pages' => max(10, (int) ($input['pages'] ?? 320)),
            'daily_rate' => max(1000.0, (float) ($input['daily_rate'] ?? 5000.0)),
            'stock' => $stock,
            'total_stock' => $stock,
            'synopsis' => trim((string) ($input['synopsis'] ?? 'Monograf digital dengan lisensi peminjaman terenkripsi.')),
            'cover_theme' => (string) ($input['cover_theme'] ?? 'terracotta'),
            'file_format' => (string) ($input['file_format'] ?? 'EPUB3 + PDF'),
            'file_size_mb' => max(1.0, (float) ($input['file_size_mb'] ?? 18.5)),
            'drm_protection' => (string) ($input['drm_protection'] ?? 'Readium LCP DRM'),
            'doi_number' => (string) ($input['doi_number'] ?? '10.1145/arkadia.' . $nextNum),
            'institution_name' => (string) ($input['institution_name'] ?? 'Arkadia University Press'),
            'audio_duration_minutes' => (int) ($input['audio_duration_minutes'] ?? 240),
            'includes_code_sandbox' => true,
        ];

        $ebook = MediaItem::fromArray($payload);
        $this->catalog = [$id => $ebook] + $this->catalog;
        $this->persistAll();

        return $ebook;
    }

    /**
     * Mendaftarkan member baru berdasarkan tier (RegularMember, PremiumMember, FacultyMember).
     *
     * @param array<string, mixed> $input
     */
    public function registerMember(array $input): User
    {
        $nextNum = count($this->members) + 1;
        $id = sprintf('MBR-%02d', $nextNum);
        $code = sprintf('ARK-%s-%03d', date('Y'), $nextNum);

        $payload = [
            'id' => $id,
            'member_code' => $code,
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'institution' => trim((string) ($input['institution'] ?? 'Program Studi Informatika — UMM')),
            'joined_date' => date('Y-m-d'),
            'active_loans' => 0,
            'tier' => (string) ($input['tier'] ?? 'regular'),
        ];

        foreach ($this->members as $existing) {
            if (strtolower($existing->getEmail()) === strtolower($payload['email'])) {
                throw new DomainException('Alamat email tersebut sudah terdaftar sebagai member.');
            }
        }

        $member = User::fromArray($payload);
        $this->members = [$id => $member] + $this->members;
        $this->persistAll();

        return $member;
    }

    /**
     * Mereset data ke kondisi awal untuk kemudahan demonstrasi/pengujian.
     */
    public function resetDemoData(): void
    {
        $this->storage->ensureSeeded(true);
        $this->loadState();
        $this->storage->exportCatalogToXml(array_values($this->catalog));
    }

    /**
     * Statistik ringkas untuk metrik dashboard.
     *
     * @return array<string, int|float>
     */
    public function getDashboardMetrics(): array
    {
        $totalTitles = count($this->catalog);
        $availableCopies = 0;
        $totalCopies = 0;

        foreach ($this->catalog as $item) {
            $availableCopies += $item->getStock();
            $totalCopies += $item->getTotalStock();
        }

        $activeTransactions = 0;
        $completedTransactions = 0;
        $totalRevenue = 0.0;

        foreach ($this->transactions as $trx) {
            $totalRevenue += $trx->getTotalCost();
            if ($trx->isActive()) {
                $activeTransactions++;
            } else {
                $completedTransactions++;
            }
        }

        return [
            'total_titles' => $totalTitles,
            'available_copies' => $availableCopies,
            'total_copies' => $totalCopies,
            'total_members' => count($this->members),
            'active_loans' => $activeTransactions,
            'completed_loans' => $completedTransactions,
            'total_revenue' => $totalRevenue,
        ];
    }
}
