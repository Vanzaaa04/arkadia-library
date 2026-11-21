/**
 * ARKADIA — Interaksi halaman (Vanilla JavaScript, tanpa framework)
 *  1. Tema terang/gelap
 *  2. Navigasi antar halaman + lompat kategori dari Beranda
 *  3. Autentikasi anggota (Masuk / Daftar / Akun Demo + preselect tier)
 *  4. Pencarian, filter, toggle Grid/Daftar katalog & scope peminjaman
 *  5. Modal detail buku
 *  6. Modal sewa + kalkulasi biaya real-time via fetch() ke PHP
 *  7. Pratinjau sampul saat menambah buku & validasi form
 *  8. Notifikasi (toast)
 */
document.addEventListener('DOMContentLoaded', () => {
    const state = window.ARKADIA_STATE || { catalog: [], members: [], currentUser: null };
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const rupiah = (n) => 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');
    const findBook = (id) => state.catalog.find((b) => b.id === id);
    const typeShort = (code) => ({ academic: 'Akademik', interactive: 'Interaktif' }[code] || 'Umum');
    const shortAuthor = (a) => String(a).split(/\s*(?:&|,)\s*/)[0];
    const shortClass = (fqcn) => String(fqcn).split('\\').pop();
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const formatTanggal = (iso) => {
        const d = new Date(iso + 'T00:00:00');
        return isNaN(d) ? iso : `${d.getDate()} ${MONTHS[d.getMonth()]} ${d.getFullYear()}`;
    };
    const initials = (name) => {
        const clean = String(name).replace(/^(Dr\.|Prof\.|Ir\.)\s+/i, '').replace(/,.*$/, '').trim();
        return clean.split(/\s+/).slice(0, 2).map((w) => w[0]?.toUpperCase() || '').join('') || 'A';
    };

    /* ---------- 8. Toast ---------- */
    const toastStack = $('#toastStack');
    function dismissToast(el) {
        if (!el || el.classList.contains('is-leaving')) return;
        el.classList.add('is-leaving');
        setTimeout(() => el.remove(), 240);
    }
    function bindToast(el) {
        $('.toast__close', el)?.addEventListener('click', () => dismissToast(el));
        setTimeout(() => dismissToast(el), 6500);
    }
    $$('.toast', toastStack).forEach(bindToast);

    /* ---------- 1. Tema ---------- */
    $('#btnTheme')?.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        try { localStorage.setItem('arkadia-theme', next); } catch (e) { /* abaikan */ }
    });

    /* ---------- 2. Navigasi halaman ---------- */
    const pages = $$('.page');
    const navLinks = $$('.nav-link[data-page]');
    let currentTab = (new URLSearchParams(window.location.search)).get('tab') || 'home';

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let revealActivePage = () => {};
    let syncNavIndicator = () => {};
    let animateQuotaBars = () => {};

    function showPage(name) {
        const target = $(`#page-${name}`);
        if (!target) return;
        currentTab = name;
        pages.forEach((p) => p.classList.toggle('is-active', p.id === `page-${name}`));
        navLinks.forEach((b) => {
            const active = b.dataset.page === name;
            b.classList.toggle('is-active', active);
            if (active) b.setAttribute('aria-current', 'page'); else b.removeAttribute('aria-current');
        });
        const url = new URL(window.location.href);
        url.searchParams.set('tab', name);
        history.replaceState(null, '', url);
        syncNavIndicator();
        revealActivePage(target);
        if (name === 'members') animateQuotaBars();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    $$('[data-page], [data-goto]').forEach((el) => {
        el.addEventListener('click', (ev) => {
            ev.preventDefault();
            showPage(el.dataset.page || el.dataset.goto);
        });
    });

    /* ---------- Modal umum ---------- */
    $$('dialog.modal').forEach((dlg) => {
        dlg.addEventListener('click', (ev) => { if (ev.target === dlg) dlg.close(); });
        $$('[data-close-modal]', dlg).forEach((btn) => btn.addEventListener('click', () => dlg.close()));
    });

    /* ---------- 3. Modal Masuk / Daftar Anggota ---------- */
    const authModal = $('#authModal');
    const authTabs = $$('.auth-tab[data-auth-tab]');
    const authPendingHint = $('#authPendingHint');

    function setAuthTab(tabName) {
        const isReg = tabName === 'register';
        authTabs.forEach((t) => {
            const active = t.dataset.authTab === tabName;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        $('#authPanelLogin')?.classList.toggle('is-active', !isReg);
        $('#authPanelRegister')?.classList.toggle('is-active', isReg);
        $('#authModalTitle').textContent = isReg ? 'Daftar Anggota Baru' : 'Masuk ke Arkadia';
    }

    function openAuth(tabName = 'login', pendingBookId = '', preselectTier = '') {
        if (!authModal) return;
        setAuthTab(tabName);
        const returnTab = pendingBookId ? 'catalog' : currentTab;
        const book = pendingBookId ? findBook(pendingBookId) : null;

        $('#authLoginPending').value = pendingBookId || '';
        $('#authRegPending').value = pendingBookId || '';
        $('#authLoginReturn').value = returnTab;
        $('#authRegReturn').value = returnTab;
        $$('.js-pending-book').forEach((el) => { el.value = pendingBookId || ''; });
        $$('.js-return-tab').forEach((el) => { el.value = returnTab; });

        if (preselectTier) {
            const radio = $(`#formAuthRegister input[name="tier"][value="${preselectTier}"]`);
            if (radio) radio.checked = true;
        }

        if (book) {
            authPendingHint.hidden = false;
            authPendingHint.textContent = `Setelah masuk, lanjut menyewa: "${book.title}"`;
        } else {
            authPendingHint.hidden = true;
            authPendingHint.textContent = '';
        }

        authModal.showModal();
    }

    authTabs.forEach((t) => t.addEventListener('click', () => setAuthTab(t.dataset.authTab)));
    $$('[data-open-auth]').forEach((btn) => {
        btn.addEventListener('click', () => {
            openAuth(btn.dataset.openAuth || 'login', '', btn.dataset.preselectTier || '');
        });
    });

    /* ---------- 4. Pencarian, filter, sortir, & Grid/List katalog ---------- */
    const searchInput = $('#searchInput');
    const typeFilter = $('#typeFilter');
    const sortFilter = $('#sortFilter');
    const availableOnly = $('#availableOnly');
    const catalogGrid = $('#catalogGrid');
    const catalogCards = $$('#catalogGrid .book-card');
    const catalogEmpty = $('#catalogEmpty');
    const catalogCount = $('#catalogCount');
    const btnViewGrid = $('#btnViewGrid');
    const btnViewList = $('#btnViewList');
    let activeCategory = 'all';

    function sortCatalog() {
        if (!catalogGrid || !sortFilter) return;
        const mode = sortFilter.value;
        const sorted = [...catalogCards].sort((a, b) => {
            if (mode === 'price-asc') return Number(a.dataset.rate) - Number(b.dataset.rate);
            if (mode === 'price-desc') return Number(b.dataset.rate) - Number(a.dataset.rate);
            if (mode === 'pages-desc') return Number(b.dataset.pages) - Number(a.dataset.pages);
            return Number(a.dataset.order) - Number(b.dataset.order);
        });
        const frag = document.createDocumentFragment();
        sorted.forEach((card) => frag.appendChild(card));
        catalogGrid.appendChild(frag);
    }

    function applyFilters() {
        const q = searchInput.value.trim().toLowerCase();
        const type = typeFilter.value;
        const onlyAvail = availableOnly.checked;
        let visible = 0;
        catalogCards.forEach((card) => {
            const ok = (!q || card.dataset.search.includes(q))
                && (activeCategory === 'all' || card.dataset.category === activeCategory)
                && (type === 'all' || card.dataset.type === type)
                && (!onlyAvail || card.dataset.available === '1');
            card.hidden = !ok;
            if (ok) visible++;
        });
        catalogEmpty.hidden = visible > 0;
        catalogCount.textContent = visible;
        revealActivePage(catalogGrid);
    }

    function triggerCatalogWave() {
        if (reduceMotion) return;
        let idx = 0;
        $$('#catalogGrid .book-card').forEach((card) => {
            if (card.hidden) return;
            card.classList.remove('is-wave');
            card.style.setProperty('--wave-delay', `${(idx % 8) * 28}ms`);
            void card.offsetWidth;
            card.classList.add('is-wave');
            idx++;
        });
    }

    function setActiveCategoryChip(cat) {
        activeCategory = cat;
        $$('.chip[data-cat]').forEach((c) => c.classList.toggle('is-active', c.dataset.cat === cat));
        applyFilters();
        triggerCatalogWave();
    }

    searchInput?.addEventListener('input', applyFilters);
    typeFilter?.addEventListener('change', () => { applyFilters(); triggerCatalogWave(); });
    sortFilter?.addEventListener('change', () => { sortCatalog(); applyFilters(); triggerCatalogWave(); });
    availableOnly?.addEventListener('change', () => { applyFilters(); triggerCatalogWave(); });
    $$('.chip[data-cat]').forEach((chip) => {
        chip.addEventListener('click', () => setActiveCategoryChip(chip.dataset.cat));
    });
    $$('[data-jump-category]').forEach((btn) => {
        btn.addEventListener('click', () => {
            setActiveCategoryChip(btn.dataset.jumpCategory || 'all');
            showPage('catalog');
        });
    });
    $('#btnResetFilter')?.addEventListener('click', () => {
        searchInput.value = '';
        typeFilter.value = 'all';
        if (sortFilter) {
            sortFilter.value = 'default';
            sortCatalog();
        }
        availableOnly.checked = false;
        setActiveCategoryChip('all');
    });

    function setCatalogView(mode) {
        const isList = mode === 'list';
        catalogGrid?.classList.toggle('is-list', isList);
        btnViewGrid?.classList.toggle('is-active', !isList);
        btnViewList?.classList.toggle('is-active', isList);
        btnViewGrid?.setAttribute('aria-pressed', isList ? 'false' : 'true');
        btnViewList?.setAttribute('aria-pressed', isList ? 'true' : 'false');
        triggerCatalogWave();
    }
    btnViewGrid?.addEventListener('click', () => setCatalogView('grid'));
    btnViewList?.addEventListener('click', () => setCatalogView('list'));

    /* Filter Peminjaman saya vs Semua anggota */
    const scopeTabs = $$('.scope-tab[data-loan-scope]');
    scopeTabs.forEach((btn) => {
        btn.addEventListener('click', () => {
            const scope = btn.dataset.loanScope;
            const userId = state.currentUser?.id || '';
            scopeTabs.forEach((t) => t.classList.toggle('is-active', t === btn));

            let activeVisible = 0;
            $$('#activeLoanList .loan-card').forEach((card) => {
                const show = scope === 'all' || card.dataset.loanOwner === userId;
                card.hidden = !show;
                if (show) activeVisible++;
            });
            const emptyMyLoans = $('#emptyMyLoans');
            if (emptyMyLoans) emptyMyLoans.hidden = activeVisible > 0;

            let histVisible = 0;
            $$('#historyTableBody tr[data-loan-owner]').forEach((row) => {
                const show = scope === 'all' || row.dataset.loanOwner === userId;
                row.hidden = !show;
                if (show) histVisible++;
            });
            const emptyHistRow = $('#emptyMyHistoryRow');
            if (emptyHistRow) emptyHistRow.hidden = histVisible > 0;
        });
    });

    /* ---------- Helper sampul ---------- */
    function paintCover(el, book, sizeClass) {
        el.className = `cover cover--${book.cover_theme} ${sizeClass}`;
        $('.cover__badge', el).textContent = typeShort(book.media_type);
        $('.cover__title', el).textContent = book.title;
        $('.cover__author', el).textContent = shortAuthor(book.author);
    }

    /* ---------- 5. Modal detail buku, Mini E-Reader, & Struk Digital ---------- */
    const bookModal = $('#bookModal');
    let detailBookId = null;
    function openBookDetail(id) {
        const b = findBook(id);
        if (!b) return;
        detailBookId = id;
        paintCover($('#bmCover'), b, 'cover--lg');
        $('#bmType').textContent = b.media_type_label;
        $('#bmTitle').textContent = b.title;
        $('#bmAuthor').textContent = 'oleh ' + b.author;
        $('#bmSynopsis').textContent = b.synopsis;
        $('#bmCategory').textContent = b.category;
        $('#bmYear').textContent = b.publish_year;
        $('#bmPages').textContent = b.pages + ' halaman';
        $('#bmFormat').textContent = `${b.file_format} (${b.file_size_mb} MB)`;
        $('#bmStock').textContent = b.stock > 0 ? `${b.stock} dari ${b.total_stock} tersedia` : 'Sedang habis';
        $('#bmTech').textContent = b.technical_metadata;
        $('#bmPrice').textContent = rupiah(b.daily_rate);
        const rentBtn = $('#bmRentBtn');
        rentBtn.disabled = b.stock <= 0;
        rentBtn.textContent = b.stock > 0 ? 'Sewa buku ini' : 'Stok habis';
        bookModal.showModal();
    }
    $$('[data-open-book]').forEach((el) => el.addEventListener('click', () => openBookDetail(el.dataset.openBook)));
    $('#bmRentBtn')?.addEventListener('click', () => {
        bookModal.close();
        openRent(detailBookId);
    });
    $('#bmReadBtn')?.addEventListener('click', () => {
        bookModal.close();
        openReader(detailBookId, 'sample');
    });

    /* --- Mini Web E-Reader --- */
    const readerModal = $('#readerModal');
    const readerShell = $('#readerShell');
    const readerContent = $('#rdContent');
    const SAMPLE_CHAPTERS = {
        'EBK-101': [
            { title: 'Reliabilitas & Konsistensi pada Skala Terdistribusi', paragraphs: ['Sistem penyimpanan data modern dituntut tetap melayani pembacaan dan penulisan meskipun sebagian node mengalami kegagalan jaringan atau partisi sementara.', 'Pendekatan konsensus seperti Raft memisahkan pemilihan pemimpin (leader election) dari replikasi log secara deterministik agar setiap salinan state machine konvergen pada urutan transaksi yang identik.', 'Dalam praktik produksi, pemisahan antara beban OLTP latensi rendah dan jalur analitik berbasis event log mencegah lonjakan kueri pelaporan mengganggu transaksi pengguna aktif.'] },
            { title: 'Replikasi Multi-Wilayah & Resolusi Konflik', paragraphs: ['Ketika aplikasi beroperasi lintas benua, latensi kecepatan cahaya memaksa arsitek memilih antara sinkronisasi ketat atau model replikasi asinkron dengan jendela konvergensi terukur.', 'Versi vektor (version vectors) dan penanda waktu logis hybrid membantu sistem mendeteksi penulisan konkuren secara akurat tanpa bergantung pada jam dinding fisik yang rentan melenceng.'] },
            { title: 'Pemrosesan Aliran Event Real-Time', paragraphs: ['Alih-alih menganggap basis data sebagai potret statis, arsitektur log berorientasi event memperlakukan setiap perubahan sebagai fakta abadi yang dapat diputar ulang kapan saja.', 'Materialized view lalu dibangun secara inkremental dari aliran event yang sama, memberi pembaca tampilan cepat tanpa mengorbankan jejak audit.'] },
        ],
        'EBK-102': [
            { title: 'Disiplin Strict Types & Kontrak Domain di PHP 8.3', paragraphs: ['Mengaktifkan declare(strict_types=1) pada setiap berkas model membuat batas kontrak antar objek ditegakkan secara eksplisit oleh runtime PHP sejak pemanggilan pertama.', 'Properti bertipe dan enum atau abstract class mencegah data tidak sah masuk ke lapisan bisnis, sehingga logika perhitungan tetap bersih dari validasi defensif berulang.'] },
            { title: 'Enkapsulasi State & Value Object', paragraphs: ['Daripada menyebarkan array asosiatif tanpa bentuk pasti ke seluruh controller, membungkus identitas transaksi dan tarif ke dalam class khusus membuat setiap perubahan state tervalidasi terpusat.', 'Method mutator yang bermakna seperti markAsReturned() atau extend() menjaga invarian bisnis tetap utuh setiap kali data disimpan ke berkas JSON atau XML.'] },
            { title: 'Polimorfisme Tanpa Ketergantungan Framework', paragraphs: ['Memisahkan aturan diskon anggota ke dalam hirarki User dan kalkulasi biaya media ke hirarki MediaItem memungkinkan sistem menghitung ribuan variasi harga melalui satu panggilan antarmuka yang seragam.', 'Arsitektur bersih seperti ini mudah diuji, dibaca dengan Reflection API, dan dipelihara lintas tim.'] },
        ],
        'EBK-103': [
            { title: 'Hierarki Visual Sebelum Dekorasi', paragraphs: ['Antarmuka digital yang terasa mahal jarang bertumpu pada bayangan berlapis atau gradien mencolok; kekuatannya justru lahir dari kontras ukuran huruf, berat font, dan spasi putih yang disengaja.', 'Mulailah dengan menaikkan bobot dan ukuran elemen utama sebelum memberi warna pembeda pada keterangan sekunder.'] },
            { title: 'Ritme Spasi Grid 8-Piksel & Warna Permukaan', paragraphs: ['Mengunci komponen pada kelipatan 4px dan 8px menertibkan proporsi kartu, jarak antar kolom, dan ketinggian tombol secara konsisten di layar desktop maupun ponsel.', 'Memadukan latar hangat berkarakter dengan permukaan putih bersih menciptakan kedalaman alami tanpa garis batas berlebih.'] },
            { title: 'Mikro-Interaksi & Tipografi Tabel Komersial', paragraphs: ['Angka harga, stok lisensi, dan tanggal jatuh tempo jauh lebih nyaman dipindai ketika diselaraskan rata kanan menggunakan angka tabular berjarak seragam.'] },
        ],
        'EBK-104': [
            { title: 'Optimasi Gradien Stokastik & Generalisasi Model', paragraphs: ['Di balik performa jaringan saraf berparameter besar terdapat interaksi halus antara ukuran batch, penjadwalan learning rate, dan lanskap kurva fungsi objektif.', 'Bukti empiris menunjukkan bahwa lintasan optimasi sering memilih wilayah minima yang datar sehingga model tetap tangguh terhadap pergeseran distribusi data uji.'] },
            { title: 'Mekanisme Attention & Representasi Kontekstual', paragraphs: ['Arsitektur Transformer menggantikan ketergantungan berurutan murni dengan bobot perhatian diri (self-attention) yang dapat memproses seluruh konteks dalam operasi matriks paralel.'] },
            { title: 'Teori Informasi Variansional dalam Inferensi Modern', paragraphs: ['Batas bawah bukti (ELBO) menjembatani kesenjangan antara model generatif probabilistik yang ekspresif dengan teknik optimasi berbasis backpropagation modern.'] },
        ],
        'EBK-105': [
            { title: 'Proporsi Matematis pada Halaman Editorial', paragraphs: ['Sistem grid lahir dari kebutuhan menghadirkan keteraturan visual sehingga pembaca dapat memindai gagasan kompleks tanpa kelelahan mata.', 'Pembagian kolom yang disiplin menetapkan ritme baca yang tenang antara judul, isi naskah, keterangan pinggir, dan ruang kosong bernapas.'] },
            { title: 'Transisi Grid Cetak ke Layar Responsif Modern', paragraphs: ['Pada media digital, lebar viewport tidak lagi tetap seperti kertas cetak; CSS Grid memungkinkan rasio kolom beradaptasi luwes dari satu kolom ponsel hingga galeri lima kolom di desktop.'] },
            { title: 'Tipografi Fungsional & Konsistensi Navigasi', paragraphs: ['Ketika skala huruf dan batas penulisan terjaga pada 55 hingga 72 karakter per baris, kecepatan pemahaman pembaca meningkat drastis.'] },
        ],
        'EBK-106': [
            { title: 'Handshake TLS 1.3 & Keamanan Lapisan Transport', paragraphs: ['Protokol TLS modern memangkas putaran negosiasi kunci menjadi satu perjalanan pulang-pergi saja dengan menyingkirkan cipher suite usang dan selalu memprioritaskan forward secrecy.', 'Setiap sesi pertukaran data terenkripsi menggunakan kunci sementara yang tidak dapat didekripsi ulang walaupun kunci privat server bocor di masa depan.'] },
            { title: 'Autentikasi WebAuthn & Model Zero-Trust', paragraphs: ['Kredensial kriptografis berbasis perangkat keras menggantikan kerentanan kata sandi statis yang mudah dicuri melalui serangan phishing.'] },
            { title: 'Audit Sesi, Validasi Input, & Isolasi Token DRM', paragraphs: ['Pertahanan aplikasi web menuntut validasi ganda: pengalaman instan di sisi peramban serta penegakan kontrak ketat oleh objek layanan di sisi server.'] },
        ],
    };

    let readerBook = null;
    let readerMode = 'sample';
    let readerIndex = 0;
    let readerSize = 16;

    function buildChapters(book) {
        if (SAMPLE_CHAPTERS[book.id]) return SAMPLE_CHAPTERS[book.id];
        return [
            { title: `Pengantar: ${book.category}`, paragraphs: [book.synopsis, `Edisi ${book.media_type_label} karya ${book.author} (${book.publish_year}) ini disajikan dalam format ${book.file_format} dengan total ${book.pages} halaman terstruktur.`] },
            { title: 'Konsep Inti & Spesifikasi Teknis', paragraphs: [`Rujukan teknis: ${book.technical_metadata}. Bagian ini menguraikan kerangka teori, studi kasus implementasi, dan praktik terbaik pada topik ${book.category}.`] },
            { title: 'Ringkasan Penerapan & Daftar Pustaka', paragraphs: [`Seluruh bab dalam publikasi "${book.title}" dirancang untuk mempercepat pemahaman terapan dan dapat diakses penuh selama masa sewa aktif di Arkadia.`] },
        ];
    }

    function setReaderPaper(paper) {
        if (!readerShell) return;
        readerShell.setAttribute('data-paper', paper);
        $$('[data-paper-btn]', readerShell).forEach((b) => b.classList.toggle('is-active', b.dataset.paperBtn === paper));
    }

    function setReaderFont(nextSize) {
        readerSize = Math.min(22, Math.max(14, nextSize));
        readerContent?.style.setProperty('--rd-size', `${readerSize}px`);
        const sizeLabel = $('#rdFontSize');
        if (sizeLabel) sizeLabel.textContent = `${readerSize}px`;
    }

    function renderReaderPage() {
        if (!readerBook) return;
        const chapters = buildChapters(readerBook);
        const total = chapters.length;
        const ch = chapters[readerIndex] || chapters[0];
        const pct = Math.round(((readerIndex + 1) / total) * 100);
        const isLast = readerIndex >= total - 1;

        $('#rdChapterNum').textContent = `Bab ${readerIndex + 1} · ${readerBook.category}`;
        $('#rdChapterTitle').textContent = ch.title;

        const body = $('#rdBody');
        body.replaceChildren(...ch.paragraphs.map((text) => {
            const p = document.createElement('p');
            p.textContent = text;
            return p;
        }));

        $('#rdCta').hidden = !(readerMode === 'sample' && isLast);
        $('#rdProgressFill').style.width = `${pct}%`;
        $('#rdPageInfo').textContent = `Bab ${readerIndex + 1} dari ${total} · ${pct}%`;
        $('#rdPrev').disabled = readerIndex === 0;
        $('#rdNext').disabled = isLast;
        $('.reader-viewport').scrollTop = 0;
    }

    function openReader(bookId, mode = 'sample') {
        const b = findBook(bookId);
        if (!b || !readerModal) return;
        readerBook = b;
        readerMode = mode;
        readerIndex = 0;

        const badge = $('#rdModeBadge');
        badge.textContent = mode === 'full' ? 'Lisensi aktif' : 'Cuplikan';
        badge.classList.toggle('reader-badge--full', mode === 'full');
        $('#rdTitle').textContent = b.title;
        $('#rdAuthor').textContent = `${b.author} · ${b.pages} halaman`;

        const siteDark = document.documentElement.getAttribute('data-theme') === 'dark';
        setReaderPaper(siteDark ? 'dark' : 'light');
        setReaderFont(readerSize);
        renderReaderPage();
        readerModal.showModal();
    }

    $$('[data-read-book]').forEach((btn) => {
        btn.addEventListener('click', () => openReader(btn.dataset.readBook, btn.dataset.readMode || 'full'));
    });
    $('#rdPrev')?.addEventListener('click', () => { if (readerIndex > 0) { readerIndex--; renderReaderPage(); } });
    $('#rdNext')?.addEventListener('click', () => { readerIndex++; renderReaderPage(); });
    $('#rdFontDown')?.addEventListener('click', () => setReaderFont(readerSize - 1));
    $('#rdFontUp')?.addEventListener('click', () => setReaderFont(readerSize + 1));
    $$('[data-paper-btn]').forEach((btn) => btn.addEventListener('click', () => setReaderPaper(btn.dataset.paperBtn)));
    $('#rdRentNow')?.addEventListener('click', () => {
        readerModal.close();
        if (readerBook) openRent(readerBook.id);
    });

    /* --- Struk Digital & Cetak PDF --- */
    const receiptModal = $('#receiptModal');
    const findTransaction = (id) => (state.transactions || []).find((t) => t.id === id);

    function openReceipt(trxId) {
        const t = findTransaction(trxId);
        if (!t || !receiptModal) return;
        const active = t.status === 'active';
        const statusEl = $('#rcStatus');
        statusEl.textContent = active ? 'Aktif' : 'Selesai';
        statusEl.className = `pill ${active ? 'pill--warning' : 'pill--success'}`;

        $('#rcCode').textContent = t.transaction_code;
        $('#rcToken').textContent = t.access_token;
        $('#rcMember').textContent = t.member_name;
        $('#rcTier').textContent = t.member_tier;
        $('#rcBorrowDate').textContent = formatTanggal(t.borrow_date);
        $('#rcDueDateLabel').textContent = active ? 'Jatuh tempo' : 'Dikembalikan';
        $('#rcDueDate').textContent = formatTanggal(active ? t.due_date : (t.return_date || t.due_date));

        $('#rcBookTitle').textContent = t.ebook_title;
        $('#rcBookMeta').textContent = `${t.ebook_type} · Durasi sewa ${t.duration_days} hari`;
        $('#rcBase').textContent = rupiah(t.base_cost);
        $('#rcService').textContent = t.service_fee > 0 ? rupiah(t.service_fee) : 'Rp 0 (Gratis)';
        $('#rcDiscount').textContent = t.discount_amount > 0 ? '−' + rupiah(t.discount_amount) : 'Rp 0';
        $('#rcTotal').textContent = rupiah(t.total_cost);
        receiptModal.showModal();
    }

    $$('[data-open-receipt]').forEach((btn) => btn.addEventListener('click', () => openReceipt(btn.dataset.openReceipt)));
    $('#btnPrintReceipt')?.addEventListener('click', () => window.print());

    /* ---------- 6. Modal sewa (berbasis akun login) ---------- */
    const rentModal = $('#rentModal');
    const rentForm = $('#rentForm');
    const rentMemberInput = $('#rentMember');
    const rentDays = $('#rentDays');
    const rentSubmit = $('#rentSubmit');
    const rentAlert = $('#rentAlert');
    const bill = $('#bill');
    const dayChips = $$('.day-chip');
    let currentBook = null;
    let quoteTimer = null;
    let quoteToken = 0;

    function currentDays() {
        let d = parseInt(rentDays.value, 10);
        if (isNaN(d) || d < 1) d = 1;
        if (d > 30) d = 30;
        return d;
    }

    function syncDayChips() {
        const m = state.currentUser;
        const d = currentDays();
        dayChips.forEach((chip) => {
            const v = parseInt(chip.dataset.days, 10);
            chip.classList.toggle('is-active', v === d);
            chip.disabled = m ? v > m.max_borrow_days : false;
        });
    }

    function showRentErrors(errors) {
        if (errors.length) {
            rentAlert.hidden = false;
            rentAlert.textContent = errors.join(' ');
            rentSubmit.disabled = true;
        } else {
            rentAlert.hidden = true;
            rentAlert.textContent = '';
            rentSubmit.disabled = false;
        }
    }

    let lastBillTotal = 0;
    let billRollRaf = 0;
    function animateBillTotal(targetVal) {
        const el = $('#billTotal');
        if (!el) return;
        const target = Number(targetVal) || 0;
        cancelAnimationFrame(billRollRaf);
        if (reduceMotion || lastBillTotal <= 0 || lastBillTotal === target) {
            lastBillTotal = target;
            el.textContent = rupiah(target);
            return;
        }
        const from = lastBillTotal;
        const duration = 340;
        const start = performance.now();
        function frame(now) {
            const p = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = rupiah(from + (target - from) * eased);
            if (p < 1) {
                billRollRaf = requestAnimationFrame(frame);
            } else {
                lastBillTotal = target;
                el.textContent = rupiah(target);
            }
        }
        billRollRaf = requestAnimationFrame(frame);
    }

    function renderQuote(q) {
        $('#billBaseLabel').textContent = `Biaya sewa (${q.duration_days} hari × ${rupiah(q.daily_rate)})`;
        $('#billBase').textContent = rupiah(q.base_cost);

        if (q.applied_service_fee === 0 && q.raw_service_fee > 0) {
            $('#billServiceLabel').textContent = 'Biaya layanan (gratis untuk Premium)';
            $('#billService').innerHTML = `<span class="strike">${rupiah(q.raw_service_fee)}</span>Rp 0`;
        } else {
            $('#billServiceLabel').textContent = 'Biaya layanan';
            $('#billService').textContent = rupiah(q.applied_service_fee);
        }

        $('#billDiscountLabel').textContent = q.discount_rate_percent > 0
            ? `Diskon ${q.member_tier} (${q.discount_rate_percent}%)`
            : 'Diskon anggota';
        $('#billDiscount').textContent = q.discount_amount > 0 ? '−' + rupiah(q.discount_amount) : 'Rp 0';
        $('#billDue').textContent = formatTanggal(q.due_date);
        animateBillTotal(q.total_cost);
        $('#billTrace').textContent = `${shortClass(q.member_class)} × ${shortClass(q.ebook_class)}`;

        const errors = [];
        if (!q.is_stock_available) errors.push('Stok buku ini sedang habis.');
        if (!q.is_quota_available) errors.push(`${q.member_name} sudah mencapai batas jumlah pinjaman.`);
        if (!q.is_duration_valid) errors.push(`Maksimal ${q.max_allowed_days} hari untuk ${q.member_tier}.`);
        showRentErrors(errors);
    }

    async function fetchQuote() {
        const m = state.currentUser;
        if (!currentBook || !m) return;
        const days = currentDays();
        const token = ++quoteToken;
        bill.classList.add('is-loading');
        try {
            const params = new URLSearchParams({ api: 'simulate', member_id: m.id, ebook_id: currentBook.id, days: String(days) });
            const res = await fetch(`index.php?${params.toString()}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (token !== quoteToken) return;
            if (!data.ok) throw new Error(data.error || 'Gagal menghitung biaya.');
            renderQuote(data.quote);
        } catch (err) {
            if (token !== quoteToken) return;
            showRentErrors([err.message]);
        } finally {
            if (token === quoteToken) bill.classList.remove('is-loading');
        }
    }

    function refreshRent(immediate = false) {
        const m = state.currentUser;
        if (m) {
            rentMemberInput.value = m.id;
            $('#rentUserName').textContent = m.name;
            $('#rentUserAvatar').className = `avatar avatar--${m.tier}`;
            $('#rentUserAvatar').textContent = initials(m.name);
            $('#rentMemberInfo').textContent = `${m.tier_label} · maks ${m.max_borrow_days} hari · pinjam ${m.active_loans}/${m.max_concurrent_loans} buku`;
        }
        syncDayChips();
        clearTimeout(quoteTimer);
        if (immediate) {
            fetchQuote();
        } else {
            quoteTimer = setTimeout(fetchQuote, 120);
        }
    }

    function openRent(id) {
        const b = findBook(id);
        if (!b) return;
        if (!state.currentUser) {
            openAuth('login', b.id);
            return;
        }
        currentBook = b;
        lastBillTotal = 0;
        $('#rentEbookId').value = b.id;
        paintCover($('#rmCover'), b, 'cover--sm');
        $('#rmTitle').textContent = b.title;
        $('#rmSub').textContent = `${rupiah(b.daily_rate)} / hari · ${b.media_type_label}`;

        rentDays.value = Math.min(3, state.currentUser.max_borrow_days);
        rentSubmit.disabled = true;
        refreshRent(true);
        rentModal.showModal();
    }

    $$('[data-rent-book]').forEach((btn) => btn.addEventListener('click', () => openRent(btn.dataset.rentBook)));
    $('#btnSwitchAccount')?.addEventListener('click', () => {
        rentModal.close();
        openAuth('login', currentBook?.id || '');
    });
    rentDays?.addEventListener('input', () => refreshRent(false));
    dayChips.forEach((chip) => chip.addEventListener('click', () => {
        rentDays.value = chip.dataset.days;
        refreshRent(true);
    }));
    $('#dayMinus')?.addEventListener('click', () => { rentDays.value = Math.max(1, currentDays() - 1); refreshRent(true); });
    $('#dayPlus')?.addEventListener('click', () => { rentDays.value = Math.min(30, currentDays() + 1); refreshRent(true); });

    rentForm?.addEventListener('submit', (ev) => {
        if (rentSubmit.disabled) {
            ev.preventDefault();
            return;
        }
        rentDays.value = currentDays();
        rentSubmit.disabled = true;
        rentSubmit.textContent = 'Memproses...';
    });

    /* Auto-buka modal sewa/auth sesuai query URL setelah login/register */
    const bootParams = new URLSearchParams(window.location.search);
    const bootRentId = bootParams.get('rent');
    const bootAuthTab = bootParams.get('auth');
    if (bootRentId || bootAuthTab) {
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('rent');
        cleanUrl.searchParams.delete('auth');
        history.replaceState(null, '', cleanUrl);

        if (bootAuthTab) {
            openAuth(bootAuthTab === 'register' ? 'register' : 'login', bootRentId || '');
        } else if (bootRentId && state.currentUser && findBook(bootRentId)) {
            openRent(bootRentId);
        }
    }

    /* ---------- 7. Pratinjau sampul & validasi form ---------- */
    const previewCover = $('#previewCover');
    const ebookTitle = $('#ebookTitle');
    const ebookAuthor = $('#ebookAuthor');
    const ebookType = $('#ebookMediaType');
    const ebookRate = $('#ebookDailyRate');
    function updatePreview() {
        if (!previewCover) return;
        const theme = ($('input[name="cover_theme"]:checked') || {}).value || 'terracotta';
        previewCover.className = `cover cover--${theme} cover--preview`;
        $('#previewTitle').textContent = ebookTitle.value.trim() || 'Judul buku kamu';
        $('#previewAuthor').textContent = ebookAuthor.value.trim() || 'Nama penulis';
        $('#previewBadge').textContent = typeShort(ebookType.value);
        $('#previewPrice').textContent = rupiah(ebookRate.value);
        $('#groupDoi').hidden = ebookType.value !== 'academic';
        $('#groupAudio').hidden = ebookType.value !== 'interactive';
    }
    [ebookTitle, ebookAuthor, ebookType, ebookRate].forEach((el) => el?.addEventListener('input', updatePreview));
    ebookType?.addEventListener('change', updatePreview);
    $$('input[name="cover_theme"]').forEach((r) => r.addEventListener('change', updatePreview));
    updatePreview();

    function setError(input, errId, message) {
        const err = document.getElementById(errId);
        input.classList.toggle('is-invalid', Boolean(message));
        if (err) err.textContent = message || '';
        return !message;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    $('#formAuthLogin')?.addEventListener('submit', (ev) => {
        const email = $('#authLoginEmail');
        if (!setError(email, 'err-authLoginEmail', emailPattern.test(email.value.trim()) ? '' : 'Masukkan email yang valid.')) {
            ev.preventDefault();
            email.focus();
        }
    });

    $('#formAuthRegister')?.addEventListener('submit', (ev) => {
        const name = $('#authRegName');
        const email = $('#authRegEmail');
        const inst = $('#authRegInst');
        const results = [
            setError(name, 'err-authRegName', name.value.trim().length < 3 ? 'Nama minimal 3 huruf.' : ''),
            setError(email, 'err-authRegEmail', emailPattern.test(email.value.trim()) ? '' : 'Format email belum benar.'),
            setError(inst, 'err-authRegInst', inst.value.trim().length < 3 ? 'Kelas / fakultas wajib diisi.' : ''),
        ];
        if (results.includes(false)) {
            ev.preventDefault();
            $('.is-invalid', ev.target)?.focus();
        }
    });

    $('#formRegisterMember')?.addEventListener('submit', (ev) => {
        const name = $('#memberName');
        const email = $('#memberEmail');
        const inst = $('#memberInstitution');
        const results = [
            setError(name, 'err-memberName', name.value.trim().length < 3 ? 'Nama minimal 3 huruf.' : ''),
            setError(email, 'err-memberEmail', emailPattern.test(email.value.trim()) ? '' : 'Format email belum benar.'),
            setError(inst, 'err-memberInstitution', inst.value.trim().length < 3 ? 'Kelas / fakultas wajib diisi.' : ''),
        ];
        if (results.includes(false)) {
            ev.preventDefault();
            $('.is-invalid', ev.target)?.focus();
        }
    });

    $('#formAddEbook')?.addEventListener('submit', (ev) => {
        const rate = parseFloat(ebookRate.value);
        const stock = parseInt($('#ebookStock').value, 10);
        const synopsis = $('#ebookSynopsis');
        const results = [
            setError(ebookTitle, 'err-ebookTitle', ebookTitle.value.trim().length < 4 ? 'Judul minimal 4 huruf.' : ''),
            setError(ebookAuthor, 'err-ebookAuthor', ebookAuthor.value.trim().length < 3 ? 'Nama penulis minimal 3 huruf.' : ''),
            setError(ebookRate, 'err-ebookDailyRate', isNaN(rate) || rate < 1000 ? 'Harga minimal Rp 1.000 per hari.' : ''),
            setError($('#ebookStock'), 'err-ebookStock', isNaN(stock) || stock < 1 ? 'Stok minimal 1.' : ''),
            setError(synopsis, 'err-ebookSynopsis', synopsis.value.trim().length < 15 ? 'Sinopsis minimal 15 huruf.' : ''),
        ];
        if (results.includes(false)) {
            ev.preventDefault();
            $('.is-invalid', ev.target)?.focus();
        }
    });

    $$('.field input, .field textarea').forEach((input) => {
        input.addEventListener('input', () => {
            if (!input.classList.contains('is-invalid')) return;
            input.classList.remove('is-invalid');
            const err = input.parentElement.querySelector('.field__error');
            if (err) err.textContent = '';
        });
    });

    /* ---------- 9. Animasi Hero Book Deck, Parallax, Count-Up, Topbar, & Scroll Reveal ---------- */
    const topbar = $('.topbar');
    const syncTopbarShadow = () => topbar?.classList.toggle('is-scrolled', window.scrollY > 12);
    window.addEventListener('scroll', syncTopbarShadow, { passive: true });
    syncTopbarShadow();

    /* Interactive Dual-Mode Hero Deck (Tim Penyusun 3D Portraits <-> Buku Sorotan) */
    const heroStage = $('#heroStage');
    const heroModeBtns = $$('[data-hero-mode]', heroStage || document);
    const heroPanes = $$('[data-hero-pane]', heroStage || document);

    heroModeBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.heroMode;
            heroModeBtns.forEach((b) => {
                const on = b.dataset.heroMode === mode;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            heroPanes.forEach((pane) => {
                pane.hidden = pane.dataset.heroPane !== mode;
            });
        });
    });

    /* Hero Pane 1: 3D Team Portrait Deck */
    const heroTeamShowcase = $('#heroTeamShowcase');
    const heroTeamItems = $$('[data-hero-team-idx]', heroTeamShowcase || document);
    const heroTeamDots = $$('[data-hero-team-dot]', heroStage || document);
    const heroTeamTitle = $('#heroTeamTitle');
    const heroTeamMeta = $('#heroTeamMeta');
    const heroTeamCopy = $('#heroTeamCopy');

    if (heroTeamShowcase && heroTeamItems.length > 0) {
        let activeTeamIdx = heroTeamItems.length >= 3 ? 1 : 0;

        function applyHeroTeam(centerIdx, revealIdentity = true) {
            const len = heroTeamItems.length;
            activeTeamIdx = ((centerIdx % len) + len) % len;
            const slotMap = len === 1 ? ['center'] : len === 2 ? ['center', 'right'] : ['center', 'right', 'left'];

            heroTeamItems.forEach((item, idx) => {
                const offset = (idx - activeTeamIdx + len) % len;
                const slot = slotMap[offset] || 'center';
                item.setAttribute('data-slot', slot);
                item.setAttribute('aria-pressed', slot === 'center' ? 'true' : 'false');
            });

            heroTeamDots.forEach((dot, idx) => {
                const on = idx === activeTeamIdx;
                dot.classList.toggle('is-active', on);
                dot.setAttribute('aria-selected', on ? 'true' : 'false');
            });

            const activeItem = heroTeamItems[activeTeamIdx];
            if (revealIdentity && activeItem && heroTeamTitle && heroTeamMeta) {
                heroTeamTitle.textContent = activeItem.dataset.memberName || '';
                heroTeamMeta.textContent = `${activeItem.dataset.memberRole || ''} · NIM ${activeItem.dataset.memberNim || ''}`;
                if (heroTeamCopy) {
                    heroTeamCopy.dataset.copyNim = activeItem.dataset.memberNim || '';
                    heroTeamCopy.hidden = false;
                }
            }
        }

        heroTeamItems.forEach((item) => {
            item.addEventListener('click', () => applyHeroTeam(Number(item.dataset.heroTeamIdx), true));
        });
        heroTeamDots.forEach((dot) => {
            dot.addEventListener('click', () => applyHeroTeam(Number(dot.dataset.heroTeamDot), true));
        });

        if (!reduceMotion) {
            heroTeamShowcase.addEventListener('pointermove', (ev) => {
                if (ev.pointerType !== 'mouse') return;
                const rect = heroTeamShowcase.getBoundingClientRect();
                const dx = ((ev.clientX - rect.left) / rect.width - 0.5) * 2;
                const dy = ((ev.clientY - rect.top) / rect.height - 0.5) * 2;
                heroTeamShowcase.style.setProperty('--tilt-y', `${(dx * 6).toFixed(2)}deg`);
                heroTeamShowcase.style.setProperty('--tilt-x', `${(-dy * 5).toFixed(2)}deg`);
            });
            heroTeamShowcase.addEventListener('pointerleave', () => {
                heroTeamShowcase.style.setProperty('--tilt-y', '0deg');
                heroTeamShowcase.style.setProperty('--tilt-x', '0deg');
            });
        }

        applyHeroTeam(activeTeamIdx, false);
    }

    /* Hero Pane 2: 3D Book Spotlight Deck */
    const heroShowcase = $('#heroShowcase');
    const heroItems = $$('[data-hero-idx]', heroShowcase || document);
    const heroDots = $$('[data-hero-dot]', heroStage || document);
    const heroDeckTitle = $('#heroDeckTitle');
    const heroDeckMeta = $('#heroDeckMeta');
    const heroDeckOpen = $('#heroDeckOpen');

    if (heroShowcase && heroItems.length > 0) {
        let activeHeroIdx = heroItems.length >= 3 ? 1 : 0;
        let heroPaused = false;
        let heroTimer = null;

        function applyHeroDeck(centerIdx) {
            const len = heroItems.length;
            activeHeroIdx = ((centerIdx % len) + len) % len;
            const slotMap = len === 1 ? ['center'] : len === 2 ? ['center', 'right'] : ['center', 'right', 'left'];

            heroItems.forEach((item, idx) => {
                const offset = (idx - activeHeroIdx + len) % len;
                const slot = slotMap[offset] || 'center';
                item.setAttribute('data-slot', slot);
                item.setAttribute('aria-pressed', slot === 'center' ? 'true' : 'false');
            });

            heroDots.forEach((dot, idx) => {
                const on = idx === activeHeroIdx;
                dot.classList.toggle('is-active', on);
                dot.setAttribute('aria-selected', on ? 'true' : 'false');
            });

            const activeItem = heroItems[activeHeroIdx];
            if (activeItem && heroDeckTitle && heroDeckMeta) {
                heroDeckTitle.textContent = activeItem.dataset.heroTitle || '';
                heroDeckMeta.textContent = `${activeItem.dataset.heroPrice || ''} · Klik sampul untuk detail`;
            }
        }

        function resetHeroTimer() {
            clearInterval(heroTimer);
            if (reduceMotion || heroItems.length < 2) return;
            heroTimer = setInterval(() => {
                const booksPane = $('#heroBooksPane');
                if (heroPaused || currentTab !== 'home' || $('dialog.modal[open]') || booksPane?.hidden) return;
                applyHeroDeck(activeHeroIdx + 1);
            }, 4200);
        }

        heroItems.forEach((item) => {
            item.addEventListener('click', () => {
                if (item.getAttribute('data-slot') === 'center') {
                    openBookDetail(item.dataset.heroBook);
                } else {
                    applyHeroDeck(Number(item.dataset.heroIdx));
                    resetHeroTimer();
                }
            });
        });

        heroDots.forEach((dot) => {
            dot.addEventListener('click', () => {
                applyHeroDeck(Number(dot.dataset.heroDot));
                resetHeroTimer();
            });
        });

        heroDeckOpen?.addEventListener('click', () => {
            const activeItem = heroItems[activeHeroIdx];
            if (activeItem) openBookDetail(activeItem.dataset.heroBook);
        });

        heroStage?.addEventListener('mouseenter', () => { heroPaused = true; });
        heroStage?.addEventListener('mouseleave', () => { heroPaused = false; });
        heroStage?.addEventListener('focusin', () => { heroPaused = true; });
        heroStage?.addEventListener('focusout', () => { heroPaused = false; });

        if (!reduceMotion) {
            heroShowcase.addEventListener('pointermove', (ev) => {
                if (ev.pointerType !== 'mouse') return;
                const rect = heroShowcase.getBoundingClientRect();
                const dx = ((ev.clientX - rect.left) / rect.width - 0.5) * 2;
                const dy = ((ev.clientY - rect.top) / rect.height - 0.5) * 2;
                heroShowcase.style.setProperty('--tilt-y', `${(dx * 6).toFixed(2)}deg`);
                heroShowcase.style.setProperty('--tilt-x', `${(-dy * 5).toFixed(2)}deg`);
            });
            heroShowcase.addEventListener('pointerleave', () => {
                heroShowcase.style.setProperty('--tilt-y', '0deg');
                heroShowcase.style.setProperty('--tilt-x', '0deg');
            });
        }

        applyHeroDeck(activeHeroIdx);
        resetHeroTimer();
    }

    /* Count-up ringan untuk angka statistik di Hero */
    if (!reduceMotion) {
        $$('.js-count[data-count]').forEach((el) => {
            const target = parseInt(el.dataset.count || '0', 10);
            if (!target || target <= 0) return;
            const duration = 680;
            const start = performance.now();
            el.textContent = '0';
            function step(now) {
                const p = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = String(Math.round(target * eased));
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    }

    /* Safe Scroll Reveal (IntersectionObserver + fallback per halaman) */
    if (!reduceMotion && 'IntersectionObserver' in window) {
        $$('.book-card, .tier-card, .member-card, .loan-card, .rubric-card').forEach((el, idx) => {
            if (!el.hasAttribute('data-reveal')) {
                el.setAttribute('data-reveal', '');
                el.style.setProperty('--reveal-delay', `${(idx % 5) * 35}ms`);
            }
        });

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.01, rootMargin: '140px 0px 140px 0px' });

        revealActivePage = (scope = document) => {
            const targets = $$('[data-reveal]', scope);
            const vh = window.innerHeight || 900;
            const isTabSwitch = scope instanceof Element && scope.id !== 'page-home';
            targets.forEach((el) => {
                const rect = el.getBoundingClientRect();
                if (rect.height > 0 && (isTabSwitch || rect.top < vh + 140)) {
                    el.classList.add('is-revealed');
                    revealObserver.unobserve(el);
                } else if (!el.classList.contains('is-revealed')) {
                    revealObserver.observe(el);
                }
            });
        };

        document.documentElement.classList.add('js-motion');
        revealActivePage($('.page.is-active') || document);
    }

    /* ---------- 10. Full-Site Motion Suite (Nav Indicator, Scroll Bar, Kinetic Hero, 3D Tilt Cards, Quota, Ripple) ---------- */
    const scrollBar = $('#scrollProgressBar');
    const syncScrollProgress = () => {
        if (!scrollBar) return;
        const doc = document.documentElement;
        const max = Math.max(1, doc.scrollHeight - window.innerHeight);
        const ratio = Math.min(1, Math.max(0, window.scrollY / max));
        scrollBar.style.transform = `scaleX(${ratio.toFixed(4)})`;
    };
    window.addEventListener('scroll', syncScrollProgress, { passive: true });
    window.addEventListener('resize', syncScrollProgress, { passive: true });
    syncScrollProgress();

    const mainNav = $('#mainNav');
    const navIndicator = $('#navIndicator');
    syncNavIndicator = () => {
        if (!mainNav || !navIndicator) return;
        const active = $('.nav-link.is-active', mainNav);
        if (!active || active.offsetWidth === 0) return;
        navIndicator.style.width = `${Math.max(12, active.offsetWidth - 20)}px`;
        navIndicator.style.transform = `translateX(${active.offsetLeft + 10}px)`;
        mainNav.classList.add('has-indicator');
    };
    syncNavIndicator();
    window.addEventListener('resize', syncNavIndicator, { passive: true });
    document.fonts?.ready?.then(syncNavIndicator);

    animateQuotaBars = () => {
        if (reduceMotion) return;
        $$('.quota span[data-quota-pct]').forEach((bar) => {
            const pct = bar.dataset.quotaPct || '0';
            bar.style.width = '0%';
            void bar.offsetWidth;
            requestAnimationFrame(() => {
                bar.style.width = `${pct}%`;
            });
        });
    };
    if (currentTab === 'members') animateQuotaBars();

    /* Kinetic Headline Rotator */
    const rotator = $('#heroRotator');
    const rotatorText = rotator?.querySelector('.hero-rotator__text');
    if (rotator && rotatorText && !reduceMotion) {
        const phrases = (rotator.dataset.phrases || '').split('|').map((s) => s.trim()).filter(Boolean);
        let phraseIdx = 0;
        if (phrases.length > 1) {
            setInterval(() => {
                if (currentTab !== 'home' || $('dialog.modal[open]')) return;
                phraseIdx = (phraseIdx + 1) % phrases.length;
                rotatorText.classList.add('is-exit');
                setTimeout(() => {
                    rotatorText.textContent = phrases[phraseIdx];
                    rotatorText.classList.remove('is-exit');
                    rotatorText.classList.add('is-enter');
                    void rotatorText.offsetWidth;
                    rotatorText.classList.remove('is-enter');
                }, 340);
            }, 3400);
        }
    }

    if (!reduceMotion) {
        /* Cursor-tracked ambient spotlight on Hero background */
        const heroBanner = $('.hero-banner');
        heroBanner?.addEventListener('pointermove', (ev) => {
            if (ev.pointerType !== 'mouse') return;
            const rect = heroBanner.getBoundingClientRect();
            const x = ((ev.clientX - rect.left) / rect.width) * 100;
            const y = ((ev.clientY - rect.top) / rect.height) * 100;
            heroBanner.style.setProperty('--hx', `${x.toFixed(1)}%`);
            heroBanner.style.setProperty('--hy', `${y.toFixed(1)}%`);
        });

        /* Subtle magnetic follow on Hero CTA buttons */
        $$('.btn--magnetic').forEach((btn) => {
            btn.addEventListener('pointermove', (ev) => {
                if (ev.pointerType !== 'mouse') return;
                const rect = btn.getBoundingClientRect();
                const dx = ((ev.clientX - rect.left) / rect.width - 0.5) * 10;
                const dy = ((ev.clientY - rect.top) / rect.height - 0.5) * 6;
                btn.style.transform = `translate3d(${dx.toFixed(1)}px, ${dy.toFixed(1)}px, 0)`;
            });
            btn.addEventListener('pointerleave', () => {
                btn.style.transform = '';
            });
        });

        /* 3D Tilt & Studio Glare on Tier & Member Cards */
        $$('.tier-card, .member-card').forEach((card) => {
            card.addEventListener('pointermove', (ev) => {
                if (ev.pointerType !== 'mouse') return;
                const rect = card.getBoundingClientRect();
                const px = (ev.clientX - rect.left) / rect.width;
                const py = (ev.clientY - rect.top) / rect.height;
                const rx = (0.5 - py) * 6;
                const ry = (px - 0.5) * 7;
                card.style.setProperty('--gx', `${(px * 100).toFixed(1)}%`);
                card.style.setProperty('--gy', `${(py * 100).toFixed(1)}%`);
                card.style.transform = `perspective(900px) rotateX(${rx.toFixed(2)}deg) rotateY(${ry.toFixed(2)}deg) translateY(-3px)`;
            });
            card.addEventListener('pointerleave', () => {
                card.style.transform = '';
            });
        });

        /* Tactile Click Ripple on Interactive Controls */
        document.addEventListener('pointerdown', (ev) => {
            const target = ev.target.closest('.btn, .chip, .cat-pill, .day-chip');
            if (!target || target.disabled) return;
            const rect = target.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const ripple = document.createElement('span');
            ripple.className = 'ripple-fx';
            ripple.style.width = `${size}px`;
            ripple.style.height = `${size}px`;
            ripple.style.left = `${ev.clientX - rect.left - size / 2}px`;
            ripple.style.top = `${ev.clientY - rect.top - size / 2}px`;
            target.appendChild(ripple);
            ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
            setTimeout(() => ripple.remove(), 580);
        });
    }

    /* ---------- 11. Galeri Tim Penyusun Interaktif (Expanding Portrait Panel) ---------- */
    const teamPanels = $$('[data-team-panel]');
    const btnToggleAllTeam = $('#btnToggleAllTeam');
    if (teamPanels.length > 0) {
        const syncTeamToggleBtn = () => {
            if (!btnToggleAllTeam) return;
            const allOpen = teamPanels.every((p) => p.classList.contains('is-active'));
            btnToggleAllTeam.setAttribute('aria-pressed', allOpen ? 'true' : 'false');
            btnToggleAllTeam.textContent = allOpen ? 'Sembunyikan profil' : 'Tampilkan semua profil';
        };

        const toggleTeamPanel = (panel) => {
            const willOpen = !panel.classList.contains('is-active');
            const allWereOpen = teamPanels.every((p) => p.classList.contains('is-active'));
            if (willOpen && !allWereOpen) {
                teamPanels.forEach((p) => {
                    const active = p === panel;
                    p.classList.toggle('is-active', active);
                    p.setAttribute('aria-expanded', active ? 'true' : 'false');
                });
            } else {
                panel.classList.toggle('is-active', willOpen);
                panel.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            }
            syncTeamToggleBtn();
        };

        teamPanels.forEach((panel) => {
            panel.addEventListener('click', (ev) => {
                if (ev.target.closest('[data-copy-nim]')) return;
                toggleTeamPanel(panel);
            });
            panel.addEventListener('keydown', (ev) => {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    toggleTeamPanel(panel);
                }
            });
        });

        btnToggleAllTeam?.addEventListener('click', () => {
            const shouldOpen = btnToggleAllTeam.getAttribute('aria-pressed') !== 'true';
            teamPanels.forEach((p) => {
                p.classList.toggle('is-active', shouldOpen);
                p.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            });
            syncTeamToggleBtn();
        });

        $$('[data-copy-nim]').forEach((btn) => {
            btn.addEventListener('click', (ev) => {
                ev.stopPropagation();
                const nim = btn.dataset.copyNim || '';
                if (!nim) return;
                const origLabel = btn.dataset.origLabel || btn.textContent;
                btn.dataset.origLabel = origLabel;
                navigator.clipboard?.writeText(nim).catch(() => {});
                btn.textContent = 'Tersalin ✓';
                setTimeout(() => { btn.textContent = origLabel; }, 1400);
            });
        });
    }
});
