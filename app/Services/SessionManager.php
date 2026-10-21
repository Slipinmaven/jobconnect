<?php
namespace App\Services;

use App\Core\BaseService;

class SessionManager extends BaseService
{
    private int $defaultExpiry = 604800; // 7 days

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => true,
                'cookie_samesite' => 'Strict',
            ]);
        }
    }

    // ------------------- PHP SESSION -------------------

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $_SESSION = [];
    }

    // ------------------- FLASH MESSAGES -------------------

    public function setFlash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, $default = null)
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function getAllFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    // ------------------- SESSION SECURITY -------------------

    public function regenerate(bool $deleteOld = true): void
    {
        session_regenerate_id($deleteOld);
    }

    public function destroy(): void
    {
        $this->clear();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    // ------------------- COOKIE MANAGEMENT -------------------

    public function setCookie(
        string $name,
        string $value,
        ?int $expiry = null,
        string $path = '/',
        string $domain = '',
        bool $secure = true,
        bool $httponly = true,
        string $samesite = 'Strict'
    ): void {
        setcookie(
            $name,
            $value,
            [
                'expires' => time() + ($expiry ?? $this->defaultExpiry),
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite
            ]
        );
        $_COOKIE[$name] = $value; // sync immediately
    }

    public function getCookie(string $name, $default = null)
    {
        return $_COOKIE[$name] ?? $default;
    }

    public function deleteCookie(string $name, string $path = '/', string $domain = ''): void
    {
        setcookie($name, '', time() - 3600, $path, $domain);
        unset($_COOKIE[$name]);
    }

    // ------------------- LOGIN STATUS -------------------

    public function isLoggedIn(): bool
    {
        return $this->has('user_id');
    }

    public function getUserId(): ?int
    {
        return $this->get('user_id');
    }

    public function getUserRole(): ?string
    {
        return $this->get('user_role');
    }

    public function getUserEmail(): ?string
    {
        return $this->get('user_email');
    }

    // ------------------- DEVICE / SESSION HELPERS -------------------

    /**
     * Save device fingerprint and IP for security
     */
    public function setDeviceInfo(string $fingerprint, ?string $ip = null): void
    {
        $this->set('device_fingerprint', $fingerprint);
        $this->set('ip_address', $ip ?? ($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    public function getDeviceFingerprint(): ?string
    {
        return $this->get('device_fingerprint');
    }

    public function getIpAddress(): ?string
    {
        return $this->get('ip_address');
    }
}