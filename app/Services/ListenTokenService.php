<?php

namespace App\Services;

class ListenTokenService
{
    public function __construct(private string $secret)
    {
    }

    public static function fromConfig(): self
    {
        return new self((string) config('services.listen.token_secret', ''));
    }

    /** Mint a compact HMAC token: base64url(json).base64url(hmac_sha256). */
    public function mint(string $linkedId, int $uid, int $ttlSeconds = 30): string
    {
        // Fail loudly on a misconfigured deployment rather than issuing a token
        // the engine will silently reject with WS close 4401.
        if ($this->secret === '') {
            throw new \RuntimeException('LISTEN_TOKEN_SECRET is not configured; cannot mint live-listen token.');
        }

        $payload = [
            'lid' => $linkedId,
            'uid' => $uid,
            'role' => 'super_admin',
            'exp' => time() + $ttlSeconds,
        ];
        $msg = $this->b64url(json_encode($payload));
        $sig = $this->b64url(hash_hmac('sha256', $msg, $this->secret, true));

        return "{$msg}.{$sig}";
    }

    /** Return claims if valid (signature + expiry + super_admin), else null. */
    public function verify(string $token): ?array
    {
        if ($this->secret === '' || substr_count($token, '.') !== 1) {
            return null;
        }
        [$msg, $sig] = explode('.', $token, 2);
        $expected = $this->b64url(hash_hmac('sha256', $msg, $this->secret, true));
        if (!hash_equals($expected, $sig)) {
            return null;
        }
        $claims = json_decode($this->b64urlDecode($msg), true);
        if (!is_array($claims) || ($claims['exp'] ?? 0) < time()) {
            return null;
        }
        if (($claims['role'] ?? null) !== 'super_admin') {
            return null;
        }

        return $claims;
    }

    private function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/'));
    }
}
