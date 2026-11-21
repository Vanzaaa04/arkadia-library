<?php

declare(strict_types=1);

use App\Support\ViewHelper;
?>
<!-- ===================== MODAL: DETAIL BUKU ===================== -->
<dialog id="bookModal" class="modal" aria-labelledby="bmTitle">
    <div class="modal__inner modal__inner--book">
        <button type="button" class="modal__close" data-close-modal aria-label="Tutup">&times;</button>
        <div class="book-detail">
            <div id="bmCover" class="cover cover--lg">
                <span class="cover__motif"></span><span class="cover__rule"></span>
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
                    <div class="book-detail__actions">
                        <button type="button" id="bmReadBtn" class="btn btn--ghost btn--lg">Baca cuplikan</button>
                        <button type="button" id="bmRentBtn" class="btn btn--primary btn--lg">Sewa buku ini</button>
                    </div>
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
        <input type="hidden" name="member_id" id="rentMember" value="<?= $currentUser !== null ? ViewHelper::e($currentUser->getId()) : '' ?>">

        <div class="rent-head">
            <div id="rmCover" class="cover cover--sm">
                <span class="cover__motif"></span><span class="cover__rule"></span>
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
                <span id="rentUserAvatar" class="avatar avatar--<?= $currentUser !== null ? ViewHelper::e($currentUser->getTierCode()) : 'regular' ?>">
                    <?= $currentUser !== null ? ViewHelper::e(ViewHelper::memberInitials($currentUser->getName())) : 'A' ?>
                </span>
                <div class="rent-account__info">
                    <strong id="rentUserName"><?= $currentUser !== null ? ViewHelper::e($currentUser->getName()) : '-' ?></strong>
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
                        <button type="button" id="dayMinus" aria-label="Kurangi hari">&minus;</button>
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
                <p class="bill__note">Dihitung server PHP &middot; <code id="billTrace">User &times; MediaItem</code></p>
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
                <input type="hidden" name="return_tab" id="authLoginReturn" value="<?= ViewHelper::e($tab) ?>">
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
                        <input type="hidden" name="member_id" value="<?= ViewHelper::e($dm->getId()) ?>">
                        <input type="hidden" name="return_tab" class="js-return-tab" value="<?= ViewHelper::e($tab) ?>">
                        <input type="hidden" name="pending_book" class="js-pending-book" value="">
                        <button type="submit" class="demo-btn demo-btn--<?= ViewHelper::e($dm->getTierCode()) ?>">
                            <span class="avatar avatar--xs avatar--<?= ViewHelper::e($dm->getTierCode()) ?>"><?= ViewHelper::e(ViewHelper::memberInitials($dm->getName())) ?></span>
                            <span class="demo-btn__meta">
                                <strong><?= ViewHelper::e($dm->getTierLabel()) ?></strong>
                                <small><?= ViewHelper::e(ViewHelper::firstName($dm->getName())) ?> &middot; Diskon <?= (int) round($dm->getDiscountRate() * 100) ?>%</small>
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
                <input type="hidden" name="return_tab" id="authRegReturn" value="<?= ViewHelper::e($tab) ?>">
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
                                <input type="radio" name="tier" value="<?= ViewHelper::e($sample->getTierCode()) ?>" <?= $i === 1 ? 'checked' : '' ?>>
                                <span>
                                    <strong><?= ViewHelper::e($sample->getTierLabel()) ?></strong>
                                    <small>Diskon <?= (int) round($sample->getDiscountRate() * 100) ?>% &middot; maks <?= $sample->getMaxBorrowDays() ?> hari</small>
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

