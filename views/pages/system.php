<?php

declare(strict_types=1);

use App\Support\ViewHelper;
?>
<!-- ===================== HALAMAN: INFO SISTEM (UNTUK PENILAIAN) ===================== -->
<section id="page-system" class="page container page-shell <?= $tab === 'system' ? 'is-active' : '' ?>" aria-labelledby="systemTitle">
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
                <li>Layout responsif (CSS Grid/Flexbox) dengan tema terang &amp; gelap serta tampilan Grid/Daftar</li>
                <li>Interaktif: pencarian, sortir katalog, E-Reader 3 mode kertas, struk cetak PDF, kalkulasi via <code>fetch()</code></li>
            </ul>
        </article>
        <article class="rubric-card">
            <div class="rubric-card__head"><span>PHP OOP</span><strong>40 poin</strong></div>
            <ul class="checklist">
                <li><b>Class &amp; Object:</b> <?= count($oopReport) ?> class/interface di <code>src/</code> (termasuk <code>RequestController</code> &amp; <code>ViewHelper</code>)</li>
                <li><b>Encapsulation:</b> properti <code>private</code> + getter/setter bervalidasi</li>
                <li><b>Inheritance:</b> <code>MediaItem</code> &rarr; 3 jenis buku, <code>User</code> &rarr; 3 jenis anggota</li>
                <li><b>Polymorphism:</b> <code>calculateBaseRentalCost()</code>, <code>getDiscountRate()</code>, dll. berbeda per subclass</li>
                <li><b>Namespace:</b> <code>App\Contracts</code>, <code>App\Models</code>, <code>App\Services</code>, <code>App\Controllers</code>, <code>App\Support</code></li>
            </ul>
        </article>
        <article class="rubric-card">
            <div class="rubric-card__head"><span>Struktur Berkas</span><strong>30 poin</strong></div>
            <ul class="checklist">
                <li>Kode OOP terpisah di folder <code>src/</code>, 1 class per file</li>
                <li>Tampilan terpisah modular di <code>views/</code> (layout, pages, partials) dengan <code>index.php</code> sebagai entry point tipis</li>
                <li>Autoloader PSR-4 (<code>autoload.php</code>), CSS &amp; JS di <code>assets/</code></li>
                <li>Data tersimpan di <a href="data/ebooks.json" target="_blank" rel="noopener">ebooks.json</a>, <a href="data/members.json" target="_blank" rel="noopener">members.json</a>, <a href="data/transactions.json" target="_blank" rel="noopener">transactions.json</a> &amp; <a href="data/ebooks.xml" target="_blank" rel="noopener">ebooks.xml</a></li>
                <li>Berjalan di PHP <?= ViewHelper::e(PHP_VERSION) ?> tanpa error</li>
            </ul>
        </article>
    </div>

    <div class="section-head">
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
                        <td><code><?= ViewHelper::e((new ReflectionClass($sample))->getShortName()) ?></code></td>
                        <td><?= (int) round($sample->getDiscountRate() * 100) ?>%</td>
                        <td><?= $sample->getMaxBorrowDays() ?> hari</td>
                        <td><?= $sample->waivesServiceFee() ? 'true' : 'false' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section-head">
        <h2>Struktur class (dibaca langsung dengan Reflection API)</h2>
        <p>Daftar ini dibuat otomatis oleh <code>OopInspectorService</code> saat halaman dibuka.</p>
    </div>
    <div class="reflection-grid">
        <?php foreach ($oopReport as $cls): ?>
            <details class="reflection-card">
                <summary>
                    <span class="role-chip"><?= ViewHelper::e((string) $cls['role']) ?></span>
                    <code class="reflection-card__name"><?= ViewHelper::e((string) $cls['short_name']) ?></code>
                    <?php if ($cls['parent'] !== null): ?>
                        <span class="muted small">extends <?= ViewHelper::e((string) $cls['parent']) ?></span>
                    <?php endif; ?>
                </summary>
                <p class="muted small mono"><?= ViewHelper::e((string) $cls['file']) ?></p>
                <?php if (count($cls['interfaces']) > 0): ?>
                    <p class="small">implements <code><?= ViewHelper::e(implode(', ', $cls['interfaces'])) ?></code></p>
                <?php endif; ?>
                <div class="reflection-cols">
                    <div>
                        <h4>Properti</h4>
                        <?php if (count($cls['properties']) > 0): ?>
                            <ul><?php foreach ($cls['properties'] as $p): ?><li><code><?= ViewHelper::e($p) ?></code></li><?php endforeach; ?></ul>
                        <?php else: ?>
                            <p class="muted small">Mewarisi dari parent.</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4>Method publik</h4>
                        <ul><?php foreach (array_slice($cls['methods'], 0, 8) as $m): ?><li><code><?= ViewHelper::e($m) ?></code></li><?php endforeach; ?></ul>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>
