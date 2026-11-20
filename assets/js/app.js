/**
 * ARKADIA — Front-End Interactivity, Dual-Theme Switcher, Polymorphic Rental Calculator
 * & Client-Side Form Validation (Vanilla ES6 JavaScript)
 */
document.addEventListener('DOMContentLoaded', () => {
    const state = window.ARKADIA_STATE || { catalog: [], members: [] };

    // --- 0. DARK / LIGHT THEME SWITCHER ---
    const btnThemeToggle = document.getElementById('btnThemeToggle');
    if (btnThemeToggle) {
        btnThemeToggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try {
                localStorage.setItem('arkadia-theme', next);
            } catch (e) {}
        });
    }

    // --- 1. TAB NAVIGATION ---
    const tabButtons = document.querySelectorAll('.nav-tab[data-tab-target]');
    const tabPanels = document.querySelectorAll('.tab-panel');

    function activateTab(targetId) {
        tabButtons.forEach((btn) => {
            const isMatch = btn.getAttribute('data-tab-target') === targetId;
            btn.classList.toggle('is-active', isMatch);
            btn.setAttribute('aria-selected', isMatch ? 'true' : 'false');
        });

        tabPanels.forEach((panel) => {
            const isMatch = panel.id === `panel-${targetId}`;
            panel.classList.toggle('is-active', isMatch);
        });
    }

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-tab-target');
            activateTab(target);
        });
    });

    // --- 2. LIVE SEARCH & FILTER KATALOG ---
    const searchInput = document.getElementById('catalogSearch');
    const filterCategory = document.getElementById('filterCategory');
    const filterMediaType = document.getElementById('filterMediaType');
    const filterAvailability = document.getElementById('filterAvailability');
    const bookCards = document.querySelectorAll('.book-card');
    const emptyState = document.getElementById('catalogEmptyState');
    const btnResetFilter = document.getElementById('btnResetFilter');

    function applyCatalogFilters() {
        if (!searchInput) return;
        const query = searchInput.value.trim().toLowerCase();
        const cat = filterCategory.value;
        const type = filterMediaType.value;
        const avail = filterAvailability.value;

        let visibleCount = 0;

        bookCards.forEach((card) => {
            const matchQuery = !query || card.dataset.title.includes(query);
            const matchCat = cat === 'all' || card.dataset.category === cat;
            const matchType = type === 'all' || card.dataset.type === type;
            const matchAvail = avail === 'all' || card.dataset.available === '1';

            const isVisible = matchQuery && matchCat && matchType && matchAvail;
            card.hidden = !isVisible;
            if (isVisible) visibleCount++;
        });

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
        }
    }

    [searchInput, filterCategory, filterMediaType, filterAvailability].forEach((el) => {
        if (el) {
            el.addEventListener('input', applyCatalogFilters);
            el.addEventListener('change', applyCatalogFilters);
        }
    });

    if (btnResetFilter) {
        btnResetFilter.addEventListener('click', () => {
            searchInput.value = '';
            filterCategory.value = 'all';
            filterMediaType.value = 'all';
            filterAvailability.value = 'all';
            applyCatalogFilters();
        });
    }

    // --- 3. POLYMORPHIC RENTAL SIMULATOR & LIVE RECEIPT ---
    const simMemberSelect = document.getElementById('simMemberId');
    const simEbookSelect = document.getElementById('simEbookId');
    const simDurationSlider = document.getElementById('simDurationSlider');
    const simDurationInput = document.getElementById('simDurationDays');
    const simDurationBadge = document.getElementById('simDurationBadge');
    const simMemberMeta = document.getElementById('simMemberMeta');
    const simEbookMeta = document.getElementById('simEbookMeta');
    const simMaxDaysHint = document.getElementById('simMaxDaysHint');
    const simValidationAlert = document.getElementById('simValidationAlert');
    const btnSubmitRental = document.getElementById('btnSubmitRental');
    const durationPresetButtons = document.querySelectorAll('.duration-preset-btn');

    const receiptClassTrace = document.getElementById('receiptClassTrace');
    const receiptBaseLabel = document.getElementById('receiptBaseLabel');
    const receiptBaseValue = document.getElementById('receiptBaseValue');
    const receiptServiceLabel = document.getElementById('receiptServiceLabel');
    const receiptServiceValue = document.getElementById('receiptServiceValue');
    const receiptDiscountLabel = document.getElementById('receiptDiscountLabel');
    const receiptDiscountValue = document.getElementById('receiptDiscountValue');
    const receiptDueDate = document.getElementById('receiptDueDate');
    const receiptTotalValue = document.getElementById('receiptTotalValue');

    function formatRupiahJS(amount) {
        return 'Rp ' + Math.round(amount).toLocaleString('id-ID');
    }

    function findMember(id) {
        return state.members.find((m) => m.id === id) || state.members[0];
    }

    function findBook(id) {
        return state.catalog.find((b) => b.id === id) || state.catalog[0];
    }

    function syncDurationPresetChips(days) {
        durationPresetButtons.forEach((btn) => {
            const presetDays = parseInt(btn.getAttribute('data-days'), 10);
            btn.classList.toggle('is-active', presetDays === days);
        });
    }

    function updateRentalSimulation() {
        if (!simMemberSelect || !simEbookSelect) return;

        const member = findMember(simMemberSelect.value);
        const book = findBook(simEbookSelect.value);
        let days = parseInt(simDurationInput.value, 10);

        if (isNaN(days) || days < 1) {
            days = 1;
        }

        simDurationBadge.textContent = `${days} Hari`;
        if (document.activeElement !== simDurationSlider) {
            simDurationSlider.value = Math.min(21, Math.max(1, days));
        }
        syncDurationPresetChips(days);

        if (!member || !book) return;

        simMemberMeta.innerHTML = `<strong>${member.tier_label}</strong> • Kuota: ${member.active_loans}/${member.max_concurrent_loans} buku • Maks ${member.max_borrow_days} hari`;
        simEbookMeta.innerHTML = `<strong>${book.media_type_label}</strong> • Stok: ${book.stock}/${book.total_stock} lisensi`;
        simMaxDaysHint.textContent = `Maksimal ${member.max_borrow_days} hari untuk ${member.tier_label}`;

        const memberClassMap = {
            regular: 'RegularMember',
            premium: 'PremiumMember',
            faculty: 'FacultyMember'
        };
        const bookClassMap = {
            standard: 'StandardEBook',
            academic: 'AcademicMonograph',
            interactive: 'InteractiveMediaBook'
        };
        receiptClassTrace.textContent = `${memberClassMap[member.tier] || 'User'} × ${bookClassMap[book.media_type] || 'MediaItem'}`;

        let baseCost = book.daily_rate * days;
        let baseNote = `${formatRupiahJS(book.daily_rate)} × ${days} hr`;

        if (book.media_type === 'standard' && days >= 7) {
            baseCost = Math.round(baseCost * 0.95);
            baseNote += ' (Diskon >=7hr 5%)';
        } else if (book.media_type === 'academic') {
            baseCost += 2500;
            baseNote += ' (+Sitasi DOI Rp 2.500)';
        } else if (book.media_type === 'interactive') {
            baseCost = (book.daily_rate + 1000) * days;
            baseNote = `(${formatRupiahJS(book.daily_rate)} + Lab Rp 1.000) × ${days} hr`;
        }

        const rawServiceFee = book.service_fee;
        const appliedServiceFee = member.waives_service_fee ? 0 : rawServiceFee;
        const discountAmount = Math.round(baseCost * member.discount_rate);
        const totalCost = Math.max(0, baseCost + appliedServiceFee - discountAmount);

        const due = new Date();
        due.setDate(due.getDate() + days);
        const dueStr = due.toISOString().split('T')[0];

        receiptBaseLabel.textContent = `Biaya Pokok (${baseNote})`;
        receiptBaseValue.textContent = formatRupiahJS(baseCost);

        receiptServiceLabel.textContent = member.waives_service_fee
            ? 'Biaya Layanan (Gratis VIP)'
            : `Biaya Layanan (${book.media_type_label})`;
        receiptServiceValue.textContent = formatRupiahJS(appliedServiceFee);

        const discPct = Math.round(member.discount_rate * 100);
        receiptDiscountLabel.textContent = `Diskon ${member.tier_label} (${discPct}%)`;
        receiptDiscountValue.textContent = '-' + formatRupiahJS(discountAmount);
        receiptDueDate.textContent = dueStr;
        receiptTotalValue.textContent = formatRupiahJS(totalCost);

        const errors = [];
        if (book.stock <= 0) {
            errors.push(`Stok lisensi "${book.title}" sedang habis.`);
        }
        if (member.active_loans >= member.max_concurrent_loans) {
            errors.push(`Kuota pinjaman ${member.name} (${member.max_concurrent_loans} buku) sudah penuh.`);
        }
        if (days > member.max_borrow_days) {
            errors.push(`Durasi ${days} hari melebihi batas ${member.max_borrow_days} hari untuk ${member.tier_label}.`);
        }

        if (errors.length > 0) {
            simValidationAlert.hidden = false;
            simValidationAlert.textContent = errors.join(' ');
            btnSubmitRental.disabled = true;
        } else {
            simValidationAlert.hidden = true;
            simValidationAlert.textContent = '';
            btnSubmitRental.disabled = false;
        }
    }

    durationPresetButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const presetDays = btn.getAttribute('data-days');
            if (simDurationInput && simDurationSlider && presetDays) {
                simDurationInput.value = presetDays;
                simDurationSlider.value = presetDays;
                updateRentalSimulation();
            }
        });
    });

    if (simDurationSlider && simDurationInput) {
        simDurationSlider.addEventListener('input', () => {
            simDurationInput.value = simDurationSlider.value;
            updateRentalSimulation();
        });
        simDurationInput.addEventListener('input', updateRentalSimulation);
    }

    if (simMemberSelect) simMemberSelect.addEventListener('change', updateRentalSimulation);
    if (simEbookSelect) simEbookSelect.addEventListener('change', updateRentalSimulation);

    updateRentalSimulation();

    // --- 4. TOMBOL "PINJAM" DARI KARTU KATALOG & DIREKTORI MEMBER ---
    document.querySelectorAll('.btn-select-rent').forEach((btn) => {
        btn.addEventListener('click', () => {
            const bookId = btn.getAttribute('data-book-id');
            if (simEbookSelect && bookId) {
                simEbookSelect.value = bookId;
                updateRentalSimulation();
                simEbookSelect.focus();
                simEbookSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });

    document.querySelectorAll('.btn-pick-member').forEach((btn) => {
        btn.addEventListener('click', () => {
            const memberId = btn.getAttribute('data-member-id');
            if (simMemberSelect && memberId) {
                simMemberSelect.value = memberId;
                updateRentalSimulation();
                simMemberSelect.focus();
            }
        });
    });

    // --- 5. MODAL DETAIL MONOGRAF / E-BOOK ---
    const modal = document.getElementById('bookDetailModal');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnModalSelectBook = document.getElementById('btnModalSelectBook');
    let currentModalBookId = null;

    document.querySelectorAll('.btn-inspect-book').forEach((btn) => {
        btn.addEventListener('click', () => {
            const bookId = btn.getAttribute('data-book-id');
            const book = findBook(bookId);
            if (!book || !modal) return;

            currentModalBookId = book.id;
            document.getElementById('modalBookType').textContent = book.media_type_label;
            document.getElementById('modalBookCategory').textContent = `${book.category} • ${book.publish_year}`;
            document.getElementById('modalBookTitle').textContent = book.title;
            document.getElementById('modalBookAuthor').textContent = `Oleh ${book.author}`;
            document.getElementById('modalBookSynopsis').textContent = book.synopsis;
            document.getElementById('modalBookIsbn').textContent = `${book.id} / ISBN ${book.isbn}`;
            document.getElementById('modalBookFormat').textContent = `${book.file_format} (${book.file_size_mb} MB • ${book.pages} hlm)`;
            document.getElementById('modalBookTech').textContent = book.technical_metadata;
            document.getElementById('modalBookRates').textContent = `${formatRupiahJS(book.daily_rate)}/hari (+Layanan ${formatRupiahJS(book.service_fee)})`;

            if (typeof modal.showModal === 'function') {
                modal.showModal();
            }
        });
    });

    if (btnCloseModal && modal) {
        btnCloseModal.addEventListener('click', () => modal.close());
    }

    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.close();
            }
        });
    }

    if (btnModalSelectBook && modal) {
        btnModalSelectBook.addEventListener('click', () => {
            if (currentModalBookId && simEbookSelect) {
                simEbookSelect.value = currentModalBookId;
                updateRentalSimulation();
            }
            modal.close();
        });
    }

    // --- 6. DYNAMIC SUBCLASS FIELDS PADA FORM TAMBAH E-BOOK ---
    const ebookMediaTypeSelect = document.getElementById('ebookMediaType');
    const groupDoi = document.getElementById('groupDoi');
    const groupAudio = document.getElementById('groupAudio');

    function syncSubclassFields() {
        if (!ebookMediaTypeSelect) return;
        const val = ebookMediaTypeSelect.value;
        if (groupDoi) groupDoi.hidden = val !== 'academic';
        if (groupAudio) groupAudio.hidden = val !== 'interactive';
    }

    if (ebookMediaTypeSelect) {
        ebookMediaTypeSelect.addEventListener('change', syncSubclassFields);
        syncSubclassFields();
    }

    // --- 7. CLIENT-SIDE FORM VALIDATION (REGISTRASI MEMBER & TAMBAH E-BOOK) ---
    function setFieldError(inputEl, errorId, message) {
        const errSpan = document.getElementById(errorId);
        if (message) {
            inputEl.classList.add('is-invalid');
            if (errSpan) errSpan.textContent = message;
            return false;
        } else {
            inputEl.classList.remove('is-invalid');
            if (errSpan) errSpan.textContent = '';
            return true;
        }
    }

    const formRegisterMember = document.getElementById('formRegisterMember');
    if (formRegisterMember) {
        formRegisterMember.addEventListener('submit', (e) => {
            const nameEl = document.getElementById('memberName');
            const emailEl = document.getElementById('memberEmail');
            const instEl = document.getElementById('memberInstitution');

            let valid = true;

            if (nameEl.value.trim().length < 3) {
                valid = setFieldError(nameEl, 'err-memberName', 'Nama lengkap minimal 3 karakter.') && valid;
            } else {
                setFieldError(nameEl, 'err-memberName', '');
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailEl.value.trim())) {
                valid = setFieldError(emailEl, 'err-memberEmail', 'Masukkan alamat email yang valid.') && valid;
            } else {
                setFieldError(emailEl, 'err-memberEmail', '');
            }

            if (instEl.value.trim().length < 3) {
                valid = setFieldError(instEl, 'err-memberInstitution', 'Afiliasi/institusi wajib diisi.') && valid;
            } else {
                setFieldError(instEl, 'err-memberInstitution', '');
            }

            if (!valid) {
                e.preventDefault();
            }
        });
    }

    const formAddEbook = document.getElementById('formAddEbook');
    if (formAddEbook) {
        formAddEbook.addEventListener('submit', (e) => {
            const titleEl = document.getElementById('ebookTitle');
            const authorEl = document.getElementById('ebookAuthor');
            const rateEl = document.getElementById('ebookDailyRate');
            const synopsisEl = document.getElementById('ebookSynopsis');

            let valid = true;

            if (titleEl.value.trim().length < 4) {
                valid = setFieldError(titleEl, 'err-ebookTitle', 'Judul e-book minimal 4 karakter.') && valid;
            } else {
                setFieldError(titleEl, 'err-ebookTitle', '');
            }

            if (authorEl.value.trim().length < 3) {
                valid = setFieldError(authorEl, 'err-ebookAuthor', 'Nama penulis minimal 3 karakter.') && valid;
            } else {
                setFieldError(authorEl, 'err-ebookAuthor', '');
            }

            const rateVal = parseFloat(rateEl.value);
            if (isNaN(rateVal) || rateVal < 1000) {
                valid = setFieldError(rateEl, 'err-ebookDailyRate', 'Tarif sewa minimal Rp 1.000/hari.') && valid;
            } else {
                setFieldError(rateEl, 'err-ebookDailyRate', '');
            }

            if (synopsisEl.value.trim().length < 15) {
                valid = setFieldError(synopsisEl, 'err-ebookSynopsis', 'Sinopsis minimal 15 karakter.') && valid;
            } else {
                setFieldError(synopsisEl, 'err-ebookSynopsis', '');
            }

            if (!valid) {
                e.preventDefault();
            }
        });
    }
});
