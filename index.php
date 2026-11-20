<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use App\Services\LibraryManager;
use App\Services\OopInspectorService;

session_start();

$manager = new LibraryManager();
$inspector = new OopInspectorService();

// Endpoint API JSON untuk simulasi kalkulasi polimorfik real-time (AJAX)
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    $endpoint = (string) $_GET['api'];

    try {
        if ($endpoint === 'simulate') {
            $memberId = (string) ($_GET['member_id'] ?? '');
            $ebookId = (string) ($_GET['ebook_id'] ?? '');
            $days = max(1, (int) ($_GET['days'] ?? 5));
            $quote = $manager->simulateRentalQuote($memberId, $ebookId, $days);
            echo json_encode(['ok' => true, 'quote' => $quote], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($endpoint === 'xml_preview') {
            $xmlPath = __DIR__ . '/data/ebooks.xml';
            if (!is_file($xmlPath)) {
                $manager->resetDemoData();
            }
            $xmlContent = is_file($xmlPath) ? (string) file_get_contents($xmlPath) : '';
            echo json_encode(['ok' => true, 'xml' => $xmlContent], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Penanganan aksi POST (Transaksi Sewa, Pengembalian, Tambah E-Book, Registrasi Member, Reset)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $activeTab = (string) ($_POST['return_tab'] ?? 'catalog');

    try {
        switch ($action) {
            case 'rent_ebook':
                $memberId = (string) ($_POST['member_id'] ?? '');
                $ebookId = (string) ($_POST['ebook_id'] ?? '');
                $days = (int) ($_POST['duration_days'] ?? 0);

                $trx = $manager->createRentalTransaction($memberId, $ebookId, $days);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Transaksi Peminjaman Berhasil Diterbitkan',
                    'message' => sprintf(
                        'Kode %s — "%s" disewa oleh %s selama %d hari (Total: Rp %s • Token DRM: %s).',
                        $trx->getTransactionCode(),
                        $trx->getEbookTitle(),
                        $trx->getMemberName(),
                        $trx->getDurationDays(),
                        number_format($trx->getTotalCost(), 0, ',', '.'),
                        $trx->getAccessToken()
                    ),
                ];
                $activeTab = 'transactions';
                break;

            case 'return_ebook':
                $trxId = (string) ($_POST['transaction_id'] ?? '');
                $trx = $manager->returnRentalTransaction($trxId);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Lisensi E-Book Berhasil Dikembalikan',
                    'message' => sprintf(
                        'Transaksi %s ("%s") oleh %s telah ditutup dan stok lisensi telah dipulihkan.',
                        $trx->getTransactionCode(),
                        $trx->getEbookTitle(),
                        $trx->getMemberName()
                    ),
                ];
                $activeTab = 'transactions';
                break;

            case 'add_ebook':
                $ebook = $manager->addEbook($_POST);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Monograf / E-Book Baru Ditambahkan ke Katalog',
                    'message' => sprintf(
                        '[%s] "%s" oleh %s (%s) berhasil disimpan ke ebooks.json & ebooks.xml.',
                        $ebook->getId(),
                        $ebook->getTitle(),
                        $ebook->getAuthor(),
                        $ebook->getMediaTypeLabel()
                    ),
                ];
                $activeTab = 'catalog';
                break;

            case 'register_member':
                $member = $manager->registerMember($_POST);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Registrasi Member Baru Berhasil',
                    'message' => sprintf(
                        '%s (%s — %s) telah terdaftar dengan kuota %d buku & batas sewa %d hari.',
                        $member->getName(),
                        $member->getMemberCode(),
                        $member->getTierLabel(),
                        $member->getMaxConcurrentLoans(),
                        $member->getMaxBorrowDays()
                    ),
                ];
                $activeTab = 'members';
                break;

            case 'reset_demo':
                $manager->resetDemoData();
                $_SESSION['flash'] = [
                    'type' => 'info',
                    'title' => 'Basis Data JSON & XML Direset',
                    'message' => 'Seluruh data katalog e-book, member, dan riwayat transaksi telah dikembalikan ke state awal.',
                ];
                $activeTab = 'catalog';
                break;
        }
    } catch (Throwable $e) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'title' => 'Validasi Domain OOP Menolak Permintaan',
            'message' => $e->getMessage(),
        ];
    }

    header('Location: index.php?tab=' . urlencode($activeTab));
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$initialTab = (string) ($_GET['tab'] ?? 'catalog');
$ebooks = $manager->getAllEbooks();
$members = $manager->getAllMembers();
$transactions = $manager->getAllTransactions();
$categories = $manager->getDistinctCategories();
$metrics = $manager->getDashboardMetrics();
$oopReport = $inspector->inspectArchitecture();

// Data JSON tersemat untuk sinkronisasi kalkulator interaktif Front-End JS
$clientCatalog = array_map(fn($b) => $b->toArray(), $ebooks);
$clientMembers = array_map(fn($m) => $m->toArray(), $members);

function formatRupiah(float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function getMemberInitials(string $name): string
{
    $cleaned = preg_replace('/^(Dr\.|Prof\.|Ir\.|H\.|Hj\.)\s+/i', '', trim($name)) ?? trim($name);
    $cleaned = preg_replace('/,.*$/', '', $cleaned) ?? $cleaned;
    $parts = preg_split('/\s+/', trim($cleaned)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part !== '') {
            $initials .= strtoupper(mb_substr($part, 0, 1));
        }
    }
    return $initials !== '' ? $initials : 'AR';
}

