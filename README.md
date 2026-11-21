# ARKADIA — Digital E-Book Lending & Storefront

Prototipe web interaktif manajemen katalog dan transaksi penyewaan e-book untuk **UTS Pemrograman Web Semester Ganjil 2026/2027** (Program Studi Informatika — Fakultas Teknik Universitas Muhammadiyah Malang). Dibangun menggunakan **HTML5 Semantik, CSS3, Vanilla ES6 JavaScript**, dan **PHP 8.x OOP murni tanpa framework** dengan penyimpanan **JSON & ekspor XML**.

## Pemenuhan Rubrik Penilaian UTS (100 Poin)

| Aspek Penilaian | Bobot | Implementasi Nyata di Proyek |
| :--- | :--- | :--- |
| **Front-End (HTML5, CSS3, JS)** | **30 poin** | Semantic HTML5 (`header`, `nav`, `main`, `section`, `article`, `dialog`, `footer`), validasi form client-side + server-side, CSS Grid/Flexbox responsif, tema **Terang & Gelap**, 3D Hero Book Deck & animasi 60fps, sortir katalog, **Mini E-Reader**, dan kalkulasi harga real-time via `fetch()`. |
| **PHP OOP (Backend)** | **40 poin** | 16 class & interface terstruktur dalam 5 namespace dengan properti terenkapsulasi (`private`), pewarisan (`MediaItem` &rarr; 3 subclass, `User` &rarr; 3 subclass), **polimorfisme tarif & diskon**, `Transaction`, `LibraryManager`, `AuthService`, serta inspeksi live **Reflection API** di menu **Info Sistem**. |
| **Struktur Berkas & Kualitas** | **30 poin** | Pemisahan bersih `src/` (autoload PSR-4, 1 class/interface per file), `views/` modular, `assets/` terpisah, penyimpanan persisten `data/*.json` & `data/ebooks.xml`, bebas error/warning pada PHP 8.2+. |

## Jenis Akun, Hak Akses, & Akun Demo

Sistem membedakan **3 jenis keanggotaan OOP** (`RegularMember`, `PremiumMember`, `FacultyMember`) ditambah **mode Pengunjung (Tamu)** sebelum masuk. Tersedia **4 akun demo bawaan** di [members.json](data/members.json).

### Apa yang Bisa Dilakukan Masing-Masing Akun

| Peran / Tier | Diskon Sewa | Biaya Layanan | Maks. Durasi | Kuota Aktif | Hak Akses & Aktivitas |
| :--- | :---: | :--- | :---: | :---: | :--- |
| **Pengunjung (Tamu)** | — | — | — | — | Jelajah Beranda & Katalog, cari/filter/sortir buku, baca **cuplikan E-Reader**, lihat semua peminjaman, tambah buku/anggota, & buka **Info Sistem** *(untuk menyewa, diarahkan Masuk/Daftar)*. |
| **Anggota Reguler** (`RegularMember`) | **0%** | Tarif standar berlaku | **7 hari** | **2 buku** | Semua akses dasar + **sewa e-book**, baca naskah penuh (**Lisensi aktif**), **+3 hari perpanjangan** *(maks. 7 hari)*, **Struk Digital PDF**, & **kembalikan buku**. |
| **Anggota Premium** (`PremiumMember`) | **20%** | **Gratis** (`Rp 0`) | **14 hari** | **5 buku** | Semua fitur sewa dengan **potongan 20% + bebas biaya layanan**, perpanjangan hingga **14 hari**, & kelola pinjaman hingga **5 judul** bersamaan. |
| **Dosen & Peneliti** (`FacultyMember`) | **30%** | Tarif standar berlaku | **21 hari** | **8 buku** | Semua fitur sewa dengan **subsidi riset 30%**, durasi & perpanjangan hingga **21 hari**, serta kuota terbesar (**8 judul** aktif bersamaan). |

### 4 Akun Demo Siap Pakai

