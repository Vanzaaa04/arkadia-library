<?php

declare(strict_types=1);

use App\Support\ViewHelper;
?>
<!-- ===================== HALAMAN: KATALOG ===================== -->
<section id="page-catalog" class="page container page-shell <?= $tab === 'catalog' ? 'is-active' : '' ?>" aria-labelledby="catalogTitle">
    <div class="page-head">
        <div>
            <h1 id="catalogTitle">Katalog E-Book</h1>
            <p>Menampilkan <strong id="catalogCount"><?= count($ebooks) ?></strong> dari <?= count($ebooks) ?> judul tersedia.</p>
        </div>
        <?php if ($currentUser !== null): ?>
            <div class="account-chip-inline">
                <span class="avatar avatar--xs avatar--<?= ViewHelper::e($currentUser->getTierCode()) ?>"><?= ViewHelper::e(ViewHelper::memberInitials($currentUser->getName())) ?></span>
                <span>Menyewa sebagai <strong><?= ViewHelper::e($currentUser->getName()) ?></strong> &middot; <?= ViewHelper::e($currentUser->getTierLabel()) ?></span>
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
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
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
            <span>Tersedia saja</span>
        </label>
        <div class="view-toggle" role="group" aria-label="Tampilan katalog">
            <button type="button" id="btnViewGrid" class="view-btn is-active" aria-pressed="true" title="Tampilan Grid">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            </button>
            <button type="button" id="btnViewList" class="view-btn" aria-pressed="false" title="Tampilan Daftar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
            </button>
        </div>
    </div>

    <div class="chips" role="group" aria-label="Filter kategori">
        <button type="button" class="chip is-active" data-cat="all">Semua</button>
        <?php foreach ($categories as $cat): ?>
            <button type="button" class="chip" data-cat="<?= ViewHelper::e($cat) ?>"><?= ViewHelper::e($cat) ?></button>
        <?php endforeach; ?>
    </div>

    <div id="catalogGrid" class="book-grid">
        <?php foreach ($ebooks as $book): ?>
            <?= ViewHelper::renderBookCard($book) ?>
        <?php endforeach; ?>
    </div>

    <div id="catalogEmpty" class="empty" hidden>
        <div class="empty__icon" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </div>
        <h3>Buku tidak ditemukan</h3>
        <p>Coba kata kunci lain atau hapus filter pencarian.</p>
        <button type="button" id="btnResetFilter" class="btn btn--ghost">Hapus semua filter</button>
    </div>
</section>
