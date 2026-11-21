# ARKADIA — Digital E-Book Lending & Storefront

Prototipe web interaktif manajemen katalog dan transaksi penyewaan e-book untuk **UTS Pemrograman Web Semester Ganjil 2026/2027** (Program Studi Informatika — Fakultas Teknik Universitas Muhammadiyah Malang). Dibangun menggunakan **HTML5 Semantik, CSS3, Vanilla ES6 JavaScript**, dan **PHP 8.x OOP murni tanpa framework** dengan penyimpanan **JSON & ekspor XML**.

## Pemenuhan Rubrik Penilaian UTS (100 Poin)

| Aspek Penilaian | Bobot | Implementasi Nyata di Proyek |
| :--- | :--- | :--- |
| **Front-End (HTML5, CSS3, JS)** | **30 poin** | Semantic HTML5 (`header`, `nav`, `main`, `section`, `article`, `dialog`, `footer`), validasi form client-side + server-side, CSS Grid/Flexbox responsif, tema **Terang & Gelap**, 3D Hero Book Deck & animasi 60fps, sortir katalog, **Mini E-Reader**, dan kalkulasi harga real-time via `fetch()`. |
| **PHP OOP (Backend)** | **40 poin** | 16 class & interface terstruktur dalam 5 namespace dengan properti terenkapsulasi (`private`), pewarisan (`MediaItem` &rarr; 3 subclass, `User` &rarr; 3 subclass), **polimorfisme tarif & diskon**, `Transaction`, `LibraryManager`, `AuthService`, serta inspeksi live **Reflection API** di menu **Info Sistem**. |
| **Struktur Berkas & Kualitas** | **30 poin** | Pemisahan bersih `src/` (autoload PSR-4, 1 class/interface per file), `views/` modular, `assets/` terpisah, penyimpanan persisten `data/*.json` & `data/ebooks.xml`, bebas error/warning pada PHP 8.2+. |

## Fitur Utama Aplikasi

- **Beranda Interaktif & Animasi Modern** — *Hero Book Deck* 3D interaktif yang dapat diklik/diputar, *kinetic headline rotator*, *marquee* informasi, kilau sampul saat *hover*, *morphing nav indicator*, serta dukungan `prefers-reduced-motion`.
- **Katalog Grid, Daftar, Pencarian, & Sortir** — Cari judul/penulis/kategori, filter jenis media & ketersediaan stok, sortir (*Terbaru*, *Harga Termurah*, *Harga Termahal*, *Halaman Terbanyak*), serta alih tampilan **Grid ↔ Daftar**.
- **Sistem Anggota 3 Tier & Demo 1-Klik** — Daftar atau masuk sebagai **Anggota Reguler** (0%, maks 7 hari, 2 buku), **Anggota Premium** (20%, bebas biaya layanan, maks 14 hari, 5 buku), atau **Dosen & Peneliti** (30%, maks 21 hari, 8 buku) menggunakan autentikasi sesi PHP.
- **Sewa Real-Time & Perpanjang (+3 Hari) Polimorfik** — Total biaya & diskon dihitung langsung oleh server PHP melalui endpoint `?api=simulate`, serta pinjaman aktif dapat diperpanjang (`+3 hari`) sesuai batas tier anggota.
- **Mini Web E-Reader & Struk Digital Cetak PDF** — Baca cuplikan atau naskah berlisensi penuh dengan kontrol ukuran huruf (`A−`/`A+`) dan 3 mode kertas (**Terang**, **Sepia**, **Gelap**), plus modal **Struk Digital** lengkap dengan Kode Invoice, Token DRM, dan tombol **Cetak / Simpan PDF**.

## Arsitektur PHP 8.x OOP (`src/` — 16 Class & Interface)

| Layer | Namespace | Komponen OOP |
| :--- | :--- | :--- |
| **Contracts** | `App\Contracts` | `RentableInterface`, `SerializableEntityInterface` |
| **Media Hierarchy** | `App\Models` | `MediaItem` *(Abstract)* &rarr; `StandardEBook`, `AcademicMonograph`, `InteractiveMediaBook` |
| **User Hierarchy** | `App\Models` | `User` *(Abstract)* &rarr; `RegularMember`, `PremiumMember`, `FacultyMember` |
| **Transaction Entity** | `App\Models` | `Transaction` |
| **Domain Services** | `App\Services` | `LibraryManager`, `AuthService`, `JsonStorageService`, `OopInspectorService` |
| **Controller & Helper** | `App\Controllers`, `App\Support` | `RequestController`, `ViewHelper` |

## Struktur Berkas

```text
├── autoload.php                # Autoloader PSR-4 (App\ -> src/)
├── index.php                   # Entry point aplikasi (session, RequestController, & views/)
├── assets/
│   ├── css/style.css           # Design system, Dark Mode, 3D Hero Deck, E-Reader, & Print CSS
│   └── js/app.js               # Navigasi, motion 60fps, filter/sort, E-Reader, Struk, & fetch()
├── data/
│   ├── ebooks.json             # Basis data katalog e-book (JSON)
│   ├── ebooks.xml              # Ekspor katalog otomatis (XML)
│   ├── members.json            # Data anggota terdaftar (JSON)
│   └── transactions.json       # Riwayat & status peminjaman aktif (JSON)
├── views/
│   ├── layout/                 # header.php, footer.php
│   ├── pages/                  # home.php, catalog.php, loans.php, members.php, manage.php, system.php
│   └── partials/               # modals.php (Detail, Sewa, Auth, E-Reader, Struk Digital)
└── src/
    ├── Contracts/
    ├── Controllers/
    ├── Models/
    ├── Services/
    └── Support/
```

## Panduan Menjalankan Secara Lokal

**Prasyarat:** **PHP 8.1 atau lebih baru** dan browser modern (tidak memerlukan database eksternal maupun Composer).

### Opsi 1 — PHP Built-in Server (Direkomendasikan)

1. Buka terminal (PowerShell / Command Prompt / Git Bash) dan masuk ke folder proyek:
   ```bash
   cd "d:\uts\uts web"
   ```
2. Jalankan server pengembangan PHP:
   ```bash
   php -S 127.0.0.1:8088
   ```
3. Buka **`http://127.0.0.1:8088`** di browser.

### Opsi 2 — Laragon / XAMPP

1. Salin atau letakkan folder proyek ke `C:\laragon\www\arkadia` *(Laragon)* atau `C:\xampp\htdocs\arkadia` *(XAMPP)*.
2. Pastikan modul **Apache** sudah aktif.
3. Buka **`http://localhost/arkadia`** di browser.

### Panduan Uji Cepat untuk Penilaian

- **Uji Polimorfisme & Sewa:** Klik **Masuk** &rarr; pilih salah satu tombol **akun demo 1-klik** (Reguler / Premium / Dosen), lalu sewa buku di **Katalog** untuk melihat perbedaan diskon, biaya layanan, dan batas hari secara langsung.
- **Uji Arsitektur OOP:** Buka menu **Info Sistem** untuk melihat ringkasan pemenuhan rubrik dan struktur seluruh **16 class/interface** yang dibaca langsung oleh **PHP Reflection API**.
- **Reset Data Demo:** Klik ikon **Reset** (panah melingkar) di kanan atas header untuk mengembalikan data awal ke `6` e-book, `4` anggota, dan `4` transaksi contoh.
