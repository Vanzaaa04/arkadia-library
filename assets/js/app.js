/**
 * ARKADIA — Interaksi halaman (Vanilla JavaScript, tanpa framework)
 *  1. Tema terang/gelap
 *  2. Navigasi antar halaman (tanpa reload)
 *  3. Autentikasi anggota (Masuk / Daftar / Akun Demo)
 *  4. Pencarian & filter katalog + filter peminjaman (Saya vs Semua)
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
    const findMember = (id) => state.members.find((m) => m.id === id);
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
        setTimeout(() => el.remove(), 260);
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

    function showPage(name) {
        if (!$(`#page-${name}`)) return;
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

    function openAuth(tabName = 'login', pendingBookId = '') {
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
        btn.addEventListener('click', () => openAuth(btn.dataset.openAuth || 'login', ''));
    });

    /* ---------- 4. Pencarian & filter katalog + scope peminjaman ---------- */
    const searchInput = $('#searchInput');
    const typeFilter = $('#typeFilter');
    const availableOnly = $('#availableOnly');
    const catalogCards = $$('#catalogGrid .book-card');
    const catalogEmpty = $('#catalogEmpty');
    const catalogCount = $('#catalogCount');
    let activeCategory = 'all';

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
    }

    searchInput?.addEventListener('input', applyFilters);
    typeFilter?.addEventListener('change', applyFilters);
    availableOnly?.addEventListener('change', applyFilters);
    $$('.chip[data-cat]').forEach((chip) => {
        chip.addEventListener('click', () => {
            activeCategory = chip.dataset.cat;
            $$('.chip[data-cat]').forEach((c) => c.classList.toggle('is-active', c === chip));
            applyFilters();
        });
    });
    $('#btnResetFilter')?.addEventListener('click', () => {
        searchInput.value = '';
        typeFilter.value = 'all';
        availableOnly.checked = false;
        activeCategory = 'all';
        $$('.chip[data-cat]').forEach((c) => c.classList.toggle('is-active', c.dataset.cat === 'all'));
        applyFilters();
    });

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

    /* ---------- 5. Modal detail buku ---------- */
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

    /* ---------- 6. Modal sewa (wajib akun login) ---------- */
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
        $('#billTotal').textContent = rupiah(q.total_cost);
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
});
