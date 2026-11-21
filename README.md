# ARKADIA — Digital E-Book Lending & Storefront

## Identitas Penyusun

| Nama Lengkap | NIM | Peran |
| :--- | :--- | :--- |
| **Ariel Ardiansyah** | `202410370110434` | **Ketua** |
| Lalu Irgi Nabil Farhan | `202410370110404` | Anggota |
| Faris Ramadhan Riyanto | `202410370110402` | Anggota |

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

## Alur Akses Menu Utama

- **Navigasi Atas:** **Beranda** (hero 3D & kategori), **Katalog** (cari, sortir, Grid/Daftar, detail, sewa), **Peminjaman** (*Pinjaman saya / Semua anggota*, baca penuh, perpanjang, struk PDF, kembalikan), **Anggota** (kartu semua member & ganti akun instan), **Tambah Buku** (input katalog baru + pratinjau sampul), dan **Info Sistem** (rubrik 100 poin & inspeksi OOP *Reflection API*).
- **Kontrol Pojok Kanan Header:** tombol **Masuk** / **Daftar** (atau kapsul akun aktif + **Keluar**), sakelar tema **Terang / Gelap**, dan tombol **Reset** *(panah melingkar)* untuk mengembalikan data demo ke kondisi awal.

## Alur Penggunaan Web (Berkesinambungan Antar Akun)

Skenario berikut dirancang menyambung dari satu akun ke akun berikutnya sehingga terlihat bagaimana perubahan stok buku, kuota pinjam, status sewa, dan katalog saling terhubung:

1. **Tahap 1 — Mode Pengunjung / Tamu *(Sebelum Masuk)***
   - Jelajahi **Beranda** (*3D Hero Book Deck* & strip kategori) lalu buka **Katalog** (uji sortir *Harga Termurah* dan tampilan *Grid &harr; Daftar*).
   - Perhatikan *Full-Stack Web Security* (`EBK-106`) hanya tersisa **`Sisa 1`** stok. Buka detailnya lalu klik **Baca cuplikan** untuk mencoba **Mini E-Reader** (*Terang / Sepia / Gelap*, ukuran huruf `A−`/`A+`).
   - Saat klik **Sewa**, sistem otomatis membuka jendela **Masuk / Daftar**.

2. **Tahap 2 — Akun Reguler *(`Nadine Kusuma Wardhani` / `MBR-03`)* &rarr; Uji Batas Kuota & Durasi**
   - Klik **Masuk** &rarr; pilih tombol 1-klik **Anggota Reguler** (`0%` diskon, maks. `7` hari, kuota awal **`1/2`**).
   - Di **Peminjaman**, klik **+3 hari** pada pinjaman aktifnya (*Full-Stack Web Security*, 5 hari) &rarr; sistem memunculkan pesan validasi OOP bahwa total `8` hari melebihi batas **7 hari** Anggota Reguler.
   - Kembali ke **Katalog**, sewa *The Pragmatic PHP 8.3* selama `3` hari. Kini kuota Nadine menjadi penuh **`2/2`** *(percobaan menyewa buku lain akan ditahan oleh batas kuota Reguler)*.

3. **Tahap 3 — Pindah ke Akun Premium *(`Raka Aditya Mahendra` / `MBR-02`)* &rarr; Habiskan Stok, Perpanjang, Struk, & Kembalikan**
   - Pindah akun lewat menu **Anggota** &rarr; **Masuk akun ini** pada **Raka Aditya Mahendra** (`20%` diskon + **gratis biaya layanan `Rp 0`**, maks. `14` hari).
   - Sewa salinan terakhir *Full-Stack Web Security* (`EBK-106`) &rarr; di Katalog buku ini langsung berubah menjadi **`Habis`** *(tidak bisa disewa akun lain selama masih dipinjam Raka)*.
   - Di **Peminjaman** *(pantau tab **Semua anggota** untuk melihat pinjaman Nadine & Raka sekaligus, lalu pindah ke **Pinjaman saya**)*:
     - Klik **Baca** (*Lisensi aktif*) untuk membaca tanpa batas pratinjau.
     - Klik **+3 hari** pada *Refactoring UI* (`7` &rarr; `10` hari, total biaya dihitung ulang polimorfik).
     - Klik **Struk** lalu uji **Cetak / Simpan PDF**.
     - Klik **Kembalikan** pada *Full-Stack Web Security* &rarr; transaksi masuk ke **Riwayat selesai**, dan stok `EBK-106` **pulih kembali (`Sisa 1`)** untuk akun berikutnya.

4. **Tahap 4 — Pindah ke Akun Dosen & Peneliti *(`Aurelia Prameswari, M.Kom.` / `MBR-01`)* &rarr; Sewa 21 Hari & Tambah Buku**
   - Pindah ke akun **Dosen & Peneliti** (`30%` subsidi riset, maks. `21` hari, kuota `8` buku).
   - Langsung sewa *Full-Stack Web Security* yang baru saja dikembalikan Raka menggunakan durasi maksimal **`21 hari`** dengan **potongan 30%**.
   - Buka menu **Tambah Buku** untuk menambahkan 1 e-book baru ke katalog *(tersimpan otomatis ke `data/ebooks.json` & `data/ebooks.xml`)*.

5. **Tahap 5 — Akun Baru *(`Daftar`)* / `Dimas Setiawan Prakoso` *(`MBR-04`)*, Info Sistem, & Reset**
   - Klik **Daftar** untuk membuat anggota baru *(atau masuk sebagai **Dimas** `MBR-04` yang kuotanya masih kosong **`0/5`**)* &rarr; e-book baru yang ditambahkan Aurelia di Tahap 4 langsung tersedia di **Katalog** dan siap disewa.
   - Buka menu **Info Sistem** untuk mengecek ringkasan rubrik 100 poin serta struktur **16 class & interface OOP** via **Reflection API**.
   - Klik ikon **Reset** *(kanan atas header)* setelah selesai demo untuk mengembalikan seluruh data ke kondisi awal (`6` buku, `4` anggota, `4` transaksi).

## Fitur Utama Aplikasi

- **Beranda Interaktif & Animasi Modern** — *Hero Book Deck* 3D interaktif yang dapat diklik/diputar, *kinetic headline rotator*, *marquee* informasi, kilau sampul saat *hover*, *morphing nav indicator*, serta dukungan `prefers-reduced-motion`.
- **Katalog Grid, Daftar, Pencarian, & Sortir** — Cari judul/penulis/kategori, filter jenis media & ketersediaan stok, sortir (*Terbaru*, *Harga Termurah*, *Harga Termahal*, *Halaman Terbanyak*), serta alih tampilan **Grid &harr; Daftar**.
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

1. Clone repositori dan masuk ke foldernya:
   ```bash
   git clone https://github.com/Vanzaaa04/arkadia-library.git
   cd arkadia-library
   ```
2. Jalankan server pengembangan PHP:
   ```bash
   php -S 127.0.0.1:8088
   ```
3. Buka **`http://127.0.0.1:8088`** di browser.

### Opsi 2 — Laragon / XAMPP

1. Clone atau letakkan folder proyek ke `C:\laragon\www\arkadia-library` *(Laragon)* atau `C:\xampp\htdocs\arkadia-library` *(XAMPP)*.
2. Pastikan modul **Apache** sudah aktif.
3. Buka **`http://localhost/arkadia-library`** di browser.
