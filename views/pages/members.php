<?php

declare(strict_types=1);

use App\Support\ViewHelper;
?>
<!-- ===================== HALAMAN: ANGGOTA ===================== -->
<section id="page-members" class="page container page-shell <?= $tab === 'members' ? 'is-active' : '' ?>" aria-labelledby="membersTitle">
    <div class="page-head">
        <div>
            <h1 id="membersTitle">Anggota</h1>
            <p>Daftar anggota terdaftar. Klik <strong>Masuk akun ini</strong> untuk berpindah akun kapan saja.</p>
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
                            <span class="avatar avatar--<?= ViewHelper::e($member->getTierCode()) ?>"><?= ViewHelper::e(ViewHelper::memberInitials($member->getName())) ?></span>
                            <div class="member-card__id">
                                <h3 title="<?= ViewHelper::e($member->getName()) ?>"><?= ViewHelper::e($member->getName()) ?></h3>
                                <p title="<?= ViewHelper::e($member->getInstitution()) ?>"><?= ViewHelper::e($member->getInstitution()) ?></p>
                            </div>
                        </div>

                        <div class="member-card__badges">
                            <span class="tier-pill tier-pill--<?= ViewHelper::e($member->getTierCode()) ?>"><?= ViewHelper::e($member->getTierLabel()) ?></span>
                            <span class="member-card__code mono"><?= ViewHelper::e($member->getMemberCode()) ?></span>
                        </div>

                        <dl class="member-card__stats">
                            <div><dt>Diskon</dt><dd><?= (int) round($member->getDiscountRate() * 100) ?>%</dd></div>
                            <div><dt>Maks. sewa</dt><dd><?= $member->getMaxBorrowDays() ?> hari</dd></div>
                            <div><dt>Dipinjam</dt><dd><?= $member->getActiveLoans() ?>/<?= $member->getMaxConcurrentLoans() ?></dd></div>
                        </dl>

                        <div class="member-card__quota">
                            <div class="member-card__quota-head">
                                <span>Kuota terpakai</span>
                                <strong><?= $ratio ?>%</strong>
                            </div>
                            <div class="quota" title="Kuota pinjaman terpakai">
                                <span style="width: <?= $ratio ?>%"></span>
                            </div>
                        </div>

                        <div class="member-card__foot">
                            <span class="muted mono" title="<?= ViewHelper::e($member->getEmail()) ?>"><?= ViewHelper::e($member->getEmail()) ?></span>
                            <?php if ($isCurrent): ?>
                                <span class="pill pill--success">Akun aktif</span>
                            <?php else: ?>
                                <form method="POST" action="index.php" class="inline-form">
                                    <input type="hidden" name="action" value="login_demo">
                                    <input type="hidden" name="member_id" value="<?= ViewHelper::e($member->getId()) ?>">
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
                                <input type="radio" name="tier" value="<?= ViewHelper::e($sample->getTierCode()) ?>" <?= $i === 0 ? 'checked' : '' ?>>
                                <span>
                                    <strong><?= ViewHelper::e($sample->getTierLabel()) ?></strong>
                                    <small>Diskon <?= (int) round($sample->getDiscountRate() * 100) ?>% &middot; <?= $sample->getMaxBorrowDays() ?> hari</small>
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
