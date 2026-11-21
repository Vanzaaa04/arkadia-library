<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Services\LibraryManager;
use App\Services\OopInspectorService;
use App\Support\ViewHelper;
use DomainException;
use Throwable;

/**
 * Class RequestController
 * Mengatur endpoint API JSON, aksi form POST (PRG), dan penyusunan data untuk views/.
 */
class RequestController
{
    private const VALID_TABS = ['home', 'catalog', 'loans', 'members', 'manage', 'system'];

    private LibraryManager $manager;
    private AuthService $auth;
    private OopInspectorService $inspector;

    public function __construct(
        LibraryManager $manager,
        AuthService $auth,
        OopInspectorService $inspector
    ) {
        $this->manager = $manager;
        $this->auth = $auth;
        $this->inspector = $inspector;
    }

    /**
     * API JSON — dipanggil JavaScript (fetch) untuk menghitung biaya sewa
     * secara real-time menggunakan polimorfisme User x MediaItem di PHP.
     *
     * @param array<string, mixed> $query
     */
    public function handleApiRequest(string $endpoint, array $query): never
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($endpoint === 'simulate') {
                $memberId = (string) ($query['member_id'] ?? '');
                $ebookId = (string) ($query['ebook_id'] ?? '');
                $days = max(1, (int) ($query['days'] ?? 3));
                $quote = $this->manager->simulateRentalQuote($memberId, $ebookId, $days);
                echo json_encode(['ok' => true, 'quote' => $quote], JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Endpoint tidak dikenal.'], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Aksi Form (POST) — Autentikasi, Sewa, Kembalikan, Tambah Buku, Reset.
     * Menggunakan pola Post-Redirect-Get (PRG) dengan flash message di session.
     *
     * @param array<string, mixed> $post
     */
    public function handlePostRequest(array $post): never
    {
        $action = (string) ($post['action'] ?? '');
        $nextTab = (string) ($post['return_tab'] ?? 'home');
        $pendingBook = trim((string) ($post['pending_book'] ?? ''));
        $extraQuery = '';

        try {
            switch ($action) {
                case 'login_email':
                    $user = $this->auth->loginByEmail((string) ($post['email'] ?? ''));
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'title' => 'Berhasil masuk',
                        'message' => sprintf('Selamat datang kembali, %s (%s).', $user->getName(), $user->getTierLabel()),
                    ];
                    if ($pendingBook !== '') {
                        $extraQuery .= '&rent=' . urlencode($pendingBook);
                    }
                    break;

                case 'login_demo':
                    $user = $this->auth->loginById((string) ($post['member_id'] ?? ''));
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'title' => 'Masuk ke akun',
                        'message' => sprintf('Sekarang Anda masuk sebagai %s (%s).', $user->getName(), $user->getTierLabel()),
                    ];
                    if ($pendingBook !== '') {
                        $extraQuery .= '&rent=' . urlencode($pendingBook);
                    }
                    break;

                case 'auth_register':
                    $user = $this->auth->registerAndLogin($post);
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'title' => 'Akun berhasil dibuat',
                        'message' => sprintf('Akun %s (%s) siap digunakan untuk menyewa e-book.', $user->getName(), $user->getTierLabel()),
                    ];
                    if ($pendingBook !== '') {
                        $extraQuery .= '&rent=' . urlencode($pendingBook);
                    }
                    break;

                case 'logout':
                    $this->auth->logout();
                    $_SESSION['flash'] = [
                        'type' => 'info',
                        'title' => 'Keluar dari akun',
                        'message' => 'Anda telah keluar. Silakan masuk lagi atau pilih akun demo untuk menyewa.',
                    ];
                    $nextTab = 'home';
                    break;

                case 'rent_ebook':
                    $activeUser = $this->auth->getCurrentUser();
                    if ($activeUser === null) {
                        throw new DomainException('Silakan masuk atau daftar anggota terlebih dahulu sebelum menyewa e-book.');
                    }
                    $trx = $this->manager->createRentalTransaction(
                        $activeUser->getId(),
                        (string) ($post['ebook_id'] ?? ''),
                        (int) ($post['duration_days'] ?? 0)
                    );
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'title' => 'Sewa berhasil!',
                        'message' => sprintf(
                            '"%s" sekarang dipinjam %s selama %d hari. Total bayar %s.',
                            $trx->getEbookTitle(),
                            $trx->getMemberName(),
                            $trx->getDurationDays(),
                            ViewHelper::formatRupiah($trx->getTotalCost())
                        ),
                    ];
                    $nextTab = 'loans';
                    break;

                case 'return_ebook':
                    $trx = $this->manager->returnRentalTransaction((string) ($post['transaction_id'] ?? ''));
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'title' => 'Buku dikembalikan',
                        'message' => sprintf('"%s" sudah dikembalikan oleh %s. Stok buku bertambah lagi.', $trx->getEbookTitle(), $trx->getMemberName()),
                    ];
                    $nextTab = 'loans';
                    break;

