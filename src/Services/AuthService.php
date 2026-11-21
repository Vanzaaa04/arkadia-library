<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use DomainException;
use InvalidArgumentException;

/**
 * Class AuthService
 * Mengelola autentikasi dan sesi akun User/Member aktif berbasis PHP Session.
 */
class AuthService
{
    private const SESSION_KEY = 'arkadia_member_id';
    private const COOKIE_KEY = 'arkadia_uid';

    private LibraryManager $manager;

    public function __construct(LibraryManager $manager)
    {
        $this->manager = $manager;
    }

    public function getCurrentUser(): ?User
    {
        $memberId = $_SESSION[self::SESSION_KEY] ?? ($_COOKIE[self::COOKIE_KEY] ?? null);
        if (!is_string($memberId) || $memberId === '') {
            return null;
        }

        $member = $this->manager->getMemberById($memberId);
        if ($member === null) {
            $this->clearSession();
            return null;
        }

        $_SESSION[self::SESSION_KEY] = $member->getId();
        return $member;
    }

    public function isLoggedIn(): bool
    {
        return $this->getCurrentUser() !== null;
    }

    public function loginByEmail(string $email): User
    {
        $cleaned = trim($email);
        if (!filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Format alamat email tidak valid.');
        }

        $member = $this->manager->getMemberByEmail($cleaned);
        if ($member === null) {
            throw new DomainException('Email belum terdaftar. Silakan buat akun di tab Daftar Baru.');
        }

        $this->persistSession($member->getId());
        return $member;
    }

    public function loginById(string $memberId): User
    {
        $member = $this->manager->getMemberById(trim($memberId));
        if ($member === null) {
            throw new DomainException('Akun anggota tidak ditemukan.');
        }

        $this->persistSession($member->getId());
        return $member;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function registerAndLogin(array $input): User
    {
        $member = $this->manager->registerMember($input);
        $this->persistSession($member->getId());
        return $member;
    }

    public function logout(): void
    {
        $this->clearSession();
    }

    private function persistSession(string $memberId): void
    {
        $_SESSION[self::SESSION_KEY] = $memberId;
        $_COOKIE[self::COOKIE_KEY] = $memberId;
        $this->setAuthCookie($memberId, time() + 86400 * 7);
    }

    private function clearSession(): void
    {
        unset($_SESSION[self::SESSION_KEY], $_COOKIE[self::COOKIE_KEY]);
        $this->setAuthCookie('', time() - 3600);
    }

    private function setAuthCookie(string $value, int $expires): void
    {
        if (headers_sent()) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        setcookie(self::COOKIE_KEY, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
