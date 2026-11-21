<?php

declare(strict_types=1);

use App\Support\ViewHelper;
?>
<!-- ===================== HALAMAN: PEMINJAMAN ===================== -->
<section id="page-loans" class="page container page-shell <?= $tab === 'loans' ? 'is-active' : '' ?>" aria-labelledby="loansTitle">
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
            <div class="empty__icon" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
            </div>
            <h3>Belum ada peminjaman aktif</h3>
            <p>Sewa buku pertama dari katalog.</p>
            <button type="button" class="btn btn--primary" data-goto="catalog">Buka Katalog</button>
        </div>
    <?php else: ?>
        <div class="loan-list" id="activeLoanList">
            <?php foreach ($activeLoans as $loan): ?>
                <?php
                $loanBook = $manager->getEbookById($loan->getEbookId());
                $left = ViewHelper::daysUntil($loan->getDueDate());
                $dueClass = $left < 0 ? 'due--late' : ($left <= 3 ? 'due--soon' : 'due--ok');
                $dueText = $left < 0 ? 'Terlambat ' . abs($left) . ' hari' : ($left === 0 ? 'Jatuh tempo hari ini' : 'Sisa ' . $left . ' hari');
                $hiddenForMine = ($currentUser !== null && $loan->getMemberId() !== $currentUser->getId());
                ?>
                <article class="loan-card" data-loan-owner="<?= ViewHelper::e($loan->getMemberId()) ?>" <?= $hiddenForMine ? 'hidden' : '' ?>>
                    <div class="loan-card__cover">
                        <?= $loanBook !== null ? ViewHelper::renderCover($loanBook, 'cover--sm') : '' ?>
                    </div>
                    <div class="loan-card__info">
                        <h3><?= ViewHelper::e($loan->getEbookTitle()) ?></h3>
                        <p class="loan-card__who">
                            <span class="avatar avatar--xs"><?= ViewHelper::e(ViewHelper::memberInitials($loan->getMemberName())) ?></span>
                            <?= ViewHelper::e($loan->getMemberName()) ?> <span class="muted">&middot; <?= ViewHelper::e($loan->getMemberTier()) ?></span>
                        </p>
                        <ul class="loan-card__meta">
                            <li>Mulai <strong><?= ViewHelper::formatTanggal($loan->getBorrowDate()) ?></strong></li>
                            <li>Jatuh tempo <strong><?= ViewHelper::formatTanggal($loan->getDueDate()) ?></strong></li>
                            <li>Dibayar <strong><?= ViewHelper::formatRupiah($loan->getTotalCost()) ?></strong></li>
                        </ul>
                    </div>
                    <div class="loan-card__side">
                        <span class="due <?= $dueClass ?>"><?= ViewHelper::e($dueText) ?></span>
                        <div class="loan-actions">
                            <button type="button" class="btn btn--soft btn--sm" data-read-book="<?= ViewHelper::e($loan->getEbookId()) ?>" data-read-mode="full">Baca</button>
                            <button type="button" class="btn btn--ghost btn--sm" data-open-receipt="<?= ViewHelper::e($loan->getId()) ?>">Struk</button>
                            <form method="POST" action="index.php" class="inline-form">
                                <input type="hidden" name="action" value="extend_ebook">
                                <input type="hidden" name="transaction_id" value="<?= ViewHelper::e($loan->getId()) ?>">
                                <input type="hidden" name="extra_days" value="3">
                                <input type="hidden" name="return_tab" value="loans">
                                <button type="submit" class="btn btn--ghost btn--sm" title="Perpanjang masa sewa 3 hari">+3 hari</button>
                            </form>
                            <form method="POST" action="index.php" class="inline-form return-form">
                                <input type="hidden" name="action" value="return_ebook">
                                <input type="hidden" name="transaction_id" value="<?= ViewHelper::e($loan->getId()) ?>">
                                <input type="hidden" name="return_tab" value="loans">
                                <button type="submit" class="btn btn--success btn--sm">Kembalikan</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div id="emptyMyLoans" class="empty" <?= ($currentUser !== null && count($myActiveLoans) === 0) ? '' : 'hidden' ?>>
            <div class="empty__icon" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"/></svg>
            </div>
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
                    <th class="num">Struk</th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                <?php if (count($historyLoans) === 0): ?>
                    <tr><td colspan="6" class="muted center">Belum ada riwayat.</td></tr>
                <?php else: ?>
                    <?php foreach ($historyLoans as $h): ?>
                        <?php $hideHist = ($currentUser !== null && $h->getMemberId() !== $currentUser->getId()); ?>
                        <tr data-loan-owner="<?= ViewHelper::e($h->getMemberId()) ?>" <?= $hideHist ? 'hidden' : '' ?>>
                            <td>
                                <strong><?= ViewHelper::e($h->getEbookTitle()) ?></strong>
                                <small class="muted block-sm"><?= ViewHelper::e($h->getTransactionCode()) ?></small>
                            </td>
                            <td><?= ViewHelper::e($h->getMemberName()) ?></td>
                            <td><?= $h->getDurationDays() ?> hari</td>
                            <td><?= ViewHelper::formatTanggal($h->getReturnDate()) ?></td>
                            <td class="num"><strong><?= ViewHelper::formatRupiah($h->getTotalCost()) ?></strong></td>
                            <td class="num"><button type="button" class="link-btn small" data-open-receipt="<?= ViewHelper::e($h->getId()) ?>">Lihat struk</button></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="emptyMyHistoryRow" <?= ($currentUser !== null && count($myHistoryLoans) === 0) ? '' : 'hidden' ?>>
                        <td colspan="6" class="muted center">Belum ada riwayat pengembalian untuk akun ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
