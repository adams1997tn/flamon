<?php

declare(strict_types=1);

/**
 * Minimal LiveKit JWT generator to avoid external dependencies.
 * Builds a short-lived token granting room join/publish/subscribe rights.
 */
function livekit_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function livekit_jwt_encode(array $header, array $payload, string $secret): string
{
    $headerJson = json_encode($header, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    $segments = [
        livekit_base64url_encode($headerJson),
        livekit_base64url_encode($payloadJson),
    ];
    $signingInput = implode('.', $segments);
    $signature = hash_hmac('sha256', $signingInput, $secret, true);

    $segments[] = livekit_base64url_encode($signature);

    return implode('.', $segments);
}

/**
 * Generate a LiveKit room token.
 *
 * @param string $apiKey
 * @param string $apiSecret
 * @param string $room
 * @param string $identity
 * @param bool   $canPublish
 * @param bool   $canSubscribe
 * @param int    $ttlSeconds
 *
 * @return string
 */
function livekit_generate_token(
    string $apiKey,
    string $apiSecret,
    string $room,
    string $identity,
    bool $canPublish = true,
    bool $canSubscribe = true,
    int $ttlSeconds = 3600
): string {
    $now = time();

    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT',
        'kid' => $apiKey,
    ];

    $payload = [
        'iss' => $apiKey,
        'sub' => $identity,
        'nbf' => $now - 10,
        'exp' => $now + max(60, $ttlSeconds),
        'video' => [
            'room' => $room,
            'roomJoin' => true,
            'canPublish' => $canPublish,
            'canSubscribe' => $canSubscribe,
        ],
    ];

    return livekit_jwt_encode($header, $payload, $apiSecret);
}
