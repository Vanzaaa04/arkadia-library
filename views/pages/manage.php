<?php

declare(strict_types=1);

use App\Support\ViewHelper;
?>
<!-- ===================== HALAMAN: TAMBAH BUKU ===================== -->
<section id="page-manage" class="page container page-shell <?= $tab === 'manage' ? 'is-active' : '' ?>" aria-labelledby="manageTitle">
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
                            $themes = ['terracotta' => 'Merlot', 'indigo' => 'Biru Tua', 'forest' => 'Hijau Pinus', 'amber' => 'Kuning Emas', 'crimson' => 'Merah Bata', 'obsidian' => 'Espresso'];
                            $first = true;
                            foreach ($themes as $val => $label): ?>
                                <label class="swatch" title="<?= ViewHelper::e($label) ?>">
                                    <input type="radio" name="cover_theme" value="<?= ViewHelper::e($val) ?>" <?= $first ? 'checked' : '' ?>>
                                    <span class="swatch__dot cover--<?= ViewHelper::e($val) ?>"></span>
                                    <span class="sr-only"><?= ViewHelper::e($label) ?></span>
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
                <span class="cover__motif"></span><span class="cover__rule"></span>
                <span class="cover__badge" id="previewBadge">Umum</span>
                <span class="cover__title" id="previewTitle">Judul buku kamu</span>
                <span class="cover__author" id="previewAuthor">Nama penulis</span>
            </div>
            <p class="preview-card__price"><strong id="previewPrice">Rp 5.000</strong> / hari</p>
            <p class="muted small">Tampilan sampul di katalog.</p>
        </aside>
    </div>
</section>