<!-- ===================== MODAL: MINI WEB E-READER ===================== -->
<dialog id="readerModal" class="modal modal--reader" aria-labelledby="rdTitle">
    <div class="modal__inner reader-shell" id="readerShell" data-paper="light">
        <header class="reader-bar">
            <div class="reader-bar__title">
                <div class="reader-bar__top">
                    <span id="rdModeBadge" class="reader-badge">Cuplikan</span>
                    <strong id="rdTitle">Judul E-Book</strong>
                </div>
                <small id="rdAuthor">Penulis</small>
            </div>
            <div class="reader-controls">
                <div class="reader-font-group" role="group" aria-label="Ukuran huruf">
                    <button type="button" id="rdFontDown" aria-label="Perkecil huruf">A&minus;</button>
                    <span id="rdFontSize">16px</span>
                    <button type="button" id="rdFontUp" aria-label="Perbesar huruf">A+</button>
                </div>
                <div class="reader-themes" role="group" aria-label="Warna kertas">
                    <button type="button" class="reader-theme-btn is-active" data-paper-btn="light">Terang</button>
                    <button type="button" class="reader-theme-btn" data-paper-btn="sepia">Sepia</button>
                    <button type="button" class="reader-theme-btn" data-paper-btn="dark">Gelap</button>
                </div>
                <button type="button" class="reader-close" data-close-modal aria-label="Tutup">&times;</button>
            </div>
        </header>

        <div class="reader-progress" aria-hidden="true">
            <span id="rdProgressFill"></span>
        </div>

        <div class="reader-viewport">
            <article class="reader-page" id="rdContent">
                <span class="reader-kicker" id="rdChapterNum">Bab 1</span>
                <h2 id="rdChapterTitle">Judul Bab</h2>
                <div class="reader-copy" id="rdBody"></div>
                <aside class="reader-cta" id="rdCta" hidden>
                    <div>
                        <strong>Pratinjau terbatas selesai.</strong>
                        <p>Sewa e-book ini untuk membuka akses penuh ke seluruh bab, unduhan DRM, dan catatan kaki lengkap.</p>
                    </div>
                    <button type="button" id="rdRentNow" class="btn btn--primary btn--sm">Sewa buku ini</button>
                </aside>
            </article>
        </div>

        <footer class="reader-foot">
            <button type="button" id="rdPrev" class="btn btn--ghost btn--sm">&larr; Bab sebelumnya</button>
            <span id="rdPageInfo" class="reader-page-info">Bab 1 dari 3 &middot; 33%</span>
            <button type="button" id="rdNext" class="btn btn--primary btn--sm">Bab berikutnya &rarr;</button>
        </footer>
    </div>
</dialog>

<!-- ===================== MODAL: STRUK DIGITAL & CETAK PDF ===================== -->
<dialog id="receiptModal" class="modal modal--receipt" aria-labelledby="rcCode">
    <div class="modal__inner modal__inner--receipt">
        <button type="button" class="modal__close no-print" data-close-modal aria-label="Tutup">&times;</button>
        <div class="receipt-sheet" id="receiptSheet">
            <div class="receipt-head">
                <div>
                    <span class="receipt-brand">ARKADIA</span>
                    <p class="receipt-sub">Bukti Transaksi Sewa Digital</p>
                </div>
                <span id="rcStatus" class="pill pill--success">Selesai</span>
            </div>

            <dl class="receipt-meta">
                <div><dt>Kode Invoice</dt><dd class="mono" id="rcCode">-</dd></div>
                <div><dt>Token Lisensi DRM</dt><dd><code id="rcToken">-</code></dd></div>
                <div><dt>Penyewa</dt><dd id="rcMember">-</dd></div>
                <div><dt>Jenis Anggota</dt><dd id="rcTier">-</dd></div>
                <div><dt>Tanggal Pinjam</dt><dd id="rcBorrowDate">-</dd></div>
                <div><dt id="rcDueDateLabel">Jatuh tempo</dt><dd id="rcDueDate">-</dd></div>
            </dl>

            <div class="receipt-item">
                <span class="receipt-item__kicker">Koleksi digital disewa</span>
                <strong id="rcBookTitle">-</strong>
                <small class="muted" id="rcBookMeta">-</small>
            </div>

            <div class="bill receipt-bill">
                <div class="bill__row"><span>Biaya sewa pokok</span><span id="rcBase">-</span></div>
                <div class="bill__row"><span>Biaya layanan</span><span id="rcService">-</span></div>
                <div class="bill__row bill__row--discount"><span>Diskon anggota</span><span id="rcDiscount">-</span></div>
                <div class="bill__total"><span>Total dibayar</span><strong id="rcTotal">-</strong></div>
            </div>

            <p class="receipt-note">Struk ini dihasilkan secara otomatis oleh mesin transaksi PHP OOP Arkadia. Simpan kode token DRM sebagai bukti lisensi baca resmi.</p>
        </div>
        <div class="rent-foot no-print">
            <button type="button" class="btn btn--ghost" data-close-modal>Tutup</button>
            <button type="button" id="btnPrintReceipt" class="btn btn--primary">Cetak / Simpan PDF</button>
        </div>
    </div>
</dialog>
