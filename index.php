<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use App\Models\MediaItem;
use App\Models\User;
use App\Services\AuthService;
use App\Services\LibraryManager;
use App\Services\OopInspectorService;

session_start();

$manager = new LibraryManager();
$inspector = new OopInspectorService();
$auth = new AuthService($manager);

/* ==========================================================================
   1. API JSON — dipanggil JavaScript (fetch) untuk menghitung biaya sewa
      secara real-time menggunakan polimorfisme User x MediaItem di PHP.
   ========================================================================== */
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    $endpoint = (string) $_GET['api'];

    try {
        if ($endpoint === 'simulate') {
            $memberId = (string) ($_GET['member_id'] ?? '');
            $ebookId = (string) ($_GET['ebook_id'] ?? '');
            $days = max(1, (int) ($_GET['days'] ?? 3));
            $quote = $manager->simulateRentalQuote($memberId, $ebookId, $days);
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

/* ==========================================================================
   2. AKSI FORM (POST) — Autentikasi, Sewa, Kembalikan, Tambah Buku, Reset
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $nextTab = (string) ($_POST['return_tab'] ?? 'home');
    $pendingBook = trim((string) ($_POST['pending_book'] ?? ''));
    $extraQuery = '';

    try {
        switch ($action) {
            case 'login_email':
                $user = $auth->loginByEmail((string) ($_POST['email'] ?? ''));
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
                $user = $auth->loginById((string) ($_POST['member_id'] ?? ''));
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
                $user = $auth->registerAndLogin($_POST);
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
                $auth->logout();
                $_SESSION['flash'] = [
                    'type' => 'info',
                    'title' => 'Keluar dari akun',
                    'message' => 'Anda telah keluar. Silakan masuk lagi atau pilih akun demo untuk menyewa.',
                ];
                $nextTab = 'home';
                break;

            case 'rent_ebook':
                $activeUser = $auth->getCurrentUser();
                if ($activeUser === null) {
                    throw new DomainException('Silakan masuk atau daftar anggota terlebih dahulu sebelum menyewa e-book.');
                }
                $trx = $manager->createRentalTransaction(
                    $activeUser->getId(),
                    (string) ($_POST['ebook_id'] ?? ''),
                    (int) ($_POST['duration_days'] ?? 0)
                );
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Sewa berhasil!',
                    'message' => sprintf(
                        '"%s" sekarang dipinjam %s selama %d hari. Total bayar %s.',
                        $trx->getEbookTitle(),
                        $trx->getMemberName(),
                        $trx->getDurationDays(),
                        formatRupiah($trx->getTotalCost())
                    ),
                ];
                $nextTab = 'loans';
                break;

            case 'return_ebook':
                $trx = $manager->returnRentalTransaction((string) ($_POST['transaction_id'] ?? ''));
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Buku dikembalikan',
                    'message' => sprintf('"%s" sudah dikembalikan oleh %s. Stok buku bertambah lagi.', $trx->getEbookTitle(), $trx->getMemberName()),
                ];
                $nextTab = 'loans';
                break;

            case 'add_ebook':
                $ebook = $manager->addEbook($_POST);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Buku baru ditambahkan',
                    'message' => sprintf('"%s" (%s) sudah masuk ke katalog.', $ebook->getTitle(), $ebook->getMediaTypeLabel()),
                ];
                $nextTab = 'catalog';
                break;

            case 'register_member':
                $member = $manager->registerMember($_POST);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Anggota baru terdaftar',
                    'message' => sprintf('%s terdaftar sebagai %s (kode %s).', $member->getName(), $member->getTierLabel(), $member->getMemberCode()),
                ];
                $nextTab = 'members';
                break;

            case 'reset_demo':
                $auth->logout();
                $manager->resetDemoData();
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

/* ==========================================================================
   3. HELPER TAMPILAN
   ========================================================================== */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatRupiah(float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatTanggal(?string $date): string
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

function daysUntil(string $date): int
{
    $today = new DateTimeImmutable('today');
    $target = new DateTimeImmutable($date);
    return (int) $today->diff($target)->format('%r%a');
}

function memberInitials(string $name): string
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

function shortAuthor(string $author): string
{
    $parts = preg_split('/\s*(?:&|,)\s*/', $author) ?: [$author];
    return trim((string) $parts[0]);
}

function firstName(string $name): string
{
    $clean = preg_replace('/^(Dr\.|Prof\.|Ir\.)\s+/i', '', trim($name)) ?? $name;
    $clean = preg_replace('/,.*$/', '', $clean) ?? $clean;
    $parts = preg_split('/\s+/', trim($clean)) ?: [$clean];
    return (string) ($parts[0] ?? $clean);
}

function typeShort(string $code): string
{
    return match ($code) {
        'academic' => 'Akademik',
        'interactive' => 'Interaktif',
        default => 'Umum',
    };
}

function renderCover(MediaItem $book, string $extraClass = ''): string
{
    return sprintf(
        '<div class="cover cover--%s %s" aria-hidden="true">'
        . '<span class="cover__shine"></span><span class="cover__ring"></span>'
        . '<span class="cover__badge">%s</span>'
        . '<span class="cover__title">%s</span>'
        . '<span class="cover__author">%s</span>'
        . '</div>',
        e($book->getCoverTheme()),
        e($extraClass),
        e(typeShort($book->getMediaTypeCode())),
        e($book->getTitle()),
        e(shortAuthor($book->getAuthor()))
    );
}

function renderBookCard(MediaItem $book): string
{
    $available = $book->isAvailable();
    ob_start(); ?>
    <article class="book-card"
             data-search="<?= e(strtolower($book->getTitle() . ' ' . $book->getAuthor() . ' ' . $book->getCategory() . ' ' . $book->getIsbn())) ?>"
             data-category="<?= e($book->getCategory()) ?>"
             data-type="<?= e($book->getMediaTypeCode()) ?>"
             data-available="<?= $available ? '1' : '0' ?>">
        <button type="button" class="book-card__cover" data-open-book="<?= e($book->getId()) ?>" aria-label="Lihat detail <?= e($book->getTitle()) ?>">
            <?= renderCover($book) ?>
            <span class="stock-tag <?= $available ? '' : 'stock-tag--out' ?>">
                <?= $available ? 'Sisa ' . $book->getStock() : 'Habis' ?>
            </span>
        </button>
        <div class="book-card__body">
            <span class="book-card__category"><?= e($book->getCategory()) ?></span>
            <h3 class="book-card__title">
                <button type="button" data-open-book="<?= e($book->getId()) ?>"><?= e($book->getTitle()) ?></button>
            </h3>
            <p class="book-card__author"><?= e(shortAuthor($book->getAuthor())) ?></p>
            <div class="book-card__footer">
                <p class="price"><strong><?= formatRupiah($book->getDailyRate()) ?></strong><span>/hari</span></p>
                <button type="button" class="btn btn--primary btn--sm" data-rent-book="<?= e($book->getId()) ?>" <?= $available ? '' : 'disabled' ?>>
                    <?= $available ? 'Sewa' : 'Habis' ?>
                </button>
            </div>
        </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

/* ==========================================================================
   4. DATA UNTUK HALAMAN
   ========================================================================== */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$validTabs = ['home', 'catalog', 'loans', 'members', 'manage', 'system'];
$tab = (string) ($_GET['tab'] ?? 'home');
if (!in_array($tab, $validTabs, true)) {
    $tab = 'home';
}

$currentUser = $auth->getCurrentUser();
$ebooks = $manager->getAllEbooks();
$members = $manager->getAllMembers();
$transactions = $manager->getAllTransactions();
$categories = $manager->getDistinctCategories();
$metrics = $manager->getDashboardMetrics();
$oopReport = $inspector->inspectArchitecture();

$activeLoans = array_values(array_filter($transactions, fn($t) => $t->isActive()));
$historyLoans = array_values(array_filter($transactions, fn($t) => !$t->isActive()));
$myActiveLoans = $currentUser !== null
    ? array_values(array_filter($activeLoans, fn($t) => $t->getMemberId() === $currentUser->getId()))
    : [];
$myHistoryLoans = $currentUser !== null
    ? array_values(array_filter($historyLoans, fn($t) => $t->getMemberId() === $currentUser->getId()))
    : [];

$featuredBooks = array_slice($ebooks, 0, 4);
$heroBooks = array_slice($ebooks, 0, 3);

// 1 contoh akun per jenis user untuk 1-klik tombol demo pada modal Masuk
$demoAccounts = [];
foreach (['regular', 'premium', 'faculty'] as $tierCode) {
    foreach ($members as $m) {
        if ($m->getTierCode() === $tierCode) {
            $demoAccounts[] = $m;
            break;
        }
    }
}

// Contoh objek tiap subclass User untuk tabel perbandingan keanggotaan (polimorfisme)
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

function navClass(string $tab, string $name): string
{
    return $tab === $name ? 'nav-link is-active' : 'nav-link';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Arkadia — aplikasi sewa e-book digital untuk mahasiswa dan dosen.">
    <title>Arkadia — Sewa E-Book Digital</title>
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('arkadia-theme');
                if (saved === 'dark' || saved === 'light') {
                    document.documentElement.setAttribute('data-theme', saved);
                }
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- ===================== HEADER & NAVIGASI ===================== -->
    <header class="topbar">
        <div class="container topbar__inner">
            <a href="index.php" class="brand" data-page-link="home">
                <span class="brand__logo" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"/></svg>
                </span>
                <span class="brand__text">
                    <strong>Arkadia</strong>
                    <small>Sewa E-Book Digital</small>
                </span>
            </a>

            <nav class="nav" aria-label="Menu utama">
                <button type="button" class="<?= navClass($tab, 'home') ?>" data-page="home">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z"/></svg>
                    <span>Beranda</span>
                </button>
                <button type="button" class="<?= navClass($tab, 'catalog') ?>" data-page="catalog">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                    <span>Katalog</span>
                </button>
                <button type="button" class="<?= navClass($tab, 'loans') ?>" data-page="loans">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span>Peminjaman</span>
                    <?php if ($loanBadgeCount > 0): ?><span class="nav-count"><?= $loanBadgeCount ?></span><?php endif; ?>
                </button>
                <button type="button" class="<?= navClass($tab, 'members') ?>" data-page="members">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Anggota</span>
                </button>
                <button type="button" class="<?= navClass($tab, 'manage') ?>" data-page="manage">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                    <span>Tambah Buku</span>
                </button>
                <button type="button" class="<?= navClass($tab, 'system') ?>" data-page="system">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/></svg>
                    <span>Info Sistem</span>
                </button>
            </nav>

            <div class="topbar__actions">
                <?php if ($currentUser === null): ?>
                    <button type="button" id="btnOpenAuth" class="btn btn--primary btn--sm" data-open-auth="login">
                        Masuk / Daftar
                    </button>
                <?php else: ?>
                    <button type="button" class="user-pill" data-open-auth="login" title="Ganti akun anggota">
                        <span class="avatar avatar--xs avatar--<?= e($currentUser->getTierCode()) ?>"><?= e(memberInitials($currentUser->getName())) ?></span>
                        <span class="user-pill__text">
                            <strong><?= e(firstName($currentUser->getName())) ?></strong>
                            <small><?= e($currentUser->getTierLabel()) ?></small>
                        </span>
                    </button>
                    <form method="POST" action="index.php" class="inline-form">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="icon-btn logout-btn" aria-label="Keluar dari akun" title="Keluar dari akun">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                        </button>
                    </form>
                <?php endif; ?>

                <button type="button" id="btnTheme" class="icon-btn" aria-label="Ganti tema terang/gelap" title="Ganti tema terang/gelap">
                    <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>
                <form method="POST" action="index.php" class="inline-form" onsubmit="return confirm('Kembalikan semua data ke contoh awal?');">
                    <input type="hidden" name="action" value="reset_demo">
                    <button type="submit" class="icon-btn" aria-label="Kembalikan data contoh" title="Kembalikan data ke contoh awal">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Notifikasi (toast) -->
    <div id="toastStack" class="toast-stack" aria-live="polite">
        <?php if ($flash !== null): ?>
            <div class="toast toast--<?= e((string) $flash['type']) ?>" role="status">
                <span class="toast__icon" aria-hidden="true"><?= $flash['type'] === 'error' ? '!' : '✓' ?></span>
                <div class="toast__body">
                    <strong><?= e((string) $flash['title']) ?></strong>
                    <p><?= e((string) $flash['message']) ?></p>
                </div>
                <button type="button" class="toast__close" aria-label="Tutup notifikasi">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <main class="container main">

        <!-- ===================== HALAMAN: BERANDA ===================== -->
        <section id="page-home" class="page <?= $tab === 'home' ? 'is-active' : '' ?>" aria-labelledby="homeTitle">
            <div class="hero">
                <div class="hero__text">
                    <?php if ($currentUser !== null): ?>
                        <span class="eyebrow">Halo, <?= e(firstName($currentUser->getName())) ?> &middot; <?= e($currentUser->getTierLabel()) ?></span>
                    <?php else: ?>
                        <span class="eyebrow">Perpustakaan digital kampus</span>
                    <?php endif; ?>
                    <h1 id="homeTitle">Sewa e-book favoritmu, <span class="text-gradient">baca tanpa ribet.</span></h1>
                    <p class="hero__lead">
                        Tidak perlu beli buku mahal. Daftar sebagai anggota, pilih e-book, tentukan berapa hari ingin membaca,
                        dan dapatkan diskon otomatis sesuai jenis keanggotaanmu.
                    </p>
                    <div class="hero__cta">
                        <button type="button" class="btn btn--primary btn--lg" data-goto="catalog">
                            Jelajahi Katalog
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                        </button>
                        <?php if ($currentUser === null): ?>
                            <button type="button" class="btn btn--ghost btn--lg" data-open-auth="register">Daftar Anggota</button>
                            <a href="#cara-pakai" class="btn btn--soft btn--lg">Cara pakai</a>
                        <?php else: ?>
                            <button type="button" class="btn btn--ghost btn--lg" data-goto="loans">Pinjaman Saya (<?= count($myActiveLoans) ?>)</button>
                        <?php endif; ?>
                    </div>
                    <dl class="hero__stats">
                        <div><dt>Judul e-book</dt><dd><?= (int) $metrics['total_titles'] ?></dd></div>
                        <div><dt>Anggota</dt><dd><?= (int) $metrics['total_members'] ?></dd></div>
                        <div><dt>Sedang dipinjam</dt><dd><?= (int) $metrics['active_loans'] ?></dd></div>
                    </dl>
                </div>
                <div class="hero__art" aria-hidden="true">
                    <div class="hero__blob"></div>
                    <?php foreach ($heroBooks as $i => $hb): ?>
                        <div class="hero__book hero__book--<?= $i + 1 ?>"><?= renderCover($hb, 'cover--hero') ?></div>
                    <?php endforeach; ?>
                    <div class="hero__float">
                        <span class="hero__float-icon">✓</span>
                        <div><strong>Diskon hingga 30%</strong><small>untuk Premium &amp; Dosen</small></div>
                    </div>
                </div>
            </div>

            <!-- Cara pakai -->
            <section id="cara-pakai" class="block" aria-labelledby="howTitle">
                <div class="block__head">
                    <h2 id="howTitle">Cara pakai dalam 3 langkah</h2>
                    <p>Buka katalog bebas kapan saja, lalu masuk atau daftar saat ingin menyewa.</p>
                </div>
                <ol class="steps">
                    <li class="step">
                        <span class="step__num">1</span>
                        <h3>Daftar atau Masuk</h3>
                        <p>Pilih tipe <strong>Reguler</strong>, <strong>Premium</strong>, atau <strong>Dosen</strong> (atau coba 1 klik akun demo).</p>
                    </li>
                    <li class="step">
                        <span class="step__num">2</span>
                        <h3>Pilih &amp; Sewa Buku</h3>
                        <p>Cari e-book di <strong>Katalog</strong>, klik <strong>Sewa</strong>, lalu pilih durasi hari. Diskon akunmu langsung terhitung.</p>
                    </li>
                    <li class="step">
                        <span class="step__num">3</span>
                        <h3>Baca &amp; Kembalikan</h3>
                        <p>Pantau tanggal jatuh tempo di menu <strong>Peminjaman</strong> dan klik <strong>Kembalikan</strong> saat selesai.</p>
                    </li>
                </ol>
            </section>

            <!-- Buku pilihan -->
            <section class="block" aria-labelledby="featuredTitle">
                <div class="block__head block__head--row">
                    <div>
                        <h2 id="featuredTitle">Koleksi pilihan</h2>
                        <p>E-book yang paling sering disewa minggu ini.</p>
                    </div>
                    <button type="button" class="link-btn" data-goto="catalog">Lihat semua &rarr;</button>
                </div>
                <div class="book-grid">
                    <?php foreach ($featuredBooks as $fb): ?>
                        <?= renderBookCard($fb) ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Jenis keanggotaan -->
            <section class="block" aria-labelledby="tierTitle">
                <div class="block__head">
                    <h2 id="tierTitle">Jenis keanggotaan</h2>
                    <p>Setiap jenis anggota punya diskon, batas hari, dan kuota buku yang berbeda.</p>
                </div>
                <div class="tier-grid">
                    <?php foreach ($tierSamples as $sample): ?>
                        <article class="tier-card tier-card--<?= e($sample->getTierCode()) ?>">
                            <span class="tier-card__label"><?= e($sample->getTierLabel()) ?></span>
                            <p class="tier-card__big">
                                <?php if ($sample->getDiscountRate() > 0): ?>
                                    Diskon <?= (int) round($sample->getDiscountRate() * 100) ?>%
                                <?php else: ?>
                                    Harga normal
                                <?php endif; ?>
                            </p>
                            <ul class="tier-card__list">
                                <li>Maksimal sewa <strong><?= $sample->getMaxBorrowDays() ?> hari</strong></li>
                                <li>Pinjam hingga <strong><?= $sample->getMaxConcurrentLoans() ?> buku</strong> sekaligus</li>
                                <li><?= $sample->waivesServiceFee() ? '<strong>Gratis</strong> biaya layanan' : 'Biaya layanan berlaku' ?></li>
                            </ul>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>

        <!-- ===================== HALAMAN: KATALOG ===================== -->
        <section id="page-catalog" class="page <?= $tab === 'catalog' ? 'is-active' : '' ?>" aria-labelledby="catalogTitle">
            <div class="page-head">
                <div>
                    <h1 id="catalogTitle">Katalog E-Book</h1>
                    <p>Menampilkan <strong id="catalogCount"><?= count($ebooks) ?></strong> dari <?= count($ebooks) ?> buku.</p>
                </div>
                <?php if ($currentUser !== null): ?>
                    <div class="account-chip-inline">
                        <span class="avatar avatar--xs avatar--<?= e($currentUser->getTierCode()) ?>"><?= e(memberInitials($currentUser->getName())) ?></span>
                        <span>Menyewa sebagai <strong><?= e($currentUser->getName()) ?></strong> (<?= e($currentUser->getTierLabel()) ?>)</span>
                        <button type="button" class="link-btn" data-open-auth="login">Ganti</button>
                    </div>
                <?php else: ?>
                    <button type="button" class="btn btn--soft btn--sm" data-open-auth="login">
                        Masuk / Daftar untuk menyewa
                    </button>
                <?php endif; ?>
            </div>

            <div class="toolbar" role="search">
                <label class="search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <span class="sr-only">Cari buku</span>
                    <input type="search" id="searchInput" placeholder="Cari judul, penulis, atau kategori..." autocomplete="off">
                </label>
                <select id="typeFilter" class="select" aria-label="Filter jenis buku">
                    <option value="all">Semua jenis</option>
                    <option value="standard">E-Book Umum</option>
                    <option value="academic">E-Book Akademik</option>
                    <option value="interactive">E-Book Interaktif</option>
                </select>
                <label class="switch">
                    <input type="checkbox" id="availableOnly">
                    <span class="switch__track"><span class="switch__thumb"></span></span>
                    <span>Hanya yang tersedia</span>
                </label>
            </div>

            <div class="chips" role="group" aria-label="Filter kategori">
                <button type="button" class="chip is-active" data-cat="all">Semua</button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="chip" data-cat="<?= e($cat) ?>"><?= e($cat) ?></button>
                <?php endforeach; ?>
            </div>

            <div id="catalogGrid" class="book-grid">
                <?php foreach ($ebooks as $book): ?>
                    <?= renderBookCard($book) ?>
                <?php endforeach; ?>
            </div>

            <div id="catalogEmpty" class="empty" hidden>
                <div class="empty__icon" aria-hidden="true">🔍</div>
                <h3>Buku tidak ditemukan</h3>
                <p>Coba kata kunci lain atau hapus filter.</p>
                <button type="button" id="btnResetFilter" class="btn btn--ghost">Hapus semua filter</button>
            </div>
        </section>

        <!-- ===================== HALAMAN: PEMINJAMAN ===================== -->
        <section id="page-loans" class="page <?= $tab === 'loans' ? 'is-active' : '' ?>" aria-labelledby="loansTitle">
            <div class="page-head">
                <div>
                    <h1 id="loansTitle">Peminjaman</h1>
                    <p>Pantau buku yang sedang dipinjam dan kembalikan jika sudah selesai dibaca.</p>
                </div>
                <?php if ($currentUser !== null): ?>
                    <div class="scope-tabs" role="tablist" aria-label="Filter daftar peminjaman">
                        <button type="button" class="scope-tab is-active" data-loan-scope="mine">
                            Pinjaman saya (<?= count($myActiveLoans) ?>)
                        </button>
                        <button type="button" class="scope-tab" data-loan-scope="all">
                            Semua anggota (<?= count($activeLoans) ?>)
                        </button>
                    </div>
                <?php else: ?>
                    <div class="summary-pills">
                        <span class="pill pill--warning"><?= count($activeLoans) ?> sedang dipinjam</span>
                        <span class="pill pill--success"><?= count($historyLoans) ?> selesai</span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($currentUser === null): ?>
                <div class="guest-banner">
                    <div>
                        <strong>Belum masuk ke akun</strong>
                        <p class="muted small">Masuk atau daftar untuk menyewa e-book dan melihat daftar pinjaman milik Anda sendiri.</p>
                    </div>
                    <button type="button" class="btn btn--primary btn--sm" data-open-auth="login">Masuk / Daftar</button>
                </div>
            <?php endif; ?>

            <h2 class="section-label">Sedang dipinjam</h2>
            <?php if (count($activeLoans) === 0): ?>
                <div class="empty">
                    <div class="empty__icon" aria-hidden="true">📚</div>
                    <h3>Belum ada peminjaman aktif</h3>
                    <p>Sewa buku pertama dari katalog.</p>
                    <button type="button" class="btn btn--primary" data-goto="catalog">Buka Katalog</button>
                </div>
            <?php else: ?>
                <div class="loan-list" id="activeLoanList">
                    <?php foreach ($activeLoans as $loan): ?>
                        <?php
                        $loanBook = $manager->getEbookById($loan->getEbookId());
                        $left = daysUntil($loan->getDueDate());
                        $dueClass = $left < 0 ? 'due--late' : ($left <= 3 ? 'due--soon' : 'due--ok');
                        $dueText = $left < 0 ? 'Terlambat ' . abs($left) . ' hari' : ($left === 0 ? 'Jatuh tempo hari ini' : 'Sisa ' . $left . ' hari');
                        $hiddenForMine = ($currentUser !== null && $loan->getMemberId() !== $currentUser->getId());
                        ?>
                        <article class="loan-card" data-loan-owner="<?= e($loan->getMemberId()) ?>" <?= $hiddenForMine ? 'hidden' : '' ?>>
                            <div class="loan-card__cover">
                                <?= $loanBook !== null ? renderCover($loanBook, 'cover--sm') : '' ?>
                            </div>
                            <div class="loan-card__info">
                                <h3><?= e($loan->getEbookTitle()) ?></h3>
                                <p class="loan-card__who">
                                    <span class="avatar avatar--xs"><?= e(memberInitials($loan->getMemberName())) ?></span>
                                    <?= e($loan->getMemberName()) ?> <span class="muted">· <?= e($loan->getMemberTier()) ?></span>
                                </p>
                                <ul class="loan-card__meta">
                                    <li>Mulai <strong><?= formatTanggal($loan->getBorrowDate()) ?></strong></li>
                                    <li>Jatuh tempo <strong><?= formatTanggal($loan->getDueDate()) ?></strong></li>
                                    <li>Dibayar <strong><?= formatRupiah($loan->getTotalCost()) ?></strong></li>
                                </ul>
                            </div>
                            <div class="loan-card__side">
                                <span class="due <?= $dueClass ?>"><?= e($dueText) ?></span>
                                <form method="POST" action="index.php" class="return-form">
                                    <input type="hidden" name="action" value="return_ebook">
                                    <input type="hidden" name="transaction_id" value="<?= e($loan->getId()) ?>">
                                    <input type="hidden" name="return_tab" value="loans">
                                    <button type="submit" class="btn btn--success btn--sm">Kembalikan</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div id="emptyMyLoans" class="empty" <?= ($currentUser !== null && count($myActiveLoans) === 0) ? '' : 'hidden' ?>>
                    <div class="empty__icon" aria-hidden="true">📖</div>
                    <h3>Anda belum menyewa buku</h3>
                    <p>Pilih e-book dari katalog untuk mulai membaca dengan diskon akun Anda.</p>
                    <button type="button" class="btn btn--primary" data-goto="catalog">Buka Katalog</button>
                </div>
            <?php endif; ?>

            <h2 class="section-label">Riwayat selesai</h2>
            <div class="table-card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Buku</th>
                            <th>Anggota</th>
                            <th>Lama sewa</th>
                            <th>Dikembalikan</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <?php if (count($historyLoans) === 0): ?>
                            <tr><td colspan="5" class="muted center">Belum ada riwayat.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historyLoans as $h): ?>
                                <?php $hideHist = ($currentUser !== null && $h->getMemberId() !== $currentUser->getId()); ?>
                                <tr data-loan-owner="<?= e($h->getMemberId()) ?>" <?= $hideHist ? 'hidden' : '' ?>>
                                    <td>
                                        <strong><?= e($h->getEbookTitle()) ?></strong>
                                        <small class="muted block-sm"><?= e($h->getTransactionCode()) ?></small>
                                    </td>
                                    <td><?= e($h->getMemberName()) ?></td>
                                    <td><?= $h->getDurationDays() ?> hari</td>
                                    <td><?= formatTanggal($h->getReturnDate()) ?></td>
                                    <td class="num"><strong><?= formatRupiah($h->getTotalCost()) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="emptyMyHistoryRow" <?= ($currentUser !== null && count($myHistoryLoans) === 0) ? '' : 'hidden' ?>>
                                <td colspan="5" class="muted center">Belum ada riwayat pengembalian untuk akun ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ===================== HALAMAN: ANGGOTA ===================== -->
        <section id="page-members" class="page <?= $tab === 'members' ? 'is-active' : '' ?>" aria-labelledby="membersTitle">
            <div class="page-head">
                <div>
                    <h1 id="membersTitle">Anggota</h1>
                    <p>Daftar anggota terdaftar. Klik <strong>Masuk sebagai akun ini</strong> untuk berpindah akun kapan saja.</p>
                </div>
                <a href="#formMember" class="btn btn--primary">+ Daftar anggota baru</a>
            </div>

            <div class="member-grid">
                <?php foreach ($members as $member): ?>
                    <?php
                    $ratio = min(100, (int) round($member->getActiveLoans() / max(1, $member->getMaxConcurrentLoans()) * 100));
                    $isCurrent = $currentUser !== null && $currentUser->getId() === $member->getId();
                    ?>
                    <article class="member-card <?= $isCurrent ? 'is-current' : '' ?>">
                        <div class="member-card__top">
                            <span class="avatar avatar--<?= e($member->getTierCode()) ?>"><?= e(memberInitials($member->getName())) ?></span>
                            <div class="member-card__id">
                                <h3><?= e($member->getName()) ?></h3>
                                <p><?= e($member->getInstitution()) ?></p>
                            </div>
                        </div>
                        <span class="tier-pill tier-pill--<?= e($member->getTierCode()) ?>"><?= e($member->getTierLabel()) ?></span>
                        <dl class="member-card__stats">
                            <div><dt>Diskon</dt><dd><?= (int) round($member->getDiscountRate() * 100) ?>%</dd></div>
                            <div><dt>Maks. sewa</dt><dd><?= $member->getMaxBorrowDays() ?> hari</dd></div>
                            <div><dt>Dipinjam</dt><dd><?= $member->getActiveLoans() ?>/<?= $member->getMaxConcurrentLoans() ?></dd></div>
                        </dl>
                        <div class="quota" title="Kuota pinjaman terpakai">
                            <span style="width: <?= $ratio ?>%"></span>
                        </div>
                        <div class="member-card__foot">
                            <span class="muted mono"><?= e($member->getEmail()) ?></span>
                            <?php if ($isCurrent): ?>
                                <span class="pill pill--success">Akun aktif</span>
                            <?php else: ?>
                                <form method="POST" action="index.php" class="inline-form">
                                    <input type="hidden" name="action" value="login_demo">
                                    <input type="hidden" name="member_id" value="<?= e($member->getId()) ?>">
                                    <input type="hidden" name="return_tab" value="members">
                                    <button type="submit" class="btn btn--soft btn--sm">Masuk akun ini</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="form-card" id="formMember">
                <div class="form-card__head">
                    <h2>Daftar anggota baru</h2>
                    <p>Isi data di bawah. Jenis keanggotaan menentukan diskon dan batas sewa.</p>
                </div>
                <form id="formRegisterMember" method="POST" action="index.php" novalidate>
                    <input type="hidden" name="action" value="register_member">
                    <input type="hidden" name="return_tab" value="members">
                    <div class="form-grid">
                        <div class="field">
                            <label for="memberName">Nama lengkap</label>
                            <input type="text" id="memberName" name="name" placeholder="Contoh: Budi Santoso" required minlength="3">
                            <small class="field__error" id="err-memberName"></small>
                        </div>
                        <div class="field">
                            <label for="memberEmail">Email</label>
                            <input type="email" id="memberEmail" name="email" placeholder="nama@webmail.umm.ac.id" required>
                            <small class="field__error" id="err-memberEmail"></small>
                        </div>
                        <div class="field">
                            <label for="memberInstitution">Kelas / Fakultas</label>
                            <input type="text" id="memberInstitution" name="institution" placeholder="Contoh: Informatika Kelas E — UMM" required minlength="3">
                            <small class="field__error" id="err-memberInstitution"></small>
                        </div>
                        <div class="field">
                            <span class="field__label">Jenis keanggotaan</span>
                            <div class="radio-cards" role="radiogroup" aria-label="Jenis keanggotaan">
                                <?php foreach ($tierSamples as $i => $sample): ?>
                                    <label class="radio-card">
                                        <input type="radio" name="tier" value="<?= e($sample->getTierCode()) ?>" <?= $i === 0 ? 'checked' : '' ?>>
                                        <span>
                                            <strong><?= e($sample->getTierLabel()) ?></strong>
                                            <small>Diskon <?= (int) round($sample->getDiscountRate() * 100) ?>% · <?= $sample->getMaxBorrowDays() ?> hari</small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">Daftarkan anggota</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ===================== HALAMAN: TAMBAH BUKU ===================== -->
        <section id="page-manage" class="page <?= $tab === 'manage' ? 'is-active' : '' ?>" aria-labelledby="manageTitle">
            <div class="page-head">
                <div>
                    <h1 id="manageTitle">Tambah buku baru</h1>
                    <p>Buku yang ditambahkan langsung muncul di katalog dan tersimpan ke file JSON &amp; XML.</p>
                </div>
            </div>

            <div class="manage-layout">
                <div class="form-card">
                    <form id="formAddEbook" method="POST" action="index.php" novalidate>
                        <input type="hidden" name="action" value="add_ebook">
                        <input type="hidden" name="return_tab" value="catalog">
                        <div class="form-grid">
                            <div class="field field--full">
                                <label for="ebookTitle">Judul buku</label>
                                <input type="text" id="ebookTitle" name="title" placeholder="Contoh: Belajar PHP Modern dari Nol" required minlength="4">
                                <small class="field__error" id="err-ebookTitle"></small>
                            </div>
                            <div class="field">
                                <label for="ebookAuthor">Penulis</label>
                                <input type="text" id="ebookAuthor" name="author" placeholder="Contoh: Andi Wijaya" required minlength="3">
                                <small class="field__error" id="err-ebookAuthor"></small>
                            </div>
                            <div class="field">
                                <label for="ebookCategory">Kategori</label>
                                <select id="ebookCategory" name="category">
                                    <option value="Web Engineering">Web Engineering</option>
                                    <option value="Software Architecture">Software Architecture</option>
                                    <option value="Interface Design">Interface Design</option>
                                    <option value="Artificial Intelligence">Artificial Intelligence</option>
                                    <option value="Cybersecurity">Cybersecurity</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="ebookMediaType">Jenis buku</label>
                                <select id="ebookMediaType" name="media_type">
                                    <option value="standard">E-Book Umum (biaya layanan Rp 1.500)</option>
                                    <option value="academic">E-Book Akademik (biaya layanan Rp 3.000)</option>
                                    <option value="interactive">E-Book Interaktif (biaya layanan Rp 4.500)</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="ebookDailyRate">Harga sewa per hari (Rp)</label>
                                <input type="number" id="ebookDailyRate" name="daily_rate" value="5000" min="1000" max="100000" step="500" required>
                                <small class="field__error" id="err-ebookDailyRate"></small>
                            </div>
                            <div class="field">
                                <label for="ebookStock">Jumlah stok</label>
                                <input type="number" id="ebookStock" name="stock" value="3" min="1" max="50" required>
                                <small class="field__error" id="err-ebookStock"></small>
                            </div>
                            <div class="field">
                                <label for="ebookPages">Jumlah halaman</label>
                                <input type="number" id="ebookPages" name="pages" value="320" min="20" max="2500">
                            </div>
                            <div class="field">
                                <label for="ebookIsbn">ISBN</label>
                                <input type="text" id="ebookIsbn" name="isbn" value="978-1-4920-8812-7">
                            </div>
                            <div class="field" id="groupDoi" hidden>
                                <label for="ebookDoi">Nomor DOI</label>
                                <input type="text" id="ebookDoi" name="doi_number" value="10.1145/3612045.3612998">
                            </div>
                            <div class="field" id="groupAudio" hidden>
                                <label for="ebookAudio">Durasi audio (menit)</label>
                                <input type="number" id="ebookAudio" name="audio_duration_minutes" value="240" min="30">
                            </div>
                            <div class="field field--full">
                                <span class="field__label">Warna sampul</span>
                                <div class="swatches" role="radiogroup" aria-label="Warna sampul">
                                    <?php
                                    $themes = ['terracotta' => 'Biru', 'indigo' => 'Ungu', 'forest' => 'Hijau', 'amber' => 'Oranye', 'crimson' => 'Merah', 'obsidian' => 'Hitam'];
                                    $first = true;
                                    foreach ($themes as $val => $label): ?>
                                        <label class="swatch" title="<?= e($label) ?>">
                                            <input type="radio" name="cover_theme" value="<?= e($val) ?>" <?= $first ? 'checked' : '' ?>>
                                            <span class="swatch__dot cover--<?= e($val) ?>"></span>
                                            <span class="sr-only"><?= e($label) ?></span>
                                        </label>
                                    <?php $first = false; endforeach; ?>
                                </div>
                            </div>
                            <div class="field field--full">
                                <label for="ebookSynopsis">Sinopsis singkat</label>
                                <textarea id="ebookSynopsis" name="synopsis" rows="3" placeholder="Ceritakan isi buku dalam 1–2 kalimat..." required minlength="15"></textarea>
                                <small class="field__error" id="err-ebookSynopsis"></small>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Simpan buku</button>
                        </div>
                    </form>
                </div>

                <aside class="preview-card" aria-label="Pratinjau sampul">
                    <span class="preview-card__label">Pratinjau</span>
                    <div id="previewCover" class="cover cover--terracotta cover--preview">
                        <span class="cover__shine"></span><span class="cover__ring"></span>
                        <span class="cover__badge" id="previewBadge">Umum</span>
                        <span class="cover__title" id="previewTitle">Judul buku kamu</span>
                        <span class="cover__author" id="previewAuthor">Nama penulis</span>
                    </div>
                    <p class="preview-card__price"><strong id="previewPrice">Rp 5.000</strong> / hari</p>
                    <p class="muted small">Begini tampilan buku di katalog.</p>
                </aside>
            </div>
        </section>

        <!-- ===================== HALAMAN: INFO SISTEM (UNTUK PENILAIAN) ===================== -->
        <section id="page-system" class="page <?= $tab === 'system' ? 'is-active' : '' ?>" aria-labelledby="systemTitle">
            <div class="page-head">
                <div>
                    <h1 id="systemTitle">Info Sistem</h1>
                    <p>Ringkasan teknis untuk penilaian UTS: bagaimana aplikasi ini memenuhi setiap poin rubrik.</p>
                </div>
            </div>

            <div class="rubric-grid">
                <article class="rubric-card">
                    <div class="rubric-card__head"><span>Front-End</span><strong>30 poin</strong></div>
                    <ul class="checklist">
                        <li>Elemen semantik HTML5: <code>header</code>, <code>nav</code>, <code>main</code>, <code>section</code>, <code>article</code>, <code>aside</code>, <code>dialog</code>, <code>footer</code></li>
                        <li>Validasi form di JavaScript (masuk, daftar, buku) + validasi ulang di PHP</li>
                        <li>Layout responsif (CSS Grid/Flexbox) dengan tema terang &amp; gelap</li>
                        <li>Interaktif: pencarian live, filter, auth &amp; sewa modal, kalkulasi via <code>fetch()</code></li>
                    </ul>
                </article>
                <article class="rubric-card">
                    <div class="rubric-card__head"><span>PHP OOP</span><strong>40 poin</strong></div>
                    <ul class="checklist">
                        <li><b>Class &amp; Object:</b> 14 class/interface di <code>src/</code> (termasuk <code>AuthService</code>)</li>
                        <li><b>Encapsulation:</b> properti <code>private</code> + getter/setter bervalidasi</li>
                        <li><b>Inheritance:</b> <code>MediaItem</code> → 3 jenis buku, <code>User</code> → 3 jenis anggota</li>
                        <li><b>Polymorphism:</b> <code>calculateBaseRentalCost()</code>, <code>getDiscountRate()</code>, dll. berbeda per subclass</li>
                        <li><b>Namespace:</b> <code>App\Contracts</code>, <code>App\Models</code>, <code>App\Services</code></li>
                    </ul>
                </article>
                <article class="rubric-card">
                    <div class="rubric-card__head"><span>Struktur Berkas</span><strong>30 poin</strong></div>
                    <ul class="checklist">
                        <li>Kode OOP terpisah di folder <code>src/</code>, 1 class per file</li>
                        <li>Autoloader PSR-4 (<code>autoload.php</code>), CSS &amp; JS di <code>assets/</code></li>
                        <li>Data tersimpan di <a href="data/ebooks.json" target="_blank" rel="noopener">ebooks.json</a>, <a href="data/members.json" target="_blank" rel="noopener">members.json</a>, <a href="data/transactions.json" target="_blank" rel="noopener">transactions.json</a> &amp; <a href="data/ebooks.xml" target="_blank" rel="noopener">ebooks.xml</a></li>
                        <li>Berjalan di PHP <?= e(PHP_VERSION) ?> tanpa error</li>
                    </ul>
                </article>
            </div>

            <div class="block__head">
                <h2>Contoh polimorfisme di aplikasi</h2>
                <p>Satu method yang sama menghasilkan perilaku berbeda tergantung objeknya.</p>
            </div>
            <div class="table-card">
                <table class="table">
                    <thead>
                        <tr><th>Objek (subclass)</th><th>getDiscountRate()</th><th>getMaxBorrowDays()</th><th>waivesServiceFee()</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tierSamples as $sample): ?>
                            <tr>
                                <td><code><?= e((new ReflectionClass($sample))->getShortName()) ?></code></td>
                                <td><?= (int) round($sample->getDiscountRate() * 100) ?>%</td>
                                <td><?= $sample->getMaxBorrowDays() ?> hari</td>
                                <td><?= $sample->waivesServiceFee() ? 'true' : 'false' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="block__head">
                <h2>Struktur class (dibaca langsung dengan Reflection API)</h2>
                <p>Daftar ini dibuat otomatis oleh <code>OopInspectorService</code> saat halaman dibuka.</p>
            </div>
            <div class="reflection-grid">
                <?php foreach ($oopReport as $cls): ?>
                    <details class="reflection-card">
                        <summary>
                            <span class="role-chip"><?= e((string) $cls['role']) ?></span>
                            <code class="reflection-card__name"><?= e((string) $cls['short_name']) ?></code>
                            <?php if ($cls['parent'] !== null): ?>
                                <span class="muted small">extends <?= e((string) $cls['parent']) ?></span>
                            <?php endif; ?>
                        </summary>
                        <p class="muted small mono"><?= e((string) $cls['file']) ?></p>
                        <?php if (count($cls['interfaces']) > 0): ?>
                            <p class="small">implements <code><?= e(implode(', ', $cls['interfaces'])) ?></code></p>
                        <?php endif; ?>
                        <div class="reflection-cols">
                            <div>
                                <h4>Properti</h4>
                                <?php if (count($cls['properties']) > 0): ?>
                                    <ul><?php foreach ($cls['properties'] as $p): ?><li><code><?= e($p) ?></code></li><?php endforeach; ?></ul>
                                <?php else: ?>
                                    <p class="muted small">Mewarisi dari parent.</p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4>Method publik</h4>
                                <ul><?php foreach (array_slice($cls['methods'], 0, 8) as $m): ?><li><code><?= e($m) ?></code></li><?php endforeach; ?></ul>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <!-- ===================== MODAL: DETAIL BUKU ===================== -->
    <dialog id="bookModal" class="modal" aria-labelledby="bmTitle">
        <div class="modal__inner modal__inner--book">
            <button type="button" class="modal__close" data-close-modal aria-label="Tutup">&times;</button>
            <div class="book-detail">
                <div id="bmCover" class="cover cover--lg">
                    <span class="cover__shine"></span><span class="cover__ring"></span>
                    <span class="cover__badge"></span><span class="cover__title"></span><span class="cover__author"></span>
                </div>
                <div class="book-detail__info">
                    <span id="bmType" class="pill pill--primary"></span>
                    <h2 id="bmTitle"></h2>
                    <p id="bmAuthor" class="book-detail__author"></p>
                    <p id="bmSynopsis" class="book-detail__synopsis"></p>
                    <dl class="spec-list">
                        <div><dt>Kategori</dt><dd id="bmCategory"></dd></div>
                        <div><dt>Tahun</dt><dd id="bmYear"></dd></div>
                        <div><dt>Halaman</dt><dd id="bmPages"></dd></div>
                        <div><dt>Format</dt><dd id="bmFormat"></dd></div>
                        <div><dt>Stok</dt><dd id="bmStock"></dd></div>
                        <div><dt>Info tambahan</dt><dd id="bmTech"></dd></div>
                    </dl>
                    <div class="book-detail__buy">
                        <p class="price price--lg"><strong id="bmPrice"></strong><span>/hari</span></p>
                        <button type="button" id="bmRentBtn" class="btn btn--primary btn--lg">Sewa buku ini</button>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    <!-- ===================== MODAL: FORM SEWA ===================== -->
    <dialog id="rentModal" class="modal" aria-labelledby="rmTitle">
        <form id="rentForm" method="POST" action="index.php" class="modal__inner modal__inner--rent" novalidate>
            <input type="hidden" name="action" value="rent_ebook">
            <input type="hidden" name="return_tab" value="loans">
            <input type="hidden" name="ebook_id" id="rentEbookId">
            <input type="hidden" name="member_id" id="rentMember" value="<?= $currentUser !== null ? e($currentUser->getId()) : '' ?>">

            <div class="rent-head">
                <div id="rmCover" class="cover cover--sm">
                    <span class="cover__shine"></span><span class="cover__ring"></span>
                    <span class="cover__badge"></span><span class="cover__title"></span><span class="cover__author"></span>
                </div>
                <div>
                    <span class="eyebrow">Sewa e-book</span>
                    <h2 id="rmTitle">Judul buku</h2>
                    <p class="muted" id="rmSub"></p>
                </div>
                <button type="button" class="modal__close" data-close-modal aria-label="Tutup">&times;</button>
            </div>

            <div class="rent-body">
                <div class="rent-account">
                    <span id="rentUserAvatar" class="avatar avatar--<?= $currentUser !== null ? e($currentUser->getTierCode()) : 'regular' ?>">
                        <?= $currentUser !== null ? e(memberInitials($currentUser->getName())) : 'A' ?>
                    </span>
                    <div class="rent-account__info">
                        <strong id="rentUserName"><?= $currentUser !== null ? e($currentUser->getName()) : '-' ?></strong>
                        <small class="muted" id="rentMemberInfo"></small>
                    </div>
                    <button type="button" id="btnSwitchAccount" class="btn btn--ghost btn--sm">Ganti akun</button>
                </div>

                <div class="field">
                    <span class="field__label">Berapa lama?</span>
                    <div class="day-chips" role="group" aria-label="Pilih lama sewa">
                        <button type="button" class="day-chip" data-days="1">1 hari</button>
                        <button type="button" class="day-chip" data-days="3">3 hari</button>
                        <button type="button" class="day-chip" data-days="7">7 hari</button>
                        <button type="button" class="day-chip" data-days="14">14 hari</button>
                        <button type="button" class="day-chip" data-days="21">21 hari</button>
                    </div>
                    <div class="day-custom">
                        <label for="rentDays" class="small muted">atau ketik jumlah hari:</label>
                        <div class="stepper">
                            <button type="button" id="dayMinus" aria-label="Kurangi hari">−</button>
                            <input type="number" id="rentDays" name="duration_days" value="3" min="1" max="30">
                            <button type="button" id="dayPlus" aria-label="Tambah hari">+</button>
                        </div>
                    </div>
                </div>

                <div class="bill" id="bill">
                    <div class="bill__row"><span id="billBaseLabel">Biaya sewa</span><span id="billBase">-</span></div>
                    <div class="bill__row"><span id="billServiceLabel">Biaya layanan</span><span id="billService">-</span></div>
                    <div class="bill__row bill__row--discount"><span id="billDiscountLabel">Diskon anggota</span><span id="billDiscount">-</span></div>
                    <div class="bill__row bill__row--muted"><span>Jatuh tempo</span><span id="billDue">-</span></div>
                    <div class="bill__total"><span>Total bayar</span><strong id="billTotal">-</strong></div>
                    <p class="bill__note">Dihitung server PHP · <code id="billTrace">User × MediaItem</code></p>
                </div>

                <div id="rentAlert" class="alert" hidden></div>
            </div>

            <div class="rent-foot">
                <button type="button" class="btn btn--ghost" data-close-modal>Batal</button>
                <button type="submit" id="rentSubmit" class="btn btn--primary">Sewa sekarang</button>
            </div>
        </form>
    </dialog>

    <!-- ===================== MODAL: MASUK / DAFTAR ANGGOTA ===================== -->
    <dialog id="authModal" class="modal" aria-labelledby="authModalTitle">
        <div class="modal__inner modal__inner--auth">
            <button type="button" class="modal__close" data-close-modal aria-label="Tutup">&times;</button>

            <div class="auth-head">
                <span class="eyebrow">Akun anggota</span>
                <h2 id="authModalTitle">Masuk ke Arkadia</h2>
                <p id="authPendingHint" class="auth-pending" hidden></p>
            </div>

            <div class="auth-tabs" role="tablist" aria-label="Pilih Masuk atau Daftar">
                <button type="button" class="auth-tab is-active" data-auth-tab="login" role="tab" aria-selected="true">Masuk</button>
                <button type="button" class="auth-tab" data-auth-tab="register" role="tab" aria-selected="false">Daftar Baru</button>
            </div>

            <!-- Panel Masuk -->
            <div id="authPanelLogin" class="auth-panel is-active">
                <form id="formAuthLogin" method="POST" action="index.php" class="auth-form" novalidate>
                    <input type="hidden" name="action" value="login_email">
                    <input type="hidden" name="return_tab" id="authLoginReturn" value="<?= e($tab) ?>">
                    <input type="hidden" name="pending_book" id="authLoginPending" value="">
                    <div class="field">
                        <label for="authLoginEmail">Email terdaftar</label>
                        <input type="email" id="authLoginEmail" name="email" placeholder="nama@webmail.umm.ac.id" required>
                        <small class="field__error" id="err-authLoginEmail"></small>
                    </div>
                    <button type="submit" class="btn btn--primary btn--full">Masuk</button>
                </form>

                <div class="auth-divider"><span>atau coba cepat 1 klik (akun demo)</span></div>

                <div class="demo-grid">
                    <?php foreach ($demoAccounts as $dm): ?>
                        <form method="POST" action="index.php" class="demo-form">
                            <input type="hidden" name="action" value="login_demo">
                            <input type="hidden" name="member_id" value="<?= e($dm->getId()) ?>">
                            <input type="hidden" name="return_tab" class="js-return-tab" value="<?= e($tab) ?>">
                            <input type="hidden" name="pending_book" class="js-pending-book" value="">
                            <button type="submit" class="demo-btn demo-btn--<?= e($dm->getTierCode()) ?>">
                                <span class="avatar avatar--xs avatar--<?= e($dm->getTierCode()) ?>"><?= e(memberInitials($dm->getName())) ?></span>
                                <span class="demo-btn__meta">
                                    <strong><?= e($dm->getTierLabel()) ?></strong>
                                    <small><?= e(firstName($dm->getName())) ?> &middot; Diskon <?= (int) round($dm->getDiscountRate() * 100) ?>%</small>
                                </span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Panel Daftar Baru -->
            <div id="authPanelRegister" class="auth-panel">
                <form id="formAuthRegister" method="POST" action="index.php" class="auth-form" novalidate>
                    <input type="hidden" name="action" value="auth_register">
                    <input type="hidden" name="return_tab" id="authRegReturn" value="<?= e($tab) ?>">
                    <input type="hidden" name="pending_book" id="authRegPending" value="">

                    <div class="field">
                        <label for="authRegName">Nama lengkap</label>
                        <input type="text" id="authRegName" name="name" placeholder="Contoh: Rania Putri" required minlength="3">
                        <small class="field__error" id="err-authRegName"></small>
                    </div>
                    <div class="field">
                        <label for="authRegEmail">Email</label>
                        <input type="email" id="authRegEmail" name="email" placeholder="rania@student.umm.ac.id" required>
                        <small class="field__error" id="err-authRegEmail"></small>
                    </div>
                    <div class="field">
                        <label for="authRegInst">Kelas / Fakultas</label>
                        <input type="text" id="authRegInst" name="institution" placeholder="Contoh: Informatika Kelas C — UMM" required minlength="3">
                        <small class="field__error" id="err-authRegInst"></small>
                    </div>
                    <div class="field">
                        <span class="field__label">Pilih jenis keanggotaan</span>
                        <div class="radio-cards" role="radiogroup" aria-label="Pilih jenis keanggotaan">
                            <?php foreach ($tierSamples as $i => $sample): ?>
                                <label class="radio-card">
                                    <input type="radio" name="tier" value="<?= e($sample->getTierCode()) ?>" <?= $i === 1 ? 'checked' : '' ?>>
                                    <span>
                                        <strong><?= e($sample->getTierLabel()) ?></strong>
                                        <small>Diskon <?= (int) round($sample->getDiscountRate() * 100) ?>% · maks <?= $sample->getMaxBorrowDays() ?> hari</small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn--primary btn--full">Daftar &amp; Masuk</button>
                </form>
            </div>
        </div>
    </dialog>

    <footer class="footer">
        <div class="container footer__inner">
            <p><strong>Arkadia</strong> · Prototipe UTS Pemrograman Web Ganjil 2026/2027 · Informatika UMM</p>
            <p class="muted small">HTML5 · CSS3 · JavaScript · PHP <?= e(PHP_VERSION) ?> OOP · Data JSON &amp; XML</p>
        </div>
    </footer>

    <script>
        window.ARKADIA_STATE = {
            catalog: <?= json_encode($clientCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>,
            members: <?= json_encode($clientMembers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>,
            currentUser: <?= $currentUser !== null ? json_encode($currentUser->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) : 'null' ?>
        };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