| ID / Kode | Nama | Email Login | Jenis Akun | Pinjaman Awal |
| :--- | :--- | :--- | :--- | :---: |
| `MBR-01` / `ARK-2026-001` | Aurelia Prameswari, M.Kom. | `aurelia.prameswari@umm.ac.id` | **Dosen & Peneliti** | 1 / 8 buku |
| `MBR-02` / `ARK-2026-002` | Raka Aditya Mahendra | `raka.mahendra@student.umm.ac.id` | **Anggota Premium** | 1 / 5 buku |
| `MBR-03` / `ARK-2026-003` | Nadine Kusuma Wardhani | `nadine.wardhani@webmail.umm.ac.id` | **Anggota Reguler** | 1 / 2 buku |
| `MBR-04` / `ARK-2026-004` | Dimas Setiawan Prakoso | `dimas.prakoso@alumni.umm.ac.id` | **Anggota Premium** | 0 / 5 buku |

> **Cara Masuk / Pindah Akun:** Klik **Masuk** di kanan atas header lalu pilih salah satu dari **3 tombol akun demo 1-klik** (Reguler / Premium / Dosen) atau masukkan email terdaftar di atas; bisa juga buka menu **Anggota** dan klik **Masuk akun ini** pada salah satu dari keempat kartu anggota, atau buat akun baru lewat tombol **Daftar**.

## Alur Penggunaan Web (Step-by-Step)

1. **Eksplorasi Beranda & Katalog** — Pengunjung langsung melihat hero interaktif *Book Deck*, strip kategori, dan galeri **Katalog** (bisa cari judul/penulis, filter jenis media & stok, sortir harga/halaman, dan alih tampilan **Grid ↔ Daftar**).
2. **Pratinjau Buku di Mini E-Reader** — Klik sampul buku manapun untuk melihat metadata teknis (ISBN/DOI/Audio Sandbox), lalu klik **Baca cuplikan** untuk mencoba mode baca web (*Terang / Sepia / Gelap* dan ukuran font `A−`/`A+`).
3. **Masuk atau Daftar Anggota** — Saat klik **Sewa** (atau lewat tombol **Masuk / Daftar** di header), pilih akun demo 1-klik, login dengan email, atau daftar akun baru. Jika sebelumnya mengklik **Sewa buku**, modal sewa otomatis terbuka setelah login.
4. **Simulasi Biaya Real-Time & Sewa** — Pilih durasi (`1`–`21` hari sesuai batas tier). Browser memanggil `fetch('index.php?api=simulate')` sehingga server PHP menghitung biaya sewa pokok, biaya layanan, diskon polimorfik, tanggal jatuh tempo, dan memvalidasi stok/kuota sebelum menyimpan transaksi ke JSON.
5. **Kelola Peminjaman Aktif & Struk PDF** — Di menu **Peminjaman** (*Pinjaman saya* atau *Semua anggota*), pengguna dapat klik **Baca** (*Lisensi aktif*), memperpanjang durasi **+3 hari** *(divalidasi terhadap batas tier di PHP)*, membuka **Struk Digital** untuk **Cetak / Simpan PDF**, atau klik **Kembalikan** untuk memulihkan stok buku & kuota pinjam.
6. **Manajemen Data, Info Sistem, & Reset Demo** — Tambahkan judul baru dengan pratinjau sampul langsung di **Tambah Buku**, periksa implementasi **16 class/interface OOP** via *Reflection API* di **Info Sistem**, dan gunakan tombol **Reset** di header kanan atas kapan saja untuk kembali ke data contoh awal.

## Fitur Utama Aplikasi

- **Beranda Interaktif & Animasi Modern** — *Hero Book Deck* 3D interaktif yang dapat diklik/diputar, *kinetic headline rotator*, *marquee* informasi, kilau sampul saat *hover*, *morphing nav indicator*, serta dukungan `prefers-reduced-motion`.
- **Katalog Grid, Daftar, Pencarian, & Sortir** — Cari judul/penulis/kategori, filter jenis media & ketersediaan stok, sortir (*Terbaru*, *Harga Termurah*, *Harga Termahal*, *Halaman Terbanyak*), serta alih tampilan **Grid ↔ Daftar**.
- **Mini Web E-Reader & Struk Digital Cetak PDF** — Baca cuplikan atau naskah berlisensi penuh (`A−`/`A+`, 3 mode kertas), serta struk resmi lengkap Kode Invoice, Token DRM, rincian polimorfik, dan CSS `@media print`.

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