                case 'add_ebook':
                    $ebook = $this->manager->addEbook($post);
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'title' => 'Buku baru ditambahkan',
                        'message' => sprintf('"%s" (%s) sudah masuk ke katalog.', $ebook->getTitle(), $ebook->getMediaTypeLabel()),
                    ];
                    $nextTab = 'catalog';
                    break;

                case 'register_member':
                    $member = $this->manager->registerMember($post);
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'title' => 'Anggota baru terdaftar',
                        'message' => sprintf('%s terdaftar sebagai %s (kode %s).', $member->getName(), $member->getTierLabel(), $member->getMemberCode()),
                    ];
                    $nextTab = 'members';
                    break;

                case 'reset_demo':
                    $this->auth->logout();
                    $this->manager->resetDemoData();
                    $_SESSION['flash'] = [
                        'type' => 'info',
                        'title' => 'Data dikembalikan',
                        'message' => 'Semua data buku, anggota, dan peminjaman kembali ke contoh awal.',
                    ];
                    $nextTab = 'home';
                    break;
            }
        } catch (Throwable $e) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'title' => 'Tidak bisa diproses',
                'message' => $e->getMessage(),
            ];
            if ($action === 'login_email') {
                $extraQuery .= '&auth=login';
            } elseif ($action === 'auth_register') {
                $extraQuery .= '&auth=register';
            }
            if ($pendingBook !== '' && in_array($action, ['login_email', 'auth_register', 'login_demo'], true)) {
                $extraQuery .= '&rent=' . urlencode($pendingBook);
            }
        }

        header('Location: index.php?tab=' . urlencode($nextTab) . $extraQuery);
        exit;
    }

    /**
     * Menyiapkan seluruh data yang dibutuhkan oleh template di folder views/.
     *
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function buildViewData(array $query): array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $tab = (string) ($query['tab'] ?? 'home');
        if (!in_array($tab, self::VALID_TABS, true)) {
            $tab = 'home';
        }

        $currentUser = $this->auth->getCurrentUser();
        $ebooks = $this->manager->getAllEbooks();
        $members = $this->manager->getAllMembers();
        $transactions = $this->manager->getAllTransactions();
        $categories = $this->manager->getDistinctCategories();
        $metrics = $this->manager->getDashboardMetrics();
        $oopReport = $this->inspector->inspectArchitecture();

        $activeLoans = array_values(array_filter($transactions, fn($t) => $t->isActive()));
        $historyLoans = array_values(array_filter($transactions, fn($t) => !$t->isActive()));
        $myActiveLoans = $currentUser !== null
            ? array_values(array_filter($activeLoans, fn($t) => $t->getMemberId() === $currentUser->getId()))
            : [];
        $myHistoryLoans = $currentUser !== null
            ? array_values(array_filter($historyLoans, fn($t) => $t->getMemberId() === $currentUser->getId()))
            : [];

        $featuredBooks = array_slice($ebooks, 0, 5);
        $heroBooks = array_slice($ebooks, 0, 3);

        // 1 contoh akun per jenis user untuk tombol demo 1-klik pada modal Masuk
        $demoAccounts = [];
        foreach (['regular', 'premium', 'faculty'] as $tierCode) {
            foreach ($members as $m) {
                if ($m->getTierCode() === $tierCode) {
                    $demoAccounts[] = $m;
                    break;
                }
            }
        }

        $tierSamples = [];
        foreach (['regular', 'premium', 'faculty'] as $i => $tierCode) {
            $tierSamples[] = User::fromArray([
                'id' => 'SAMPLE-' . $i,
                'member_code' => '-',
                'name' => 'Contoh Anggota',
                'email' => 'contoh@arkadia.id',
                'institution' => '-',
                'joined_date' => date('Y-m-d'),
                'tier' => $tierCode,
            ]);
        }

        $clientCatalog = array_map(fn($b) => $b->toArray(), $ebooks);
        $clientMembers = array_map(fn($m) => $m->toArray(), $members);
        $loanBadgeCount = $currentUser !== null ? count($myActiveLoans) : count($activeLoans);

        return [
            'manager' => $this->manager,
            'flash' => $flash,
            'tab' => $tab,
            'currentUser' => $currentUser,
            'ebooks' => $ebooks,
            'members' => $members,
            'transactions' => $transactions,
            'categories' => $categories,
            'metrics' => $metrics,
            'oopReport' => $oopReport,
            'activeLoans' => $activeLoans,
            'historyLoans' => $historyLoans,
            'myActiveLoans' => $myActiveLoans,
            'myHistoryLoans' => $myHistoryLoans,
            'featuredBooks' => $featuredBooks,
            'heroBooks' => $heroBooks,
            'demoAccounts' => $demoAccounts,
            'tierSamples' => $tierSamples,
            'clientCatalog' => $clientCatalog,
            'clientMembers' => $clientMembers,
            'loanBadgeCount' => $loanBadgeCount,
        ];
    }
}
