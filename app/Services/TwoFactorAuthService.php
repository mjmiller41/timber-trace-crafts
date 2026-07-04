<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Self-contained TOTP (RFC 6238) implementation. Deliberately dependency-free
 * so adding 2FA does not churn composer.lock or require a prod `composer
 * install`. Uses hash_hmac + a hand-rolled base32 codec.
 */
class TwoFactorAuthService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** TOTP time step in seconds (standard authenticator apps use 30). */
    private const PERIOD = 30;

    /** Number of digits in a generated code. */
    private const DIGITS = 6;

    /**
     * Generate a new base32-encoded shared secret (160 bits, the RFC 4226
     * recommended length).
     */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    /**
     * Verify a user-supplied code against the secret, allowing a small clock
     * drift window (±$window steps, default ±1 = ±30s).
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! preg_match('/^\d{'.self::DIGITS.'}$/', $code)) {
            return false;
        }

        $timestep = (int) floor(time() / self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->codeForCounter($secret, $timestep + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build the otpauth:// URI that authenticator apps consume (also the
     * payload behind a QR code).
     */
    public function otpauthUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer.':'.$account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    /**
     * Format a secret in space-separated groups of four for easier manual
     * entry into an authenticator app.
     */
    public function formatSecretForDisplay(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    /**
     * Generate a fresh set of single-use recovery codes.
     *
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->values()
            ->all();
    }

    /**
     * Compute the HOTP value for a given counter (the core of TOTP).
     */
    private function codeForCounter(string $secret, int $counter): string
    {
        $binarySecret = $this->base32Decode($secret);

        if ($binarySecret === '') {
            return '';
        }

        // 64-bit big-endian counter.
        $hash = hash_hmac('sha1', pack('J', $counter), $binarySecret, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        if ($data === '') {
            return '';
        }

        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $output .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $output;
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));

        if ($secret === '') {
            return '';
        }

        $binary = '';
        foreach (str_split($secret) as $char) {
            $binary .= str_pad(decbin(strpos(self::BASE32_ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }

        return $output;
    }
}