function getCoverFormatBadge(string $mediaType): string
{
    return match ($mediaType) {
        'academic' => 'PDF/A',
        'interactive' => 'AUDIO+LAB',
        default => 'EPUB3',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arkadia — Platform Katalog &amp; Penyewaan E-Book Digital (PHP 8.x OOP)</title>
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
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Sticky Glassmorphic SaaS Header -->
    <header class="site-header">
        <div class="container site-header__inner">
            <div class="header-brand">
                <div class="brand-logo" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>
                        <path d="M9 7h6"/>
                        <path d="M9 11h4"/>
                    </svg>
                </div>
                <div class="brand-text">
                    <div class="brand-title-row">
                        <h1 class="brand-title">Arkadia</h1>
                        <span class="engine-badge">PHP <?= htmlspecialchars(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) ?> OOP</span>
                    </div>
                    <p class="brand-subtitle">Digital E-Book Lending Hub</p>
                </div>
            </div>

            <nav class="primary-nav" aria-label="Navigasi Modul Aplikasi">
                <div class="nav-tabs" role="tablist">
                    <button type="button"
                            class="nav-tab <?= $initialTab === 'catalog' ? 'is-active' : '' ?>"
                            data-tab-target="catalog"
                            role="tab"
                            aria-selected="<?= $initialTab === 'catalog' ? 'true' : 'false' ?>">
                        <svg class="nav-tab__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="7" height="7" x="3" y="3" rx="1"/>
                            <rect width="7" height="7" x="14" y="3" rx="1"/>
                            <rect width="7" height="7" x="14" y="14" rx="1"/>
                            <rect width="7" height="7" x="3" y="14" rx="1"/>
                        </svg>
                        <span class="nav-tab__label">Katalog</span>
                        <span class="nav-tab__badge"><?= count($ebooks) ?></span>
                    </button>

                    <button type="button"
                            class="nav-tab <?= $initialTab === 'transactions' ? 'is-active' : '' ?>"
                            data-tab-target="transactions"
                            role="tab"
                            aria-selected="<?= $initialTab === 'transactions' ? 'true' : 'false' ?>">
                        <svg class="nav-tab__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/>
                            <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/>
                            <path d="M12 17.5v-11"/>
                        </svg>
                        <span class="nav-tab__label">Transaksi</span>
                        <span class="nav-tab__badge"><?= (int) $metrics['active_loans'] ?> Aktif</span>
                    </button>

                    <button type="button"
                            class="nav-tab <?= $initialTab === 'members' ? 'is-active' : '' ?>"
                            data-tab-target="members"
                            role="tab"
                            aria-selected="<?= $initialTab === 'members' ? 'true' : 'false' ?>">
                        <svg class="nav-tab__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span class="nav-tab__label">Member</span>
                        <span class="nav-tab__badge"><?= count($members) ?></span>
                    </button>

                    <button type="button"
                            class="nav-tab <?= $initialTab === 'manage-catalog' ? 'is-active' : '' ?>"
                            data-tab-target="manage-catalog"
                            role="tab"
                            aria-selected="<?= $initialTab === 'manage-catalog' ? 'true' : 'false' ?>">
                        <svg class="nav-tab__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M8 12h8"/>
                            <path d="M12 8v8"/>
                        </svg>
                        <span class="nav-tab__label">Tambah E-Book</span>
                    </button>

                    <button type="button"
                            class="nav-tab <?= $initialTab === 'oop-architecture' ? 'is-active' : '' ?>"
                            data-tab-target="oop-architecture"
                            role="tab"
                            aria-selected="<?= $initialTab === 'oop-architecture' ? 'true' : 'false' ?>">
                        <svg class="nav-tab__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="16 18 22 12 16 6"/>
                            <polyline points="8 6 2 12 8 18"/>
                        </svg>
                        <span class="nav-tab__label">OOP Inspector</span>
                        <span class="nav-tab__badge nav-tab__badge--highlight">Reflection</span>
                    </button>
                </div>
            </nav>

            <div class="header-actions">
                <button type="button" id="btnThemeToggle" class="btn-icon-toggle" aria-label="Ganti Tema Gelap/Terang" title="Ganti Tema Gelap/Terang">
                    <svg class="icon-sun" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M12 2v2"/><path d="M12 20v2"/>
                        <path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/>
                        <path d="M2 12h2"/><path d="M20 12h2"/>
                        <path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                    </svg>
                    <svg class="icon-moon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                    </svg>
                </button>

                <form method="POST" action="index.php" class="inline-reset-form" onsubmit="return confirm('Reset seluruh data JSON/XML ke kondisi semula?');">
                    <input type="hidden" name="action" value="reset_demo">
                    <button type="submit" class="btn-header-reset" title="Kembalikan data demo JSON & XML ke kondisi awal">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                        </svg>
                        <span>Reset</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Semantic Content Shell -->
    <main class="container main-shell">
        <!-- Modern Bento KPI Telemetry Strip -->
        <section class="kpi-bento" aria-label="Ringkasan Sistem">
            <article class="kpi-card">
                <div class="kpi-card__icon kpi-card__icon--blue" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                </div>
                <div class="kpi-card__body">
                    <span class="kpi-card__label">Koleksi E-Book</span>
                    <strong class="kpi-card__value"><?= (int) $metrics['total_titles'] ?> <em>Judul</em></strong>
                    <span class="kpi-card__sub"><?= (int) $metrics['available_copies'] ?>/<?= (int) $metrics['total_copies'] ?> lisensi tersedia • PSR-4</span>
                </div>
            </article>

            <article class="kpi-card">
                <div class="kpi-card__icon kpi-card__icon--indigo" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="kpi-card__body">
                    <span class="kpi-card__label">Member Terdaftar</span>
                    <strong class="kpi-card__value"><?= (int) $metrics['total_members'] ?> <em>Akun</em></strong>
                    <span class="kpi-card__sub">3 Subclass User Polimorfik</span>
                </div>
            </article>

            <article class="kpi-card">
                <div class="kpi-card__icon kpi-card__icon--amber" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="kpi-card__body">
                    <span class="kpi-card__label">Pinjaman Aktif</span>
                    <strong class="kpi-card__value"><?= (int) $metrics['active_loans'] ?> <em>Aktif</em></strong>
                    <span class="kpi-card__sub"><?= (int) $metrics['completed_loans'] ?> selesai dikembalikan</span>
                </div>
            </article>

            <article class="kpi-card kpi-card--highlight">
                <div class="kpi-card__icon kpi-card__icon--emerald" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="kpi-card__body">
                    <span class="kpi-card__label">Total Royalti Sewa</span>
                    <strong class="kpi-card__value"><?= formatRupiah((float) $metrics['total_revenue']) ?></strong>
                    <span class="kpi-card__sub">Persistensi JSON &amp; XML</span>
                </div>
            </article>
        </section>

        <div class="workspace-grid">
            <!-- Left/Center Primary Column -->
            <div class="workspace-primary">
                <?php if ($flash !== null): ?>
                    <div class="flash-banner flash-banner--<?= htmlspecialchars((string) $flash['type']) ?>" role="alert">
                        <div class="flash-banner__content">
                            <strong class="flash-banner__title"><?= htmlspecialchars((string) $flash['title']) ?></strong>
                            <p class="flash-banner__text"><?= htmlspecialchars((string) $flash['message']) ?></p>
                        </div>
                        <button type="button" class="flash-banner__close" onclick="this.parentElement.remove()" aria-label="Tutup notifikasi">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- TAB 1: KATALOG MEDIA & E-BOOK -->
                <section id="panel-catalog" class="tab-panel <?= $initialTab === 'catalog' ? 'is-active' : '' ?>" aria-labelledby="heading-catalog">
                    <div class="section-header">
                        <div>
                            <span class="section-kicker">Katalog Media Digital</span>
                            <h2 id="heading-catalog" class="section-title">Eksplorasi Koleksi E-Book</h2>
                        </div>
                        <p class="section-desc">
                            Setiap item diinstansiasi secara polimorfik dari <code>MediaItem</code> menjadi
                            <code>StandardEBook</code>, <code>AcademicMonograph</code>, atau <code>InteractiveMediaBook</code>.
                        </p>
                    </div>

                    <!-- Interactive Filter & Search Toolbar -->
                    <div class="filter-bar" role="search" aria-label="Filter dan Pencarian Katalog E-Book">
                        <div class="filter-bar__search">
                            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.3-4.3"/>
                            </svg>
                            <label for="catalogSearch" class="sr-only">Cari judul, penulis, atau ISBN</label>
                            <input type="search"
                                   id="catalogSearch"
                                   class="form-input form-input--search"
                                   placeholder="Cari judul buku, penulis, DOI, atau ISBN..."
                                   autocomplete="off">
                        </div>

                        <div class="filter-bar__controls">
                            <select id="filterCategory" class="form-select" aria-label="Filter Kategori">
                                <option value="all">Semua Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <select id="filterMediaType" class="form-select" aria-label="Filter Subclass OOP">
                                <option value="all">Semua Subclass</option>
                                <option value="standard">Standard Trade E-Book</option>
                                <option value="academic">Academic Monograph</option>
                                <option value="interactive">Interactive Suite &amp; Audio</option>
                            </select>

                            <select id="filterAvailability" class="form-select" aria-label="Filter Ketersediaan">
                                <option value="all">Semua Status</option>
                                <option value="available">Tersedia Dipinjam</option>
                            </select>
                        </div>
                    </div>

                    <!-- Book Grid -->
                    <div id="catalogGrid" class="catalog-grid">
                        <?php foreach ($ebooks as $book): ?>
                            <?php $isAvailable = $book->isAvailable(); ?>
                            <article class="book-card"
                                     data-id="<?= htmlspecialchars($book->getId()) ?>"
                                     data-title="<?= htmlspecialchars(strtolower($book->getTitle() . ' ' . $book->getAuthor() . ' ' . $book->getIsbn())) ?>"
                                     data-category="<?= htmlspecialchars($book->getCategory()) ?>"
                                     data-type="<?= htmlspecialchars($book->getMediaTypeCode()) ?>"
                                     data-available="<?= $isAvailable ? '1' : '0' ?>">

                                <!-- Modern Gradient Vector Book Cover -->
                                <figure class="book-cover book-cover--<?= htmlspecialchars($book->getCoverTheme()) ?>">
                                    <div class="book-cover__glow" aria-hidden="true"></div>
                                    <div class="book-cover__inner">
                                        <div class="book-cover__top">
                                            <span class="book-cover__pill"><?= htmlspecialchars(getCoverFormatBadge($book->getMediaTypeCode())) ?></span>
                                            <span class="book-cover__code"><?= htmlspecialchars($book->getId()) ?></span>
                                        </div>
                                        <div class="book-cover__art" aria-hidden="true">
                                            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                                                <circle cx="24" cy="24" r="19" stroke="rgba(255,255,255,0.28)" stroke-width="1.2"/>
                                                <rect x="13" y="13" width="22" height="22" rx="6" transform="rotate(15 24 24)" stroke="rgba(255,255,255,0.45)" stroke-width="1.3"/>
                                                <circle cx="24" cy="24" r="4.5" fill="rgba(255,255,255,0.85)"/>
                                            </svg>
                                        </div>
                                        <figcaption class="book-cover__bottom">
                                            <span class="book-cover__category"><?= htmlspecialchars($book->getCategory()) ?></span>
                                            <span class="book-cover__title"><?= htmlspecialchars($book->getTitle()) ?></span>
                                        </figcaption>
                                    </div>
                                </figure>

                                <!-- Clean Card Content Body -->
                                <div class="book-card__body">
                                    <div class="book-card__main">
                                        <div class="book-card__badges">
                                            <span class="badge badge--type badge--<?= htmlspecialchars($book->getMediaTypeCode()) ?>">
                                                <?= htmlspecialchars($book->getMediaTypeLabel()) ?>
                                            </span>
                                            <?php if ($isAvailable): ?>
                                                <span class="badge badge--available">
                                                    <span class="avail-dot"></span>Tersedia <?= $book->getStock() ?>/<?= $book->getTotalStock() ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge--out">Habis (0/<?= $book->getTotalStock() ?>)</span>
                                            <?php endif; ?>
                                        </div>

                                        <h3 class="book-card__title"><?= htmlspecialchars($book->getTitle()) ?></h3>
                                        <p class="book-card__author"><?= htmlspecialchars($book->getAuthor()) ?></p>
                                        <p class="book-card__synopsis"><?= htmlspecialchars($book->getSynopsis()) ?></p>

                                        <div class="book-meta-chips">
                                            <span class="meta-chip"><?= $book->getPages() ?> hlm</span>
                                            <span class="meta-chip"><?= number_format($book->getFileSizeMb(), 1) ?> MB</span>
                                            <span class="meta-chip"><?= $book->getPublishYear() ?></span>
                                            <span class="meta-chip meta-chip--tech" title="<?= htmlspecialchars($book->getTechnicalMetadata()) ?>">
                                                <?= htmlspecialchars($book->getTechnicalMetadata()) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="book-card__footer">
                                        <div class="book-price">
                                            <strong class="book-price__value"><?= formatRupiah($book->getDailyRate()) ?><span>/hari</span></strong>
                                            <span class="book-price__fee">+Layanan <?= formatRupiah($book->getServiceFee()) ?></span>
                                        </div>
                                        <div class="book-card__actions">
                                            <button type="button"
                                                    class="btn btn--ghost btn-inspect-book"
                                                    data-book-id="<?= htmlspecialchars($book->getId()) ?>">
                                                Detail
                                            </button>
                                            <button type="button"
                                                    class="btn btn--primary btn-select-rent"
                                                    data-book-id="<?= htmlspecialchars($book->getId()) ?>"
                                                    <?= !$isAvailable ? 'disabled' : '' ?>>
                                                <?= $isAvailable ? 'Pinjam' : 'Habis' ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div id="catalogEmptyState" class="empty-state" hidden>
                        <h3>Tidak Ada E-Book yang Cocok dengan Filter</h3>
                        <p>Coba ubah kata kunci pencarian atau pilih kategori dan subclass media lainnya.</p>
                        <button type="button" id="btnResetFilter" class="btn btn--secondary">Tampilkan Semua Koleksi</button>
                    </div>
                </section>

                <!-- TAB 2: RIWAYAT TRANSAKSI & PENGEMBALIAN -->
                <section id="panel-transactions" class="tab-panel <?= $initialTab === 'transactions' ? 'is-active' : '' ?>" aria-labelledby="heading-transactions">
                    <div class="section-header">
                        <div>
                            <span class="section-kicker">Sirkulasi Lisensi &amp; Ledger</span>
                            <h2 id="heading-transactions" class="section-title">Riwayat Penyewaan &amp; Pengembalian</h2>
                        </div>
                        <p class="section-desc">
                            Rekam kalkulasi polimorfik antara subclass <code>User</code> dan <code>MediaItem</code>
                            yang tersimpan persisten ke <code>data/transactions.json</code>.
                        </p>
                    </div>

                    <div class="ledger-card">
                        <div class="table-responsive">
                            <table class="ledger-table">
                                <thead>
                                    <tr>
                                        <th>Kode &amp; Token DRM</th>
                                        <th>Peminjam (Member)</th>
                                        <th>Monograf / E-Book</th>
                                        <th>Masa Sewa</th>
                                        <th>Rincian Biaya</th>
                                        <th>Total Bayar</th>
                                        <th>Status &amp; Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $trx): ?>
                                        <tr class="<?= $trx->isActive() ? 'row-active' : 'row-returned' ?>">
                                            <td>
                                                <strong class="mono-code"><?= htmlspecialchars($trx->getTransactionCode()) ?></strong>
                                                <span class="token-pill">DRM: <?= htmlspecialchars($trx->getAccessToken()) ?></span>
                                            </td>
                                            <td>
                                                <div class="cell-primary"><?= htmlspecialchars($trx->getMemberName()) ?></div>
                                                <span class="cell-sub"><?= htmlspecialchars($trx->getMemberTier()) ?></span>
                                            </td>
                                            <td>
                                                <div class="cell-primary"><?= htmlspecialchars($trx->getEbookTitle()) ?></div>
                                                <span class="cell-sub"><?= htmlspecialchars($trx->getEbookType()) ?> (<?= htmlspecialchars($trx->getEbookId()) ?>)</span>
                                            </td>
                                            <td>
                                                <div class="cell-primary"><?= $trx->getDurationDays() ?> Hari</div>
                                                <span class="cell-sub mono-text"><?= htmlspecialchars($trx->getBorrowDate()) ?> &rarr; <?= htmlspecialchars($trx->getDueDate()) ?></span>
                                            </td>
                                            <td>
                                                <div class="cost-breakdown-mini">
                                                    <span>Pokok: <?= formatRupiah($trx->getBaseCost()) ?></span>
                                                    <span>Layanan: +<?= formatRupiah($trx->getServiceFee()) ?></span>
                                                    <?php if ($trx->getDiscountAmount() > 0): ?>
                                                        <span class="discount-tag">Diskon: -<?= formatRupiah($trx->getDiscountAmount()) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="mono-price"><?= formatRupiah($trx->getTotalCost()) ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($trx->isActive()): ?>
                                                    <span class="status-badge status-badge--active">Sedang Dipinjam</span>
                                                    <form method="POST" action="index.php" class="return-form">
                                                        <input type="hidden" name="action" value="return_ebook">
                                                        <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($trx->getId()) ?>">
                                                        <input type="hidden" name="return_tab" value="transactions">
                                                        <button type="submit" class="btn btn--small btn--return">
                                                            Kembalikan E-Book
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="status-badge status-badge--returned">Telah Dikembalikan</span>
                                                    <small class="returned-date">Tgl: <?= htmlspecialchars((string) $trx->getReturnDate()) ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- TAB 3: DIREKTORI & REGISTRASI MEMBER -->
                <section id="panel-members" class="tab-panel <?= $initialTab === 'members' ? 'is-active' : '' ?>" aria-labelledby="heading-members">
                    <div class="section-header">
                        <div>
                            <span class="section-kicker">Manajemen Pengguna</span>
                            <h2 id="heading-members" class="section-title">Direktori Member &amp; Hierarki Akses</h2>
                        </div>
                        <p class="section-desc">
                            Pewarisan (Inheritance) dari abstract class <code>App\Models\User</code> ke
                            <code>RegularMember</code>, <code>PremiumMember</code>, dan <code>FacultyMember</code>.
                        </p>
                    </div>

                    <div class="members-grid">
                        <?php foreach ($members as $member): ?>
                            <?php
                            $quotaRatio = min(100, (int) round(($member->getActiveLoans() / max(1, $member->getMaxConcurrentLoans())) * 100));
                            $initials = getMemberInitials($member->getName());
                            ?>
                            <article class="member-card member-card--<?= htmlspecialchars($member->getTierCode()) ?>">
                                <div class="member-card__header">
                                    <div class="member-identity">
                                        <div class="member-avatar member-avatar--<?= htmlspecialchars($member->getTierCode()) ?>" aria-hidden="true">
                                            <?= htmlspecialchars($initials) ?>
                                        </div>
                                        <div>
                                            <span class="mono-code"><?= htmlspecialchars($member->getMemberCode()) ?></span>
                                            <h3 class="member-card__name"><?= htmlspecialchars($member->getName()) ?></h3>
                                            <p class="member-card__inst"><?= htmlspecialchars($member->getInstitution()) ?></p>
                                        </div>
                                    </div>
                                    <span class="tier-pill tier-pill--<?= htmlspecialchars($member->getTierCode()) ?>">
                                        <?= htmlspecialchars($member->getTierLabel()) ?>
                                    </span>
                                </div>

                                <div class="member-card__body">
                                    <div class="member-meta-row">
                                        <span>Email</span>
                                        <strong class="mono-text"><?= htmlspecialchars($member->getEmail()) ?></strong>
                                    </div>
                                    <div class="member-meta-row">
                                        <span>Class PHP</span>
                                        <code class="class-chip">App\Models\<?= (new ReflectionClass($member))->getShortName() ?></code>
                                    </div>
                                    <div class="member-meta-row">
                                        <span>Hak Polimorfik</span>
                                        <strong class="text-right"><?= htmlspecialchars($member->getPrivilegeSummary()) ?></strong>
                                    </div>

                                    <div class="quota-meter">
                                        <div class="quota-meter__labels">
                                            <span>Kuota Pinjaman Aktif</span>
                                            <strong class="mono-text"><?= $member->getActiveLoans() ?> / <?= $member->getMaxConcurrentLoans() ?> E-Book</strong>
                                        </div>
                                        <div class="quota-meter__track">
                                            <div class="quota-meter__fill" style="width: <?= $quotaRatio ?>%"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="member-card__footer">
                                    <span class="joined-label">Bergabung <?= htmlspecialchars($member->getJoinedDate()) ?></span>
                                    <button type="button"
                                            class="btn btn--ghost btn--small btn-pick-member"
                                            data-member-id="<?= htmlspecialchars($member->getId()) ?>">
                                        Gunakan di Simulasi &rarr;
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Form Registrasi Member Baru -->
                    <div class="form-panel">
                        <div class="form-panel__header">
                            <span class="section-kicker">Pendaftaran Anggota Baru</span>
                            <h3>Registrasi User / Member Perpustakaan</h3>
                            <p>Input divalidasi di client-side (JS) dan server-side (PHP OOP) sebelum diinstansiasi menjadi subclass <code>User</code>.</p>
                        </div>

                        <form id="formRegisterMember" method="POST" action="index.php" novalidate>
                            <input type="hidden" name="action" value="register_member">
                            <input type="hidden" name="return_tab" value="members">

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label for="memberName" class="form-label">Nama Lengkap Member <span class="req">*</span></label>
                                    <input type="text"
                                           id="memberName"
                                           name="name"
                                           class="form-input"
                                           placeholder="Contoh: Dr. Hendra Pratama, S.Kom., M.T."
                                           required
                                           minlength="3">
                                    <span class="field-error" id="err-memberName"></span>
                                </div>

                                <div class="form-group">
                                    <label for="memberEmail" class="form-label">Alamat Email Institusi / Pribadi <span class="req">*</span></label>
                                    <input type="email"
                                           id="memberEmail"
                                           name="email"
                                           class="form-input"
                                           placeholder="nama@webmail.umm.ac.id"
                                           required>
                                    <span class="field-error" id="err-memberEmail"></span>
                                </div>

                                <div class="form-group">
                                    <label for="memberInstitution" class="form-label">Afiliasi / Kelas / Fakultas <span class="req">*</span></label>
                                    <input type="text"
                                           id="memberInstitution"
                                           name="institution"
                                           class="form-input"
                                           placeholder="Contoh: Informatika Kelas E — Fakultas Teknik UMM"
                                           required
                                           minlength="3">
                                    <span class="field-error" id="err-memberInstitution"></span>
                                </div>

                                <div class="form-group">
                                    <label for="memberTier" class="form-label">Tipe Keanggotaan (Subclass Polimorfik) <span class="req">*</span></label>
                                    <select id="memberTier" name="tier" class="form-select" required>
                                        <option value="regular">RegularMember — Diskon 0%, Maks 7 Hari, Kuota 2 Buku</option>
                                        <option value="premium">PremiumMember (VIP) — Diskon 20%, Bebas Biaya Layanan, Maks 14 Hari</option>
                                        <option value="faculty">FacultyMember (Scholar) — Subsidi Riset 30%, Maks 21 Hari, Kuota 8 Buku</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn--primary">Daftarkan Member Baru</button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- TAB 4: TAMBAH E-BOOK BARU KE KATALOG -->
                <section id="panel-manage-catalog" class="tab-panel <?= $initialTab === 'manage-catalog' ? 'is-active' : '' ?>" aria-labelledby="heading-add-ebook">
                    <div class="section-header">
                        <div>
                            <span class="section-kicker">Manajemen Katalog &amp; Serialisasi</span>
                            <h2 id="heading-add-ebook" class="section-title">Tambah Koleksi E-Book Baru</h2>
                        </div>
                        <p class="section-desc">
                            Membuat instance baru dari <code>StandardEBook</code>, <code>AcademicMonograph</code>, atau
                            <code>InteractiveMediaBook</code> serta menyimpannya ke <code>ebooks.json</code> &amp; <code>ebooks.xml</code>.
                        </p>
                    </div>

                    <div class="form-panel">
                        <form id="formAddEbook" method="POST" action="index.php" novalidate>
                            <input type="hidden" name="action" value="add_ebook">
                            <input type="hidden" name="return_tab" value="catalog">

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label for="ebookTitle" class="form-label">Judul E-Book / Monograf <span class="req">*</span></label>
                                    <input type="text"
                                           id="ebookTitle"
                                           name="title"
                                           class="form-input"
                                           placeholder="Contoh: Modern Compiler Implementation in PHP & Rust"
                                           required
                                           minlength="4">
                                    <span class="field-error" id="err-ebookTitle"></span>
                                </div>

                                <div class="form-group">
                                    <label for="ebookAuthor" class="form-label">Penulis / Editor Utama <span class="req">*</span></label>
                                    <input type="text"
                                           id="ebookAuthor"
                                           name="author"
                                           class="form-input"
                                           placeholder="Contoh: Prof. Andrew W. Appel"
                                           required
                                           minlength="3">
                                    <span class="field-error" id="err-ebookAuthor"></span>
                                </div>

                                <div class="form-group">
                                    <label for="ebookMediaType" class="form-label">Subclass Media (Polimorfisme OOP) <span class="req">*</span></label>
                                    <select id="ebookMediaType" name="media_type" class="form-select" required>
                                        <option value="standard">StandardEBook (Layanan Rp 1.500, diskon durasi >= 7 hari)</option>
                                        <option value="academic">AcademicMonograph (Layanan Rp 3.000 + Lisensi DOI Rp 2.500)</option>
                                        <option value="interactive">InteractiveMediaBook (Layanan Rp 4.500 + Sandbox Rp 1.000/hari)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="ebookCategory" class="form-label">Kategori Keilmuan <span class="req">*</span></label>
                                    <select id="ebookCategory" name="category" class="form-select" required>
                                        <option value="Web Engineering">Web Engineering</option>
                                        <option value="Software Architecture">Software Architecture</option>
                                        <option value="Interface Design">Interface Design</option>
                                        <option value="Artificial Intelligence">Artificial Intelligence</option>
                                        <option value="Cybersecurity">Cybersecurity</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="ebookDailyRate" class="form-label">Tarif Sewa Harian (Rp) <span class="req">*</span></label>
                                    <input type="number"
                                           id="ebookDailyRate"
                                           name="daily_rate"
                                           class="form-input"
                                           value="5500"
                                           min="1000"
                                           max="100000"
                                           step="500"
                                           required>
                                    <span class="field-error" id="err-ebookDailyRate"></span>
                                </div>

                                <div class="form-group">
                                    <label for="ebookStock" class="form-label">Jumlah Lisensi Tersedia (Stok) <span class="req">*</span></label>
                                    <input type="number"
                                           id="ebookStock"
                                           name="stock"
                                           class="form-input"
                                           value="3"
                                           min="1"
                                           max="50"
                                           required>
                                    <span class="field-error" id="err-ebookStock"></span>
                                </div>

                                <div class="form-group">
                                    <label for="ebookIsbn" class="form-label">Nomor ISBN-13 <span class="req">*</span></label>
                                    <input type="text"
                                           id="ebookIsbn"
                                           name="isbn"
                                           class="form-input"
                                           value="978-1-4920-8812-7"
                                           required>
                                    <span class="field-error" id="err-ebookIsbn"></span>
                                </div>

                                <div class="form-group">
                                    <label for="ebookPages" class="form-label">Jumlah Halaman &amp; Tema Sampul</label>
                                    <div class="input-split">
                                        <input type="number"
                                               id="ebookPages"
                                               name="pages"
                                               class="form-input"
                                               value="420"
                                               min="20"
                                               max="2500"
                                               required>
                                        <select id="ebookCoverTheme" name="cover_theme" class="form-select">
                                            <option value="terracotta">Cobalt Tech</option>
                                            <option value="obsidian">Midnight Obsidian</option>
                                            <option value="forest">Emerald Vector</option>
                                            <option value="indigo">Electric Indigo</option>
                                            <option value="amber">Amber Pulse</option>
                                            <option value="crimson">Crimson Cipher</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Dynamic Subclass Metadata Input -->
                                <div class="form-group" id="groupDoi" hidden>
                                    <label for="ebookDoi" class="form-label">Nomor DOI Jurnal / Monograf (Khusus AcademicMonograph)</label>
                                    <input type="text" id="ebookDoi" name="doi_number" class="form-input" value="10.1145/3612045.3612998">
                                </div>

                                <div class="form-group" id="groupAudio" hidden>
                                    <label for="ebookAudio" class="form-label">Durasi Audio Narasi dalam Menit (Khusus InteractiveMediaBook)</label>
                                    <input type="number" id="ebookAudio" name="audio_duration_minutes" class="form-input" value="285" min="30">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="ebookSynopsis" class="form-label">Sinopsis / Abstrak Monograf <span class="req">*</span></label>
                                <textarea id="ebookSynopsis"
                                          name="synopsis"
                                          rows="3"
                                          class="form-textarea"
                                          placeholder="Jelaskan ringkasan isi buku, cakupan materi teknis, dan kontribusi utamanya..."
                                          required
                                          minlength="15"></textarea>
                                <span class="field-error" id="err-ebookSynopsis"></span>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn--primary">Simpan ke Katalog JSON &amp; XML</button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- TAB 5: INSPEKTOR ARSITEKTUR PHP 8.x OOP (LIVE REFLECTION) -->
                <section id="panel-oop-architecture" class="tab-panel <?= $initialTab === 'oop-architecture' ? 'is-active' : '' ?>" aria-labelledby="heading-oop">
                    <div class="section-header">
                        <div>
                            <span class="section-kicker">Verifikasi Arsitektur Backend</span>
                            <h2 id="heading-oop" class="section-title">Inspektor Live PHP 8.x OOP &amp; Reflection</h2>
                        </div>
                        <p class="section-desc">
                            Membaca struktur class di direktori <code>src/</code> secara real-time menggunakan
                            <strong>PHP Reflection API</strong> untuk memverifikasi Namespace, Encapsulation, Inheritance, dan Polymorphism.
                        </p>
                    </div>

                    <div class="oop-pillars">
                        <div class="pillar-card">
                            <span class="pillar-card__num">01 / ENCAPSULATION</span>
                            <h3>Private &amp; Protected State</h3>
                            <p>Atribut pada <code>MediaItem</code>, <code>User</code>, dan <code>Transaction</code> bersifat <code>private</code> dengan strict type hinting dan akses via Getter/Setter.</p>
                        </div>
                        <div class="pillar-card">
                            <span class="pillar-card__num">02 / INHERITANCE</span>
                            <h3>2 Hierarki Class</h3>
                            <p><code>StandardEBook</code>, <code>AcademicMonograph</code>, dan <code>InteractiveMediaBook</code> mewarisi <code>MediaItem</code>; 3 tipe member mewarisi <code>User</code>.</p>
                        </div>
                        <div class="pillar-card">
                            <span class="pillar-card__num">03 / POLYMORPHISM</span>
                            <h3>Dynamic Dispatch</h3>
                            <p><code>calculateBaseRentalCost()</code>, <code>getServiceFee()</code>, <code>getDiscountRate()</code>, dan <code>waivesServiceFee()</code> beradaptasi sesuai instance runtime.</p>
                        </div>
                        <div class="pillar-card">
                            <span class="pillar-card__num">04 / PSR-4 NAMESPACE</span>
                            <h3>Modular <code>src/</code></h3>
                            <p>Terorganisir di <code>App\Contracts</code>, <code>App\Models</code>, dan <code>App\Services</code> dengan 1 class per berkas &amp; autoloader PSR-4.</p>
                        </div>
                    </div>

                    <div class="reflection-grid">
                        <?php foreach ($oopReport as $cls): ?>
                            <article class="reflection-card">
                                <div class="reflection-card__header">
                                    <span class="role-chip"><?= htmlspecialchars((string) $cls['role']) ?></span>
                                    <code class="file-path"><?= htmlspecialchars((string) $cls['file']) ?></code>
                                </div>
                                <h3 class="reflection-card__title"><?= htmlspecialchars((string) $cls['fqcn']) ?></h3>

                                <?php if ($cls['parent'] !== null || count($cls['interfaces']) > 0): ?>
                                    <div class="reflection-lineage">
                                        <?php if ($cls['parent'] !== null): ?>
                                            <span>extends <code><?= htmlspecialchars((string) $cls['parent']) ?></code></span>
                                        <?php endif; ?>
                                        <?php if (count($cls['interfaces']) > 0): ?>
                                            <span>implements <code><?= htmlspecialchars(implode(', ', $cls['interfaces'])) ?></code></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="reflection-columns">
                                    <div>
                                        <h4>Encapsulated Properties</h4>
                                        <?php if (count($cls['properties']) > 0): ?>
                                            <ul class="code-list">
                                                <?php foreach ($cls['properties'] as $prop): ?>
                                                    <li><code><?= htmlspecialchars($prop) ?></code></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="muted-note">Mewarisi properti private dari parent class / kontrak.</p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h4>Public &amp; Polymorphic Methods</h4>
                                        <ul class="code-list">
                                            <?php foreach (array_slice($cls['methods'], 0, 7) as $m): ?>
                                                <li><code><?= htmlspecialchars($m) ?></code></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- Right Sticky Column: Fintech-Style Rental Simulator & Checkout Workbench (<aside>) -->
            <aside class="workspace-sidebar" aria-label="Simulator & Kasir Penyewaan E-Book">
                <div class="workbench-card">
                    <div class="workbench-card__header">
                        <div class="workbench-title-row">
                            <span class="workbench-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="16" height="20" x="4" y="2" rx="2"/>
                                    <line x1="8" x2="16" y1="6" y2="6"/>
                                    <line x1="16" x2="16" y1="14" y2="18"/>
                                    <path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/>
                                    <path d="M12 14h.01"/><path d="M8 14h.01"/>
                                    <path d="M12 18h.01"/><path d="M8 18h.01"/>
                                </svg>
                            </span>
                            <div>
                                <span class="workbench-kicker">Simulasi &amp; Checkout</span>
                                <h2 class="workbench-title">Kalkulator Sewa Polimorfik</h2>
                            </div>
                        </div>
                        <p class="workbench-subtitle">
                            Pilih member, e-book, dan durasi hari untuk melihat kalkulasi otomatis dan menerbitkan transaksi.
                        </p>
                    </div>

                    <form id="formRentalSimulation" method="POST" action="index.php" class="workbench-form" novalidate>
                        <input type="hidden" name="action" value="rent_ebook">
                        <input type="hidden" name="return_tab" value="transactions">

                        <div class="form-group">
                            <label for="simMemberId" class="form-label">1. Pilih Member Peminjam <span class="req">*</span></label>
                            <select id="simMemberId" name="member_id" class="form-select" required>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= htmlspecialchars($m->getId()) ?>">
                                        <?= htmlspecialchars($m->getName()) ?> (<?= htmlspecialchars($m->getTierLabel()) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="simMemberMeta" class="sim-context-pill"></div>
                        </div>

                        <div class="form-group">
                            <label for="simEbookId" class="form-label">2. Pilih E-Book / Monograf <span class="req">*</span></label>
                            <select id="simEbookId" name="ebook_id" class="form-select" required>
                                <?php foreach ($ebooks as $b): ?>
                                    <option value="<?= htmlspecialchars($b->getId()) ?>" <?= !$b->isAvailable() ? 'disabled' : '' ?>>
                                        [<?= htmlspecialchars($b->getId()) ?>] <?= htmlspecialchars($b->getTitle()) ?> (<?= formatRupiah($b->getDailyRate()) ?>/hr)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="simEbookMeta" class="sim-context-pill"></div>
                        </div>

                        <div class="form-group">
                            <div class="slider-header">
                                <label for="simDurationDays" class="form-label">3. Durasi Penyewaan <span class="req">*</span></label>
                                <strong id="simDurationBadge" class="duration-readout">5 Hari</strong>
                            </div>
                            <div class="duration-presets" role="group" aria-label="Pilih cepat durasi hari sewa">
                                <button type="button" class="duration-preset-btn" data-days="3">3 Hr</button>
                                <button type="button" class="duration-preset-btn is-active" data-days="5">5 Hr</button>
                                <button type="button" class="duration-preset-btn" data-days="7">7 Hr</button>
                                <button type="button" class="duration-preset-btn" data-days="14">14 Hr</button>
                                <button type="button" class="duration-preset-btn" data-days="21">21 Hr</button>
                            </div>
                            <input type="range"
                                   id="simDurationSlider"
                                   min="1"
                                   max="21"
                                   value="5"
                                   class="duration-slider"
                                   aria-label="Slider durasi hari sewa">
                            <div class="duration-number-row">
                                <input type="number"
                                       id="simDurationDays"
                                       name="duration_days"
                                       class="form-input form-input--compact"
                                       value="5"
                                       min="1"
                                       max="21"
                                       required>
                                <span id="simMaxDaysHint" class="muted-note">Batas durasi mengikuti tier member</span>
                            </div>
                            <span class="field-error" id="err-simDuration"></span>
                        </div>

                        <!-- Dark Obsidian Invoice Summary Card -->
                        <div class="receipt-box" aria-live="polite">
                            <div class="receipt-box__header">
                                <span>INVOICE POLIMORFIK</span>
                                <code id="receiptClassTrace">User &times; MediaItem</code>
                            </div>

                            <dl class="receipt-lines">
                                <div class="receipt-line">
                                    <dt id="receiptBaseLabel">Biaya Sewa Pokok</dt>
                                    <dd id="receiptBaseValue" class="mono-text">Rp 0</dd>
                                </div>
                                <div class="receipt-line">
                                    <dt id="receiptServiceLabel">Biaya Layanan Media</dt>
                                    <dd id="receiptServiceValue" class="mono-text">Rp 0</dd>
                                </div>
                                <div class="receipt-line receipt-line--discount">
                                    <dt id="receiptDiscountLabel">Diskon Tier Member</dt>
                                    <dd id="receiptDiscountValue" class="mono-text">-Rp 0</dd>
                                </div>
                                <div class="receipt-line receipt-line--dates">
                                    <dt>Estimasi Jatuh Tempo</dt>
                                    <dd id="receiptDueDate" class="mono-text">-</dd>
                                </div>
                            </dl>

                            <div class="receipt-total">
                                <span>TOTAL PEMBAYARAN</span>
                                <strong id="receiptTotalValue" class="receipt-total__amount">Rp 0</strong>
                            </div>
                        </div>

                        <div id="simValidationAlert" class="workbench-alert" hidden></div>

                        <button type="submit" id="btnSubmitRental" class="btn btn--primary btn--full">
                            Konfirmasi &amp; Sewa E-Book
                        </button>
                    </form>
                </div>
            </aside>
        </div>
    </main>

    <!-- Modal Detail E-Book (<dialog>) -->
    <dialog id="bookDetailModal" class="editorial-modal" aria-labelledby="modalBookTitle">
        <div class="editorial-modal__inner">
            <div class="editorial-modal__header">
                <span id="modalBookType" class="badge badge--type">Subclass Media</span>
                <button type="button" id="btnCloseModal" class="editorial-modal__close" aria-label="Tutup dialog">&times;</button>
            </div>
            <div class="editorial-modal__body">
                <p id="modalBookCategory" class="section-kicker"></p>
                <h3 id="modalBookTitle" class="editorial-modal__title"></h3>
                <p id="modalBookAuthor" class="editorial-modal__author"></p>
                <p id="modalBookSynopsis" class="editorial-modal__synopsis"></p>

                <dl class="modal-specs-grid">
                    <div>
                        <dt>Kode Katalog &amp; ISBN</dt>
                        <dd id="modalBookIsbn" class="mono-text"></dd>
                    </div>
                    <div>
                        <dt>Format &amp; Ukuran File</dt>
                        <dd id="modalBookFormat" class="mono-text"></dd>
                    </div>
                    <div>
                        <dt>Metadata Subclass</dt>
                        <dd id="modalBookTech"></dd>
                    </div>
                    <div>
                        <dt>Tarif Harian &amp; Layanan</dt>
                        <dd id="modalBookRates" class="mono-text"></dd>
                    </div>
                </dl>
            </div>
            <div class="editorial-modal__footer">
                <button type="button" id="btnModalSelectBook" class="btn btn--primary">Gunakan di Kalkulator Sewa</button>
            </div>
        </div>
    </dialog>

    <!-- Semantic Footer -->
    <footer class="site-footer">
        <div class="container site-footer__inner">
            <div>
                <strong>Arkadia Digital Library Platform</strong>
                <p>Prototipe UTS Pemrograman Web Ganjil 2026/2027 &bull; Program Studi Informatika — Fakultas Teknik UMM</p>
            </div>
            <div class="site-footer__meta">
                <span>HTML5 Semantik &bull; Modern CSS Grid &bull; Vanilla ES6 JS &bull; PHP <?= htmlspecialchars(PHP_VERSION) ?> OOP</span>
            </div>
        </div>
    </footer>

    <!-- Embedded Initial Data for Instant Client-Side Interactivity -->
    <script>
        window.ARKADIA_STATE = {
            catalog: <?= json_encode($clientCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            members: <?= json_encode($clientMembers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
