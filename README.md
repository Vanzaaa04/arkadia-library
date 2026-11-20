# Arkadia — Aplikasi Sewa E-Book Digital

Prototipe aplikasi web interaktif untuk **UTS Pemrograman Web Semester Ganjil 2026/2027** (Program Studi Informatika — Fakultas Teknik UMM). Dibangun menggunakan **HTML5 Semantik, CSS3, Vanilla ES6 JavaScript**, dan **PHP 8.x OOP murni tanpa framework** dengan penyimpanan **JSON & XML**.

## Cara Pakai Aplikasi (3 Langkah)

1. **Daftar atau Masuk** — Pengunjung bebas menjelajahi Beranda & Katalog terlebih dahulu. Untuk menyewa, klik **Masuk / Daftar** (atau langsung klik **Sewa** di buku pilihan): buat akun anggota baru (**Reguler**, **Premium**, atau **Dosen**), masuk dengan email terdaftar, atau gunakan **tombol 1-klik akun demo** untuk menguji perbedaan diskon secara instan.
2. **Pilih & Sewa Buku** — Di modal sewa, pilih durasi hari (`1`–`21` hari). Total biaya serta diskon akun yang sedang aktif dihitung otomatis real-time oleh server PHP melalui `fetch('index.php?api=simulate')`.
3. **Baca & Kembalikan** — Pantau pinjaman aktif milik akun Anda (atau filter semua anggota) beserta sisa hari jatuh tempo di menu **Peminjaman**, lalu klik **Kembalikan** saat selesai untuk memulihkan stok buku dan kuota pinjaman.

## Menu & Fitur Utama

- **Beranda** — Penjelasan singkat aplikasi, panduan 3 langkah, koleksi pilihan, dan perbandingan 3 jenis keanggotaan (`Anggota Reguler`, `Anggota Premium`, `Dosen & Peneliti`).
- **Katalog** — Pencarian langsung (*live search*), filter jenis buku (`Umum`, `Akademik`, `Interaktif`), filter ketersediaan stok, chip kategori, modal detail buku, dan modal transaksi sewa berbasis akun aktif.
- **Masuk / Daftar (PHP Session + `AuthService`)** — Modal autentikasi 2-tab (`Masuk` dengan email / 1-klik akun demo, serta `Daftar Baru` dengan validasi JavaScript & PHP). Saat menyewa lalu diminta masuk, aplikasi otomatis melanjutkan ke modal sewa buku tersebut setelah login/daftar berhasil.
- **Peminjaman** — Toggle **Pinjaman saya** vs **Semua anggota**, kartu pinjaman aktif dengan status jatuh tempo, tombol **Kembalikan**, serta tabel riwayat selesai.
- **Anggota** — Daftar kartu anggota dengan avatar inisial, batas kuota pinjaman, badge **Akun aktif**, tombol pindah akun instan (**Masuk akun ini**), dan form pendaftaran anggota baru.
- **Tambah Buku** — Formulir penambahan e-book baru disertai pratinjau sampul live (*live cover preview*) dengan 6 pilihan warna sampul.
- **Info Sistem** — Halaman khusus penilaian berisi checklist rubrik UTS (Front-End 30, PHP OOP 40, Struktur Berkas 30), tabel bukti polimorfisme, dan inspektor live **PHP Reflection API**.
- **Tema Terang & Gelap + Reset Data** — Toggle tema gelap/terang (`localStorage`) dan tombol reset data ke contoh awal di pojok kanan atas.

## Arsitektur PHP 8.x OOP (`src/`)

| Layer | Namespace | Komponen & Peran OOP |
| :--- | :--- | :--- |
| **Contracts** | `App\Contracts` | `RentableInterface`, `SerializableEntityInterface` |
| **Media Hierarchy** | `App\Models` | `MediaItem` *(Abstract)* $\rightarrow$ `StandardEBook`, `AcademicMonograph`, `InteractiveMediaBook` |
| **User Hierarchy** | `App\Models` | `User` *(Abstract)* $\rightarrow$ `RegularMember`, `PremiumMember`, `FacultyMember` |
| **Transaction Entity** | `App\Models` | `Transaction` *(Encapsulated entity)* |
| **Domain Services** | `App\Services` | `LibraryManager`, `AuthService`, `JsonStorageService`, `OopInspectorService` |

## Struktur Berkas

```text
├── autoload.php                # PSR-4 Autoloader (App\ -> src/)
├── index.php                   # Controller, Auth/Rental actions, AJAX endpoint (?api=simulate), & UI HTML5
├── assets/
│   ├── css/style.css           # Desain responsif Light/Dark mode
│   └── js/app.js               # Navigasi, Auth modal, filter, modal sewa via fetch(), preview & validasi
├── data/
│   ├── ebooks.json             # Penyimpanan katalog e-book (JSON)
│   ├── ebooks.xml              # Ekspor katalog otomatis (XML)
│   ├── members.json            # Penyimpanan data anggota (JSON)
│   └── transactions.json       # Penyimpanan riwayat peminjaman (JSON)
└── src/
    ├── Contracts/
    ├── Models/
    └── Services/
```

## Menjalankan Secara Lokal

Pastikan **PHP 8.1+** sudah terpasang, lalu jalankan di direktori proyek:

```bash
php -S 127.0.0.1:8088
```

Lalu buka **http://127.0.0.1:8088** di browser.
