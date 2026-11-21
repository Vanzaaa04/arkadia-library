# ARKADIA — Digital E-Book Lending & Storefront

Prototipe aplikasi web interaktif untuk **UTS Pemrograman Web Semester Ganjil 2026/2027** (Program Studi Informatika — Fakultas Teknik UMM). Dibangun menggunakan **HTML5 Semantik, CSS3, Vanilla ES6 JavaScript**, dan **PHP 8.x OOP murni tanpa framework** dengan penyimpanan **JSON & XML**.

## Desain & Pengalaman Pengguna

Antarmuka **Arkadia** dirancang dengan pendekatan *editorial commerce* modern:
- **Palet Komersial Berkarakter** — Latar porselen hangat (`#FAF9F7`), tipografi espresso (`#171412`), dan aksen merlot crimson (`#9B1C2E`), dilengkapi **Dark Mode** sinematik (`#0D0C0B`).
- **6 Gaya Sampul Penerbit Berbeda** — Setiap tema sampul memiliki komposisi grafis penerbit yang unik lengkap dengan lipatan punggung buku (*spine*).
- **Tampilan Katalog Grid, Daftar, & Sortir** — Pengunjung bisa beralih antara galeri **Grid** dan tampilan **Daftar** terperinci, menyortir menurut terbaru, harga termurah/termahal, atau jumlah halaman, serta mencari secara langsung.
- **Mini Web E-Reader** — Pratinjau bab buku langsung dari peramban (atau akses baca penuh pada pinjaman aktif) dengan pengatur ukuran huruf (`A−`/`A+`), 3 mode kertas (**Terang**, **Sepia**, **Gelap**), dan progres bab.
- **Struk Digital & Cetak PDF** — Setiap transaksi aktif maupun selesai memiliki struk resmi lengkap dengan Kode Invoice, Token Lisensi DRM, rincian perhitungan polimorfik, dan tombol cetak / simpan PDF.

## Cara Pakai Aplikasi (3 Langkah)

1. **Daftar atau Masuk** — Jelajahi Beranda & Katalog dengan bebas. Saat siap menyewa, klik **Masuk / Daftar** (atau langsung klik **Sewa** di buku pilihan): buat akun anggota baru (**Reguler**, **Premium**, atau **Dosen**), masuk dengan email terdaftar, atau coba cepat **tombol 1-klik akun demo**.
2. **Pilih Buku & Durasi** — Di modal sewa, pilih durasi hari (`1`–`21` hari). Biaya sewa, layanan, serta diskon akun aktif dihitung otomatis real-time oleh server PHP melalui `fetch('index.php?api=simulate')`.
3. **Baca, Perpanjang, & Kembalikan** — Buka naskah lewat **Mini E-Reader**, perpanjang durasi (**+3 hari**) sesuai kuota tier anggota, cetak **Struk Digital**, dan klik **Kembalikan** saat selesai untuk memulihkan stok lisensi.

## Arsitektur PHP 8.x OOP (`src/` — 16 Class & Interface)

| Layer | Namespace | Komponen & Peran OOP |
| :--- | :--- | :--- |
| **Contracts** | `App\Contracts` | `RentableInterface`, `SerializableEntityInterface` |
| **Media Hierarchy** | `App\Models` | `MediaItem` *(Abstract)* $\rightarrow$ `StandardEBook`, `AcademicMonograph`, `InteractiveMediaBook` |
| **User Hierarchy** | `App\Models` | `User` *(Abstract)* $\rightarrow$ `RegularMember`, `PremiumMember`, `FacultyMember` |
| **Transaction Entity** | `App\Models` | `Transaction` *(Encapsulated entity)* |
| **Domain Services** | `App\Services` | `LibraryManager`, `AuthService`, `JsonStorageService`, `OopInspectorService` |
| **HTTP Controller** | `App\Controllers` | `RequestController` *(Routing API JSON, aksi POST PRG, & data view)* |
| **View Helper** | `App\Support` | `ViewHelper` *(Escaping HTML, format mata uang/tanggal, render kartu & sampul)* |

## Struktur Berkas

```text
├── autoload.php                # PSR-4 Autoloader (App\ -> src/)
├── index.php                   # Entry point tipis yang memanggil RequestController & views/
├── assets/
│   ├── css/style.css           # Design system (Porcelain/Espresso/Merlot, Dark Mode, Reader, Print CSS)
│   └── js/app.js               # Navigasi, sortir, Mini E-Reader, Struk modal, validasi & fetch() quote
├── data/
│   ├── ebooks.json             # Penyimpanan katalog e-book (JSON)
│   ├── ebooks.xml              # Ekspor katalog otomatis (XML)
│   ├── members.json            # Penyimpanan data anggota (JSON)
│   └── transactions.json       # Penyimpanan riwayat peminjaman (JSON)
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

## Menjalankan Secara Lokal

Pastikan **PHP 8.1+** sudah terpasang, lalu jalankan di direktori proyek:

```bash
php -S 127.0.0.1:8088
```

Lalu buka **http://127.0.0.1:8088** di browser.
