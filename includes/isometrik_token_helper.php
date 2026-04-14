<?php

declare(strict_types=1);

/**
 * Minimal HS256 token helper for Isometrik calls.
 * Adjust claims as needed when full SDK documentation is available.
 */
function isometrik_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function isometrik_jwt_encode(array $header, array $payload, string $secret): string
{
    $headerJson = json_encode($header, JSON_UNESCAPED_SLASHES);
    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

    $segments = [
        isometrik_base64url_encode($headerJson),
        isometrik_base64url_encode($payloadJson),
    ];
    $signingInput = implode('.', $segments);
    $signature = hash_hmac('sha256', $signingInput, $secret, true);
    $segments[] = isometrik_base64url_encode($signature);

    return implode('.', $segments);
}

/**
 * Build a lightweight access token for Isometrik calls.
 *
 * Note: Claims are illustrative; align with official Isometrik token contract when available.
 */
function isometrik_generate_call_token(
    string $apiKey,
    string $apiSecret,
    string $projectId,
    string $roomName,
    string $identity,
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
        'sub' => $projectId,
        'nbf' => $now - 10,
        'exp' => $now + max(60, $ttlSeconds),
        'room' => $roomName,
        'user' => $identity,
        'scope' => [
            'publish' => true,
            'subscribe' => true,
        ],
    ];

    return isometrik_jwt_encode($header, $payload, $apiSecret);
}
