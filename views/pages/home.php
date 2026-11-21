<?php

declare(strict_types=1);

use App\Support\ViewHelper;
?>
<!-- ===================== HALAMAN: BERANDA ===================== -->
<section id="page-home" class="page <?= $tab === 'home' ? 'is-active' : '' ?>" aria-labelledby="homeTitle">
    <!-- Full-bleed Espresso Brand Hero -->
    <div class="hero-banner">
        <div class="container hero-banner__inner">
            <div class="hero-banner__copy">
                <?php if ($currentUser !== null): ?>
                    <span class="hero-eyebrow">Halo, <?= ViewHelper::e(ViewHelper::firstName($currentUser->getName())) ?> &middot; <?= ViewHelper::e($currentUser->getTierLabel()) ?></span>
                <?php else: ?>
                    <span class="hero-eyebrow">Pilihan Editor &middot; Perpustakaan Digital Kampus</span>
                <?php endif; ?>
                <h1 id="homeTitle">Baca lebih banyak, <span class="hero-accent">bayar sesuai hari.</span></h1>
                <p class="hero-lead">
                    Sewa buku teknologi, desain, dan riset akademik per hari tanpa harus membeli penuh.
                    Diskon hingga 30% aktif otomatis untuk Anggota Premium dan Dosen.
                </p>
                <div class="hero-cta">
                    <button type="button" class="btn btn--primary btn--lg hero-cta__primary" data-goto="catalog">
                        Jelajahi Katalog
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </button>
                    <?php if ($currentUser === null): ?>
                        <button type="button" class="btn btn--outline-light btn--lg" data-open-auth="register">Daftar Anggota</button>
                    <?php else: ?>
                        <button type="button" class="btn btn--outline-light btn--lg" data-goto="loans">Pinjaman Saya (<?= count($myActiveLoans) ?>)</button>
                    <?php endif; ?>
                </div>
                <dl class="hero-metrics">
                    <div><dt>Koleksi Kurasi</dt><dd><span class="js-count" data-count="<?= (int) $metrics['total_titles'] ?>"><?= (int) $metrics['total_titles'] ?></span> Judul</dd></div>
                    <div><dt>Anggota Aktif</dt><dd><span class="js-count" data-count="<?= (int) $metrics['total_members'] ?>"><?= (int) $metrics['total_members'] ?></span> Akun</dd></div>
                    <div><dt>Sedang Dipinjam</dt><dd><span class="js-count" data-count="<?= (int) $metrics['active_loans'] ?>"><?= (int) $metrics['active_loans'] ?></span> Buku</dd></div>
                </dl>
            </div>

            <?php
            $deckSlots = ['left', 'center', 'right'];
            $initialCenterIdx = count($heroBooks) >= 3 ? 1 : 0;
            $initialBook = $heroBooks[$initialCenterIdx] ?? ($heroBooks[0] ?? null);
            ?>
            <div class="hero-stage" id="heroStage">
                <div class="hero-showcase" id="heroShowcase" role="region" aria-label="Koleksi buku sorotan">
                    <span class="hero-showcase__glow" aria-hidden="true"></span>
                    <?php foreach ($heroBooks as $i => $hb): ?>
                        <button type="button"
                                class="hero-showcase__item"
                                data-hero-idx="<?= $i ?>"
                                data-slot="<?= $deckSlots[$i] ?? 'center' ?>"
                                data-hero-book="<?= ViewHelper::e($hb->getId()) ?>"
                                data-hero-title="<?= ViewHelper::e($hb->getTitle()) ?>"
                                data-hero-price="<?= ViewHelper::e(ViewHelper::formatRupiah($hb->getDailyRate())) ?>/hari"
                                aria-label="Sorotan: <?= ViewHelper::e($hb->getTitle()) ?>">
                            <span class="hero-float hero-float--<?= $i + 1 ?>">
                                <?= ViewHelper::renderCover($hb, 'cover--hero') ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php if ($initialBook !== null): ?>
                    <div class="hero-deck-bar">
                        <button type="button" class="hero-deck-info" id="heroDeckOpen">
                            <strong id="heroDeckTitle"><?= ViewHelper::e($initialBook->getTitle()) ?></strong>
                            <span id="heroDeckMeta"><?= ViewHelper::e(ViewHelper::formatRupiah($initialBook->getDailyRate())) ?>/hari &middot; Klik sampul untuk detail</span>
                        </button>
                        <div class="hero-deck-dots" role="tablist" aria-label="Pilih buku sorotan">
                            <?php foreach ($heroBooks as $i => $hb): ?>
                                <button type="button"
                                        class="hero-deck-dot <?= $i === $initialCenterIdx ? 'is-active' : '' ?>"
                                        data-hero-dot="<?= $i ?>"
                                        role="tab"
                                        aria-selected="<?= $i === $initialCenterIdx ? 'true' : 'false' ?>"
                                        aria-label="Pilih <?= ViewHelper::e($hb->getTitle()) ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Horizontal Quick-Browse Category Strip -->
    <div class="cat-strip">
        <div class="container cat-strip__inner">
            <span class="cat-strip__label">Kategori:</span>
            <button type="button" class="cat-pill" data-jump-category="all">Semua Koleksi</button>
            <?php foreach ($categories as $cat): ?>
                <button type="button" class="cat-pill" data-jump-category="<?= ViewHelper::e($cat) ?>"><?= ViewHelper::e($cat) ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="container home-body">
        <!-- Koleksi Pilihan -->
        <section class="section-block" data-reveal aria-labelledby="featuredTitle">
            <div class="section-head section-head--row">
                <div>
                    <h2 id="featuredTitle">Koleksi Pilihan Minggu Ini</h2>
                    <p>Judul terkurasi yang paling banyak dibaca oleh mahasiswa dan dosen.</p>
                </div>
                <button type="button" class="link-btn" data-goto="catalog">Lihat semua <?= count($ebooks) ?> buku &rarr;</button>
            </div>
            <div class="book-grid">
                <?php foreach ($featuredBooks as $fb): ?>
                    <?= ViewHelper::renderBookCard($fb) ?>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Cara Pakai Editorial Hairline Steps -->
        <section id="cara-pakai" class="section-block" data-reveal aria-labelledby="howTitle">
            <div class="section-head">
                <h2 id="howTitle">Mulai Membaca dalam 3 Langkah</h2>
                <p>Jelajah katalog secara bebas, lalu masuk atau daftar saat ingin menyewa.</p>
            </div>
            <ol class="steps-editorial" data-reveal>
                <li>
                    <span class="step-num">01</span>
                    <h3>Daftar atau Masuk</h3>
                    <p>Pilih tipe <strong>Anggota Reguler</strong>, <strong>Premium</strong>, atau <strong>Dosen</strong>—atau gunakan tombol 1-klik akun demo.</p>
                </li>
                <li>
                    <span class="step-num">02</span>
                    <h3>Pilih Buku &amp; Durasi</h3>
                    <p>Klik <strong>Sewa</strong> di buku mana pun dan tentukan lama sewa. Potongan biaya langsung dihitung oleh sistem.</p>
                </li>
                <li>
                    <span class="step-num">03</span>
                    <h3>Baca &amp; Kembalikan</h3>
                    <p>Pantau masa pinjaman di menu <strong>Peminjaman</strong> dan klik <strong>Kembalikan</strong> kapan saja saat selesai.</p>
                </li>
            </ol>
        </section>

        <!-- Paket Keanggotaan -->
        <section class="section-block" data-reveal aria-labelledby="tierTitle">
            <div class="section-head">
                <h2 id="tierTitle">Paket Keanggotaan</h2>
                <p>Hak diskon, batas durasi, dan kuota pinjaman mengikuti tipe akun Anda.</p>
            </div>
            <div class="tier-grid" data-reveal>
                <?php foreach ($tierSamples as $sample): ?>
                    <?php $isPopular = $sample->getTierCode() === 'premium'; ?>
                    <article class="tier-card tier-card--<?= ViewHelper::e($sample->getTierCode()) ?>">
                        <div class="tier-card__top">
                            <span class="tier-card__label"><?= ViewHelper::e($sample->getTierLabel()) ?></span>
                            <?php if ($isPopular): ?><span class="tier-badge">Paling Populer</span><?php endif; ?>
                        </div>
                        <p class="tier-card__big">
                            <?php if ($sample->getDiscountRate() > 0): ?>
                                Diskon <?= (int) round($sample->getDiscountRate() * 100) ?>%
                            <?php else: ?>
                                Tarif Standar
                            <?php endif; ?>
                        </p>
                        <ul class="tier-card__list">
                            <li>Maksimal sewa <strong><?= $sample->getMaxBorrowDays() ?> hari</strong> per judul</li>
                            <li>Pinjam hingga <strong><?= $sample->getMaxConcurrentLoans() ?> buku</strong> bersamaan</li>
                            <li><?= $sample->waivesServiceFee() ? '<strong>Gratis</strong> biaya layanan setiap transaksi' : 'Biaya layanan standar berlaku' ?></li>
                        </ul>
                        <?php if ($currentUser === null): ?>
                            <button type="button"
                                    class="btn <?= $isPopular ? 'btn--primary' : 'btn--ghost' ?> btn--full"
                                    data-open-auth="register"
                                    data-preselect-tier="<?= ViewHelper::e($sample->getTierCode()) ?>">
                                Daftar <?= ViewHelper::e($sample->getTierLabel()) ?>
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
