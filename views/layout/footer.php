<?php

declare(strict_types=1);

use App\Support\ViewHelper;
?>
    </main>

    <?php require __DIR__ . '/../partials/modals.php'; ?>

    <footer class="footer">
        <div class="container footer__inner">
            <p><strong>ARKADIA</strong> &middot; Prototipe UTS Pemrograman Web Ganjil 2026/2027 &middot; Informatika UMM</p>
            <p class="muted small">HTML5 &middot; CSS3 &middot; JavaScript &middot; PHP <?= ViewHelper::e(PHP_VERSION) ?> OOP &middot; Data JSON &amp; XML</p>
        </div>
    </footer>

    <script>
        window.ARKADIA_STATE = {
            catalog: <?= json_encode($clientCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>,
            members: <?= json_encode($clientMembers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>,
            currentUser: <?= $currentUser !== null ? json_encode($currentUser->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) : 'null' ?>
        };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
