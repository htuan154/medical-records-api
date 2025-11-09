<?php

namespace App\Services\Auth;

class JwtService
{
    private string $secret;
    private int $ttl;
    private int $refreshTtl;
    private int $leeway = 60;

    public function __construct()
    {
        $this->secret     = env('JWT_SECRET', 'change_me');
        $this->ttl        = (int) env('JWT_TTL', 86400);          // 1d
        $this->refreshTtl = (int) env('JWT_REFRESH_TTL', 1209600); // 14d
    }

    /* ======================= Public API ======================= */

    public function issueTokens(array $user, array $permissions = []): array
    {
        $now = time();

        $accessPayload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttl,
            'sub' => $user['_id'] ?? null,
            'u'   => [
                'id'          => $user['_id'] ?? null,
                'username'    => $user['username'] ?? null,
                'role_names'  => $user['role_names'] ?? [],
                'account_type'=> $user['account_type'] ?? null,
                'status'      => $user['status'] ?? null,
                'perms'       => array_values(array_unique($permissions)),
            ],
        ];

        $refreshPayload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->refreshTtl,
            'typ' => 'refresh',
            'sub' => $user['_id'] ?? null,
        ];

        return [
            'access_token'        => $this->encode($accessPayload, 'HS256'),
            'expires_in'          => $this->ttl,
            'refresh_token'       => $this->encode($refreshPayload, 'HS256'),
            'refresh_expires_in'  => $this->refreshTtl,
            'token_type'          => 'Bearer',
        ];
    }

    /** Trả về stdClass payload (giống decode). Ném \RuntimeException nếu lỗi. */
    public function verify(string $jwt)
    {
        [$header, $payload] = $this->decode($jwt, 'HS256', $this->secret, $this->leeway);
        // convert array -> object cho tương thích code cũ
        return json_decode(json_encode($payload));
    }

    /* ======================= Internal: JWT HS256 ======================= */

    private function encode(array $payload, string $alg = 'HS256'): string
    {
        $header = ['typ' => 'JWT', 'alg' => $alg];

        $segments = [
            $this->b64url(json_encode($header,  JSON_UNESCAPED_SLASHES)),
            $this->b64url(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];

        $signingInput = implode('.', $segments);
        $signature = $this->sign($signingInput, $alg, $this->secret);
        $segments[] = $this->b64url($signature);

        return implode('.', $segments);
    }

    /**
     * @return array{0: array $header, 1: array $payload}
     */
    private function decode(string $jwt, string $alg, string $secret, int $leeway = 0): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Wrong number of segments');
        }

        [$headB64, $payloadB64, $sigB64] = $parts;

        $header  = json_decode($this->b64url_decode($headB64), true, 512, JSON_THROW_ON_ERROR);
        $payload = json_decode($this->b64url_decode($payloadB64), true, 512, JSON_THROW_ON_ERROR);
        $sig     = $this->b64url_decode($sigB64);

        if (($header['alg'] ?? '') !== $alg) {
            throw new \RuntimeException('Algorithm not allowed');
        }

        $signingInput = $headB64 . '.' . $payloadB64;
        $expected = $this->sign($signingInput, $alg, $secret);

        if (!hash_equals($expected, $sig)) {
            throw new \RuntimeException('Signature verification failed');
        }

        $now = time();

        if (isset($payload['nbf']) && $now + $leeway < (int) $payload['nbf']) {
            throw new \RuntimeException('Token not yet valid (nbf)');
        }
        if (isset($payload['iat']) && $now + $leeway < (int) $payload['iat']) {
            throw new \RuntimeException('Token issued in the future (iat)');
        }
        if (isset($payload['exp']) && $now - $leeway >= (int) $payload['exp']) {
            throw new \RuntimeException('Token expired (exp)');
        }

        return [$header, $payload];
    }

    private function sign(string $msg, string $alg, string $secret): string
    {
        return match ($alg) {
            'HS256' => hash_hmac('sha256', $msg, $secret, true),
            'HS384' => hash_hmac('sha384', $msg, $secret, true),
            'HS512' => hash_hmac('sha512', $msg, $secret, true),
            default => throw new \RuntimeException('Unsupported alg: '.$alg),
        };
    }

    private function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function b64url_decode(string $b64url): string
    {
        $remainder = strlen($b64url) % 4;
        if ($remainder) {
            $b64url .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($b64url, '-_', '+/'));
    }
}
