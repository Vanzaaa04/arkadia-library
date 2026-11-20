<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SerializableEntityInterface;
use SimpleXMLElement;

/**
 * Class JsonStorageService
 * Mengelola persistensi objek PHP ke berkas JSON & ekspor XML di direktori data/.
 */
class JsonStorageService
{
    private string $dataDir;

    public function __construct(?string $dataDir = null)
    {
        $this->dataDir = $dataDir ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0775, true);
        }
    }

    public function getFilePath(string $filename): string
    {
        return $this->dataDir . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Membaca data dari file JSON.
     *
     * @return array<int, array<string, mixed>>
     */
    public function readJson(string $filename): array
    {
        $path = $this->getFilePath($filename);
        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Menyimpan koleksi objek SerializableEntityInterface ke berkas JSON.
     *
     * @param string $filename
     * @param array<int, SerializableEntityInterface|array<string, mixed>> $items
     */
    public function writeJson(string $filename, array $items): void
    {
        $payload = [];
        foreach ($items as $item) {
            if ($item instanceof SerializableEntityInterface) {
                $payload[] = $item->toArray();
            } elseif (is_array($item)) {
                $payload[] = $item;
            }
        }

        $path = $this->getFilePath($filename);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($path, $json !== false ? $json : '[]', LOCK_EX);
    }

    /**
     * Mengekspor katalog e-book ke format XML (mendukung catatan soal XML/JSON).
     *
     * @param array<int, SerializableEntityInterface> $items
     */
    public function exportCatalogToXml(array $items, string $filename = 'ebooks.xml'): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><arkadia_catalog></arkadia_catalog>');
        $xml->addAttribute('generated_at', date('c'));
        $xml->addAttribute('engine', 'PHP ' . PHP_VERSION . ' OOP');

        foreach ($items as $item) {
            $data = $item->toArray();
            $node = $xml->addChild('ebook');
            $node->addAttribute('id', (string) $data['id']);
            $node->addAttribute('type', (string) $data['media_type']);
            foreach ($data as $key => $val) {
                if (is_bool($val)) {
                    $node->addChild((string) $key, $val ? 'true' : 'false');
                } elseif (is_scalar($val)) {
                    $node->addChild((string) $key, htmlspecialchars((string) $val, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
                }
            }
        }

        $path = $this->getFilePath($filename);
        $xmlContent = $xml->asXML();
        if (is_string($xmlContent)) {
            file_put_contents($path, $xmlContent, LOCK_EX);
            return $xmlContent;
        }
        return '';
    }

    /**
     * Seed data awal jika berkas JSON belum tersedia.
     */
    public function ensureSeeded(bool $forceReset = false): void
    {
        $ebooksPath = $this->getFilePath('ebooks.json');
        $membersPath = $this->getFilePath('members.json');
        $transactionsPath = $this->getFilePath('transactions.json');

        if ($forceReset || !is_file($ebooksPath) || count($this->readJson('ebooks.json')) === 0) {
            $this->writeJson('ebooks.json', $this->getDefaultEbooks());
        }

        if ($forceReset || !is_file($membersPath) || count($this->readJson('members.json')) === 0) {
            $this->writeJson('members.json', $this->getDefaultMembers());
        }

        if ($forceReset || !is_file($transactionsPath) || count($this->readJson('transactions.json')) === 0) {
            $this->writeJson('transactions.json', $this->getDefaultTransactions());
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultEbooks(): array
    {
        return [
            [
                'id' => 'EBK-101',
                'media_type' => 'academic',
                'title' => 'Designing Data-Intensive Distributed Systems',
                'author' => 'Dr. Martin Kleppmann & Nadia Eghbal',
                'category' => 'Software Architecture',
                'isbn' => '978-1-4493-7332-0',
                'publish_year' => 2025,
                'pages' => 612,
                'daily_rate' => 6500.0,
                'stock' => 4,
                'total_stock' => 5,
                'synopsis' => 'Monograf komprehensif mengenai arsitektur penyimpanan data modern, konsistensi terdistribusi, replikasi log konsensus Raft, dan pemrosesan aliran real-time.',
                'cover_theme' => 'terracotta',
                'file_format' => 'PDF/A + BibTeX Citation',
                'file_size_mb' => 28.4,
                'doi_number' => '10.1145/3580305.3599812',
                'institution_name' => 'O’Reilly Research & ACM Digital Library',
            ],
            [
                'id' => 'EBK-102',
                'media_type' => 'standard',
                'title' => 'The Pragmatic PHP 8.3 & Clean Domain Craft',
                'author' => 'Brent Roose & Freek Van der Herten',
                'category' => 'Web Engineering',
                'isbn' => '978-0-1359-5705-9',
                'publish_year' => 2024,
                'pages' => 384,
                'daily_rate' => 4500.0,
                'stock' => 3,
                'total_stock' => 4,
                'synopsis' => 'Panduan mendalam membangun sistem backend modern berbasis PHP 8 OOP murni, strict typing, desain berbasis domain (DDD), serta pola polimorfisme tanpa ketergantungan framework.',
                'cover_theme' => 'obsidian',
                'file_format' => 'EPUB3 + High-Res PDF',
                'file_size_mb' => 14.2,
                'drm_protection' => 'Readium LCP Watermark',
            ],
            [
                'id' => 'EBK-103',
                'media_type' => 'interactive',
                'title' => 'Refactoring UI & Typography Systems in Practice',
                'author' => 'Adam Wathan, Steve Schoger & Matthew Butterick',
                'category' => 'Interface Design',
                'isbn' => '978-1-9848-2218-5',
                'publish_year' => 2025,
                'pages' => 296,
                'daily_rate' => 7500.0,
                'stock' => 2,
                'total_stock' => 3,
                'synopsis' => 'Edisi interaktif yang membedah hierarki tipografi editorial, sistem grid Swiss, mikro-interaksi CSS modern, dilengkapi narasi audio kurator dan sandbox komponen live.',
                'cover_theme' => 'forest',
                'file_format' => 'EPUB3 + Lossless Audio + Sandbox',
                'file_size_mb' => 182.0,
                'audio_duration_minutes' => 275,
                'includes_code_sandbox' => true,
            ],
            [
                'id' => 'EBK-104',
                'media_type' => 'academic',
                'title' => 'Foundations of Statistical Machine Learning & Neural Nets',
                'author' => 'Prof. Yoshua Bengio & Shakir Mohamed',
                'category' => 'Artificial Intelligence',
                'isbn' => '978-0-2620-3561-3',
                'publish_year' => 2026,
                'pages' => 740,
                'daily_rate' => 8000.0,
                'stock' => 5,
                'total_stock' => 5,
                'synopsis' => 'Referensi akademik tingkat lanjut mengenai optimasi gradien stokastik, arsitektur Transformer, teori informasi variansional, dan pembuktian matematis generalisasi model.',
                'cover_theme' => 'indigo',
                'file_format' => 'PDF/A + LaTeX Source + BibTeX',
                'file_size_mb' => 41.6,
                'doi_number' => '10.7551/mitpress/11474.001.0001',
                'institution_name' => 'MIT Press Monograph Series',
            ],
            [
                'id' => 'EBK-105',
                'media_type' => 'standard',
                'title' => 'Grid Systems in Graphic & Digital Editorial Design',
                'author' => 'Josef Müller-Brockmann & Ellen Lupton',
                'category' => 'Interface Design',
                'isbn' => '978-3-7212-0145-1',
                'publish_year' => 2023,
                'pages' => 228,
                'daily_rate' => 4000.0,
                'stock' => 4,
                'total_stock' => 4,
                'synopsis' => 'Karya klasik tipografi Swiss yang diadaptasi untuk tata letak web responsif masa kini: rasio proporsi matematis, ritme vertikal, dan komposisi asimetris.',
                'cover_theme' => 'amber',
                'file_format' => 'Fixed-Layout EPUB + PDF',
                'file_size_mb' => 36.8,
                'drm_protection' => 'Adobe Readium LCP',
            ],
            [
                'id' => 'EBK-106',
                'media_type' => 'interactive',
                'title' => 'Full-Stack Web Security & Cryptography Engineering',
                'author' => 'Bruce Schneier & Filippo Valsorda',
                'category' => 'Cybersecurity',
                'isbn' => '978-1-5932-7950-9',
                'publish_year' => 2026,
                'pages' => 498,
                'daily_rate' => 7000.0,
                'stock' => 1,
                'total_stock' => 2,
                'synopsis' => 'Eksplorasi protokol TLS 1.3, Zero-Knowledge Proofs, autentikasi WebAuthn, dan pencegahan eksploitasi aplikasi web disertai laboratorium simulasi kriptografi interaktif.',
                'cover_theme' => 'crimson',
                'file_format' => 'EPUB3 + Audio Commentary + Lab',
                'file_size_mb' => 154.5,
                'audio_duration_minutes' => 340,
                'includes_code_sandbox' => true,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultMembers(): array
    {
        return [
            [
                'id' => 'MBR-01',
                'member_code' => 'ARK-2026-001',
                'name' => 'Aurelia Prameswari, M.Kom.',
                'email' => 'aurelia.prameswari@umm.ac.id',
                'institution' => 'Fakultas Teknik Informatika — UMM',
                'joined_date' => '2026-02-14',
                'active_loans' => 1,
                'tier' => 'faculty',
            ],
            [
                'id' => 'MBR-02',
                'member_code' => 'ARK-2026-002',
                'name' => 'Raka Aditya Mahendra',
                'email' => 'raka.mahendra@student.umm.ac.id',
                'institution' => 'Informatika Kelas C — UMM',
                'joined_date' => '2026-03-01',
                'active_loans' => 1,
                'tier' => 'premium',
            ],
            [
                'id' => 'MBR-03',
                'member_code' => 'ARK-2026-003',
                'name' => 'Nadine Kusuma Wardhani',
                'email' => 'nadine.wardhani@webmail.umm.ac.id',
                'institution' => 'Informatika Kelas D — UMM',
                'joined_date' => '2026-04-10',
                'active_loans' => 1,
                'tier' => 'regular',
            ],
            [
                'id' => 'MBR-04',
                'member_code' => 'ARK-2026-004',
                'name' => 'Dimas Setiawan Prakoso',
                'email' => 'dimas.prakoso@alumni.umm.ac.id',
                'institution' => 'Software Engineer — Malang Tech Hub',
                'joined_date' => '2026-05-19',
                'active_loans' => 0,
                'tier' => 'premium',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultTransactions(): array
    {
        return [
            [
                'id' => 'TRX-9001',
                'transaction_code' => 'INV/2026/11/0901',
                'member_id' => 'MBR-01',
                'member_name' => 'Aurelia Prameswari, M.Kom.',
                'member_tier' => 'Academic Scholar',
                'ebook_id' => 'EBK-101',
                'ebook_title' => 'Designing Data-Intensive Distributed Systems',
                'ebook_type' => 'Academic Monograph',
                'duration_days' => 10,
                'base_cost' => 67500.0,
                'service_fee' => 3000.0,
                'discount_amount' => 20250.0,
                'total_cost' => 50250.0,
                'borrow_date' => '2026-11-15',
                'due_date' => '2026-11-25',
                'return_date' => null,
                'status' => 'active',
                'access_token' => 'LCP-9A4F82B1',
            ],
            [
                'id' => 'TRX-9002',
                'transaction_code' => 'INV/2026/11/0902',
                'member_id' => 'MBR-02',
                'member_name' => 'Raka Aditya Mahendra',
                'member_tier' => 'Arkadia Fellow (VIP)',
                'ebook_id' => 'EBK-103',
                'ebook_title' => 'Refactoring UI & Typography Systems in Practice',
                'ebook_type' => 'Interactive Suite & Audio',
                'duration_days' => 7,
                'base_cost' => 59500.0,
                'service_fee' => 0.0,
                'discount_amount' => 11900.0,
                'total_cost' => 47600.0,
                'borrow_date' => '2026-11-18',
                'due_date' => '2026-11-25',
                'return_date' => null,
                'status' => 'active',
                'access_token' => 'LCP-7C1D55E9',
            ],
            [
                'id' => 'TRX-9003',
                'transaction_code' => 'INV/2026/11/0903',
                'member_id' => 'MBR-03',
                'member_name' => 'Nadine Kusuma Wardhani',
                'member_tier' => 'Regular Reader',
                'ebook_id' => 'EBK-106',
                'ebook_title' => 'Full-Stack Web Security & Cryptography Engineering',
                'ebook_type' => 'Interactive Suite & Audio',
                'duration_days' => 5,
                'base_cost' => 40000.0,
                'service_fee' => 4500.0,
                'discount_amount' => 0.0,
                'total_cost' => 44500.0,
                'borrow_date' => '2026-11-19',
                'due_date' => '2026-11-24',
                'return_date' => null,
                'status' => 'active',
                'access_token' => 'LCP-3E8B10A4',
            ],
            [
                'id' => 'TRX-8998',
                'transaction_code' => 'INV/2026/11/0898',
                'member_id' => 'MBR-02',
                'member_name' => 'Raka Aditya Mahendra',
                'member_tier' => 'Arkadia Fellow (VIP)',
                'ebook_id' => 'EBK-102',
                'ebook_title' => 'The Pragmatic PHP 8.3 & Clean Domain Craft',
                'ebook_type' => 'Standard Trade E-Book',
                'duration_days' => 6,
                'base_cost' => 27000.0,
                'service_fee' => 0.0,
                'discount_amount' => 5400.0,
                'total_cost' => 21600.0,
                'borrow_date' => '2026-11-05',
                'due_date' => '2026-11-11',
                'return_date' => '2026-11-10',
                'status' => 'returned',
                'access_token' => 'LCP-6F20D1C8',
            ],
        ];
    }
}
