# Arkadia — Digital Monograph & E-Book Lending System

Prototipe aplikasi web interaktif untuk **UTS Pemrograman Web Semester Ganjil 2026/2027** (Program Studi Informatika — Fakultas Teknik UMM) yang mengintegrasikan antarmuka Front-End modern (**HTML5 Semantik, CSS3, Vanilla ES6 JS**) dengan backend engine **PHP 8.x OOP murni tanpa framework**.

## Fitur Utama

1. **Katalog Media Digital Interaktif**
   - Tampilan kartu katalog modern dengan *custom vector covers*, *live search*, serta filter berdasarkan kategori, subclass media OOP, dan ketersediaan stok lisensi.
   - Dialog detail spesifikasi e-book (`<dialog>`) & formulir penambahan koleksi baru (`+ Tambah E-Book`).
2. **Dual Theme (Light & Dark Mode)**
   - Desain modern terinspirasi SaaS 2026 dengan tombol toggle tema gelap/terang (`localStorage` persistent).
3. **Manajemen Member (User)**
   - Direktori anggota lengkap dengan kartu profil, *quota meter*, dan formulir registrasi dengan validasi client-side JavaScript & server-side PHP.
4. **Simulasi & Transaksi Penyewaan E-Book Polimorfik**
   - Kalkulator sewa real-time (*quick duration preset chips*, *range slider*, dan *live invoice summary*) yang menghitung tarif pokok, biaya layanan media, diskon tier member, serta batas peminjaman sesuai subclass runtime.
   - Ledger transaksi aktif & fitur pengembalian e-book yang memulihkan stok lisensi dan kuota member secara otomatis.
5. **Inspektor PHP 8.x OOP (Reflection API)**
   - Panel khusus yang membaca seluruh namespace, properti `private`/`protected`, pewarisan, dan metode polimorfik di folder `src/` secara langsung saat runtime.
6. **Persistensi Ganda JSON & XML**
   - Seluruh state objek diserialisasikan ke berkas `data/ebooks.json`, `data/members.json`, `data/transactions.json`, serta diekspor otomatis ke `data/ebooks.xml`.

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
├── index.php                   # Controller, AJAX endpoints, & Semantik HTML5 UI
├── assets/
│   ├── css/style.css           # Design system (Light & Dark Mode, Responsive Grid)
│   └── js/app.js               # Theme switcher, filter katalog, kalkulator & validasi
├── data/
│   ├── ebooks.json             # Penyimpanan katalog e-book (JSON)
│   ├── ebooks.xml              # Ekspor katalog otomatis (XML)
│   ├── members.json            # Penyimpanan data member (JSON)
│   └── transactions.json       # Penyimpanan riwayat transaksi (JSON)
└── src/
    ├── Contracts/
    ├── Models/
    └── Services/
```

## Cara Menjalankan Secara Lokal

Pastikan **PHP 8.1+** sudah terpasang, lalu jalankan perintah berikut di direktori root proyek:

```bash
php -S 127.0.0.1:8088
```

Buka browser dan kunjungi: **http://127.0.0.1:8088**
