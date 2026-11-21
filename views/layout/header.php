<?php

declare(strict_types=1);

use App\Support\ViewHelper;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Arkadia — Platform penyewaan e-book digital kampus dengan pilihan tarif fleksibel per hari.">
    <title>ARKADIA — Digital E-Book Lending &amp; Storefront</title>
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
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body>
    <div class="scroll-progress" aria-hidden="true"><span id="scrollProgressBar"></span></div>

    <!-- ===================== HEADER & NAVIGASI ===================== -->
    <header class="topbar">
        <div class="container topbar__inner">
            <a href="index.php" class="brand" data-page-link="home">
                <span class="brand__logo" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"/></svg>
                </span>
                <span class="brand__text">
                    <strong>ARKADIA</strong>
                    <small>Digital Library</small>
                </span>
            </a>

            <nav class="nav" id="mainNav" aria-label="Menu utama">
                <button type="button" class="<?= ViewHelper::navClass($tab, 'home') ?>" data-page="home">
                    <span>Beranda</span>
                </button>
                <button type="button" class="<?= ViewHelper::navClass($tab, 'catalog') ?>" data-page="catalog">
                    <span>Katalog</span>
                </button>
                <button type="button" class="<?= ViewHelper::navClass($tab, 'loans') ?>" data-page="loans">
                    <span>Peminjaman</span>
                    <?php if ($loanBadgeCount > 0): ?><span class="nav-count"><?= $loanBadgeCount ?></span><?php endif; ?>
                </button>
                <button type="button" class="<?= ViewHelper::navClass($tab, 'members') ?>" data-page="members">
                    <span>Anggota</span>
                </button>
                <button type="button" class="<?= ViewHelper::navClass($tab, 'manage') ?>" data-page="manage">
                    <span>Tambah Buku</span>
                </button>
                <button type="button" class="<?= ViewHelper::navClass($tab, 'system') ?>" data-page="system">
                    <span>Info Sistem</span>
                </button>
                <span class="nav-indicator" id="navIndicator" aria-hidden="true"></span>
            </nav>

            <div class="topbar__actions">
                <?php if ($currentUser === null): ?>
                    <button type="button" class="btn btn--text btn--sm" data-open-auth="login">Masuk</button>
                    <button type="button" id="btnOpenAuth" class="btn btn--dark btn--sm" data-open-auth="register">Daftar</button>
                <?php else: ?>
                    <button type="button" class="user-pill" data-open-auth="login" title="Ganti akun anggota">
                        <span class="avatar avatar--xs avatar--<?= ViewHelper::e($currentUser->getTierCode()) ?>"><?= ViewHelper::e(ViewHelper::memberInitials($currentUser->getName())) ?></span>
                        <span class="user-pill__text">
                            <strong><?= ViewHelper::e(ViewHelper::firstName($currentUser->getName())) ?></strong>
                            <small><?= ViewHelper::e($currentUser->getTierLabel()) ?></small>
                        </span>
                    </button>
                    <form method="POST" action="index.php" class="inline-form">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="icon-btn logout-btn" aria-label="Keluar dari akun" title="Keluar dari akun">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                        </button>
                    </form>
                <?php endif; ?>

                <button type="button" id="btnTheme" class="icon-btn" aria-label="Ganti tema terang/gelap" title="Ganti tema terang/gelap">
                    <svg class="icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    <svg class="icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>
                <form method="POST" action="index.php" class="inline-form" onsubmit="return confirm('Kembalikan semua data ke contoh awal?');">
                    <input type="hidden" name="action" value="reset_demo">
                    <button type="submit" class="icon-btn" aria-label="Kembalikan data contoh" title="Kembalikan data ke contoh awal">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Notifikasi (toast) -->
    <div id="toastStack" class="toast-stack" aria-live="polite">
        <?php if ($flash !== null): ?>
            <div class="toast toast--<?= ViewHelper::e((string) $flash['type']) ?>" role="status">
                <span class="toast__icon" aria-hidden="true">
                    <?php if ($flash['type'] === 'error'): ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <?php else: ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php endif; ?>
                </span>
                <div class="toast__body">
                    <strong><?= ViewHelper::e((string) $flash['title']) ?></strong>
                    <p><?= ViewHelper::e((string) $flash['message']) ?></p>
                </div>
                <button type="button" class="toast__close" aria-label="Tutup notifikasi">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <main class="main">
