# Arkadia — Aplikasi Sewa E-Book Digital

Prototipe aplikasi web interaktif untuk **UTS Pemrograman Web Semester Ganjil 2026/2027** (Program Studi Informatika — Fakultas Teknik UMM). Dibangun menggunakan **HTML5 Semantik, CSS3, Vanilla ES6 JavaScript**, dan **PHP 8.x OOP murni tanpa framework** dengan penyimpanan **JSON & XML**.

## Cara Pakai Aplikasi (3 Langkah)

1. **Pilih Buku di Katalog** — Buka menu **Beranda** atau **Katalog**, cari judul/penulis atau filter berdasarkan kategori & jenis buku, klik sampul untuk melihat detail lengkap, atau langsung klik **Sewa**.
2. **Atur Anggota & Lama Sewa** — Di modal sewa, pilih anggota penyewa dan durasi hari (`1`–`21+` hari). Total biaya dihitung otomatis secara real-time melalui `fetch()` ke server PHP (`?api=simulate`) sesuai diskon tipe anggota dan tarif buku.
3. **Baca & Kembalikan** — Pantau daftar buku yang sedang dipinjam beserta sisa hari jatuh tempo di menu **Peminjaman**, dan klik **Kembalikan** saat selesai untuk memulihkan stok buku dan kuota anggota.

## Menu & Fitur Utama

- **Beranda** — Pengantar singkat cara pakai dalam 3 langkah, buku pilihan, dan perbandingan 3 jenis keanggotaan (`Anggota Reguler`, `Anggota Premium`, `Dosen & Peneliti`).
- **Katalog** — Pencarian langsung, filter jenis buku (`Umum`, `Akademik`, `Interaktif`), filter ketersediaan stok, chip kategori, modal detail buku, dan modal transaksi sewa.
- **Peminjaman** — Daftar pinjaman aktif lengkap dengan indikator jatuh tempo, tombol **Kembalikan**, serta tabel riwayat transaksi selesai.
- **Anggota** — Daftar kartu anggota dengan avatar inisial, kuota pinjaman, tombol pintas **Sewakan buku**, dan formulir pendaftaran anggota baru bervalidasi JavaScript & PHP.
- **Tambah Buku** — Formulir penambahan e-book baru disertai pratinjau sampul live (*live cover preview*) dengan pilihan 6 warna sampul.
- **Info Sistem** — Halaman khusus ringkasan pemenuhan rubrik UTS (Front-End 30, PHP OOP 40, Struktur Berkas 30), tabel bukti polimorfisme, dan inspektor class live menggunakan **PHP Reflection API**.
- **Tema Terang & Gelap + Reset Data** — Toggle tema gelap/terang (`localStorage`) dan tombol reset data ke kondisi contoh awal di pojok kanan atas.

## Arsitektur PHP 8.x OOP (`src/`)

| Layer | Namespace | Komponen & Peran OOP |
| :--- | :--- | :--- |
| **Contracts** | `App\Contracts` | `RentableInterface`, `SerializableEntityInterface` |
| **Media Hierarchy** | `App\Models` | `MediaItem` *(Abstract)* $\rightarrow$ `StandardEBook`, `AcademicMonograph`, `InteractiveMediaBook` |
| **User Hierarchy** | `App\Models` | `User` *(Abstract)* $\rightarrow$ `RegularMember`, `PremiumMember`, `FacultyMember` |
| **Transaction Entity** | `App\Models` | `Transaction` *(Encapsulated entity)* |
| **Domain Services** | `App\Services` | `LibraryManager`, `JsonStorageService`, `OopInspectorService` |

## Struktur Berkas

```text
├── autoload.php                # PSR-4 Autoloader (App\ -> src/)
├── index.php                   # Controller, AJAX endpoint (?api=simulate), & UI HTML5 Semantik
├── assets/
│   ├── css/style.css           # Desain responsif Light/Dark mode
│   └── js/app.js               # Navigasi, filter, modal sewa via fetch(), preview & validasi
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
