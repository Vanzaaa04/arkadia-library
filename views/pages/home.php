<?php

declare(strict_types=1);

use App\Support\ViewHelper;
?>
<!-- ===================== HALAMAN: BERANDA ===================== -->
<?php
$teamMembers = [
    [
        'id' => 'faris',
        'name' => 'Faris Ramadhan Riyanto',
        'nim' => '202410370110402',
        'role' => 'Anggota',
        'is_leader' => false,
        'focus' => 'Modular Views & UI Component Architecture',
        'photo' => 'assets/img/team/faris.jpg',
    ],
    [
        'id' => 'ariel',
        'name' => 'Ariel Ardiansyah',
        'nim' => '202410370110434',
        'role' => 'Ketua Tim',
        'is_leader' => true,
        'focus' => 'Lead Full-Stack & PHP 8.x OOP Architecture',
        'photo' => 'assets/img/team/ariel.jpg',
    ],
    [
        'id' => 'irgi',
        'name' => 'Lalu Irgi Nabil Farhan',
        'nim' => '202410370110404',
        'role' => 'Anggota',
        'is_leader' => false,
        'focus' => 'Catalog Data Modeling, E-Reader & QA',
        'photo' => 'assets/img/team/irgi.jpg',
    ],
];
$deckSlots = ['left', 'center', 'right'];
$initialCenterIdx = count($heroBooks) >= 3 ? 1 : 0;
$initialBook = $heroBooks[$initialCenterIdx] ?? ($heroBooks[0] ?? null);
?>
<section id="page-home" class="page <?= $tab === 'home' ? 'is-active' : '' ?>" aria-labelledby="homeTitle">
    <!-- Full-bleed Espresso Brand Hero -->
    <div class="hero-banner">
        <div class="container hero-banner__inner">
            <div class="hero-banner__copy">
                <?php if ($currentUser !== null): ?>
                    <span class="hero-eyebrow"><span class="live-dot" aria-hidden="true"></span>Halo, <?= ViewHelper::e(ViewHelper::firstName($currentUser->getName())) ?> &middot; <?= ViewHelper::e($currentUser->getTierLabel()) ?></span>
                <?php else: ?>
                    <span class="hero-eyebrow"><span class="live-dot" aria-hidden="true"></span>Pilihan Editor &middot; Perpustakaan Digital Kampus</span>
                <?php endif; ?>
                <h1 id="homeTitle">Baca lebih banyak, <span class="hero-accent hero-rotator" id="heroRotator" data-phrases="bayar sesuai hari.|akses instan 24/7.|hemat hingga 30%."><span class="hero-rotator__text">bayar sesuai hari.</span></span></h1>
                <p class="hero-lead">
                    Sewa buku teknologi, desain, dan riset akademik per hari tanpa harus membeli penuh.
                    Diskon hingga 30% aktif otomatis untuk Anggota Premium dan Dosen.
                </p>
                <div class="hero-cta">
                    <button type="button" class="btn btn--primary btn--lg hero-cta__primary btn--magnetic" data-goto="catalog">
                        Jelajahi Katalog
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </button>
                    <?php if ($currentUser === null): ?>
                        <button type="button" class="btn btn--outline-light btn--lg btn--magnetic" data-open-auth="register">Daftar Anggota</button>
                    <?php else: ?>
                        <button type="button" class="btn btn--outline-light btn--lg btn--magnetic" data-goto="loans">Pinjaman Saya (<?= count($myActiveLoans) ?>)</button>
                    <?php endif; ?>
                </div>
                <dl class="hero-metrics">
                    <div><dt>Koleksi Kurasi</dt><dd><span class="js-count" data-count="<?= (int) $metrics['total_titles'] ?>"><?= (int) $metrics['total_titles'] ?></span> Judul</dd></div>
                    <div><dt>Anggota Aktif</dt><dd><span class="js-count" data-count="<?= (int) $metrics['total_members'] ?>"><?= (int) $metrics['total_members'] ?></span> Akun</dd></div>
                    <div><dt>Sedang Dipinjam</dt><dd><span class="js-count" data-count="<?= (int) $metrics['active_loans'] ?>"><?= (int) $metrics['active_loans'] ?></span> Buku</dd></div>
                </dl>
            </div>

            <div class="hero-stage" id="heroStage">
                <div class="hero-mode-switch" role="tablist" aria-label="Tampilan sorotan beranda">
                    <button type="button" class="hero-mode-btn is-active" data-hero-mode="team" role="tab" aria-selected="true">Tim Penyusun</button>
                    <button type="button" class="hero-mode-btn" data-hero-mode="books" role="tab" aria-selected="false">Buku Sorotan</button>
                </div>

                <!-- Mode 1: 3D Hero Team Portrait Deck -->
                <div id="heroTeamPane" class="hero-pane" data-hero-pane="team">
                    <div class="hero-showcase hero-showcase--team" id="heroTeamShowcase" role="region" aria-label="Sorotan foto tim penyusun">
                        <span class="hero-showcase__glow" aria-hidden="true"></span>
                        <?php foreach ($teamMembers as $i => $tm): ?>
                            <button type="button"
                                    class="hero-showcase__item hero-showcase__item--team <?= $tm['is_leader'] ? 'is-leader' : '' ?>"
                                    data-hero-team-idx="<?= $i ?>"
                                    data-slot="<?= $deckSlots[$i] ?? 'center' ?>"
                                    data-member-name="<?= ViewHelper::e($tm['name']) ?>"
                                    data-member-nim="<?= ViewHelper::e($tm['nim']) ?>"
                                    data-member-role="<?= ViewHelper::e($tm['role']) ?>"
                                    aria-label="<?= ViewHelper::e($tm['role'] . ' — ' . $tm['name']) ?>">
                                <span class="hero-float hero-float--<?= $i + 1 ?>">
                                    <span class="hero-portrait">
                                        <img src="<?= ViewHelper::e($tm['photo']) ?>" alt="Foto <?= ViewHelper::e($tm['name']) ?>">
                                        <span class="hero-portrait__tag <?= $tm['is_leader'] ? 'hero-portrait__tag--leader' : '' ?>"><?= ViewHelper::e($tm['role']) ?></span>
                                    </span>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="hero-deck-bar hero-deck-bar--team">
                        <div class="hero-deck-info" id="heroTeamInfo">
                            <strong id="heroTeamTitle">Tim Penyusun Arkadia</strong>
                            <span id="heroTeamMeta">Klik foto untuk lihat Nama, Role &amp; NIM</span>
                        </div>
                        <button type="button" id="heroTeamCopy" class="team-copy-btn" data-copy-nim="" hidden>Salin NIM</button>
                        <div class="hero-deck-dots" role="tablist" aria-label="Pilih anggota tim">
                            <?php foreach ($teamMembers as $i => $tm): ?>
                                <button type="button"
                                        class="hero-deck-dot <?= $i === 1 ? 'is-active' : '' ?>"
                                        data-hero-team-dot="<?= $i ?>"
                                        role="tab"
                                        aria-selected="<?= $i === 1 ? 'true' : 'false' ?>"
                                        aria-label="Pilih <?= ViewHelper::e($tm['name']) ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Mode 2: 3D Hero Book Spotlight Deck -->
                <div id="heroBooksPane" class="hero-pane" data-hero-pane="books" hidden>
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
    </div>

    <!-- Editorial Infinite Marquee Strip -->
    <div class="editorial-marquee" aria-hidden="true">
        <div class="editorial-marquee__track">
            <?php for ($mLoop = 0; $mLoop < 2; $mLoop++): ?>
                <div class="editorial-marquee__group">
                    <span>PHP 8 OOP POLIMORFISME</span><i class="marquee-sep"></i>
                    <span>DISKON ANGGOTA PREMIUM 20%</span><i class="marquee-sep"></i>
                    <span>DISKON DOSEN &amp; PENELITI 30%</span><i class="marquee-sep"></i>
                    <span>MINI WEB E-READER 3 MODE KERTAS</span><i class="marquee-sep"></i>
                    <span>LISENSI READIUM LCP DRM</span><i class="marquee-sep"></i>
                    <span>STRUK DIGITAL &amp; CETAK PDF</span><i class="marquee-sep"></i>
                    <span>PERPANJANG SEWA +3 HARI</span><i class="marquee-sep"></i>
                    <span>SEWA FLEKSIBEL PER HARI</span><i class="marquee-sep"></i>
                </div>
            <?php endfor; ?>
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

        <!-- Tim Penyusun (Interactive Expanding Portrait Gallery) -->
        <section id="tim-penyusun" class="section-block" data-reveal aria-labelledby="teamTitle">
            <div class="section-head section-head--row">
                <div>
                    <span class="eyebrow">Informatika UMM &middot; Pemrograman Web</span>
                    <h2 id="teamTitle">Tim Penyusun Arkadia</h2>
                    <p>Klik pada foto untuk membuka identitas lengkap, NIM, dan peran masing-masing penyusun.</p>
                </div>
                <button type="button" id="btnToggleAllTeam" class="btn btn--ghost btn--sm" aria-pressed="false">
                    Tampilkan semua profil
                </button>
            </div>

            <div class="team-showcase" id="teamShowcase" role="group" aria-label="Galeri interaktif tim penyusun">
                <?php foreach ($teamMembers as $tm): ?>
                    <article class="team-panel <?= $tm['is_leader'] ? 'team-panel--leader' : '' ?>"
                             data-team-panel="<?= ViewHelper::e($tm['id']) ?>"
                             tabindex="0"
                             role="button"
                             aria-expanded="false"
                             aria-label="Lihat profil <?= ViewHelper::e($tm['name']) ?>">
                        <img src="<?= ViewHelper::e($tm['photo']) ?>"
                             alt="Foto <?= ViewHelper::e($tm['name']) ?>"
                             class="team-panel__img"
                             loading="lazy">
                        <div class="team-panel__shade" aria-hidden="true"></div>

                        <div class="team-panel__top">
                            <span class="team-badge <?= $tm['is_leader'] ? 'team-badge--leader' : '' ?>">
                                <?= ViewHelper::e($tm['role']) ?>
                            </span>
                            <span class="team-panel__trigger" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </div>

                        <div class="team-panel__hint">
                            <span>Klik untuk lihat Nama &amp; NIM</span>
                        </div>

                        <div class="team-panel__drawer">
                            <span class="team-panel__role-kicker">
                                <?= ViewHelper::e($tm['role']) ?> &middot; Informatika UMM
                            </span>
                            <h3 class="team-panel__name"><?= ViewHelper::e($tm['name']) ?></h3>
                            <div class="team-panel__nim-row">
                                <code class="team-panel__nim">NIM <?= ViewHelper::e($tm['nim']) ?></code>
                                <button type="button"
                                        class="team-copy-btn"
                                        data-copy-nim="<?= ViewHelper::e($tm['nim']) ?>"
                                        title="Salin NIM <?= ViewHelper::e($tm['nim']) ?>">
                                    Salin
                                </button>
                            </div>
                            <p class="team-panel__focus"><?= ViewHelper::e($tm['focus']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
