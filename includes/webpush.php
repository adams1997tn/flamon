<?php

if (!function_exists('dizzy_webpush_base64url_encode')) {
    function dizzy_webpush_base64url_encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

if (!function_exists('dizzy_webpush_base64url_decode')) {
    function dizzy_webpush_base64url_decode(string $value): string
    {
        $value = trim($value);
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($value, true);
        return $decoded === false ? '' : $decoded;
    }
}

if (!function_exists('dizzy_webpush_normalize_base64url')) {
    function dizzy_webpush_normalize_base64url(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $decoded = dizzy_webpush_base64url_decode($value);
        if ($decoded === '') {
            $decoded = base64_decode($value, true);
            if ($decoded === false || $decoded === '') {
                return '';
            }
        }
        return dizzy_webpush_base64url_encode($decoded);
    }
}

if (!function_exists('dizzy_webpush_read_der_length')) {
    function dizzy_webpush_read_der_length(string $der, int &$offset): int
    {
        if (!isset($der[$offset])) {
            return 0;
        }
        $length = ord($der[$offset]);
        $offset++;
        if (($length & 0x80) === 0) {
            return $length;
        }
        $bytes = $length & 0x7F;
        if ($bytes < 1 || $bytes > 4) {
            return 0;
        }
        $length = 0;
        for ($i = 0; $i < $bytes; $i++) {
            if (!isset($der[$offset])) {
                return 0;
            }
            $length = ($length << 8) | ord($der[$offset]);
            $offset++;
        }
        return $length;
    }
}

if (!function_exists('dizzy_webpush_der_signature_to_jose')) {
    function dizzy_webpush_der_signature_to_jose(string $signature): string
    {
        if ($signature === '' || ord($signature[0]) !== 0x30) {
            return '';
        }

        $offset = 1;
        $sequenceLength = dizzy_webpush_read_der_length($signature, $offset);
        if ($sequenceLength < 1 || ($offset + $sequenceLength) > strlen($signature)) {
            return '';
        }
        if (!isset($signature[$offset]) || ord($signature[$offset]) !== 0x02) {
            return '';
        }
        $offset++;

        $rLength = dizzy_webpush_read_der_length($signature, $offset);
        if ($rLength < 1 || ($offset + $rLength) > strlen($signature)) {
            return '';
        }
        $r = substr($signature, $offset, $rLength);
        $offset += $rLength;

        if (!isset($signature[$offset]) || ord($signature[$offset]) !== 0x02) {
            return '';
        }
        $offset++;

        $sLength = dizzy_webpush_read_der_length($signature, $offset);
        if ($sLength < 1 || ($offset + $sLength) > strlen($signature)) {
            return '';
        }
        $s = substr($signature, $offset, $sLength);

        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        $r = str_pad(substr($r, -32), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(substr($s, -32), 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }
}

if (!function_exists('dizzy_webpush_uncompressed_public_to_pem')) {
    function dizzy_webpush_uncompressed_public_to_pem(string $publicKey): string
    {
        if (strlen($publicKey) !== 65 || $publicKey[0] !== "\x04") {
            return '';
        }
        $prefix = hex2bin('3059301306072A8648CE3D020106082A8648CE3D030107034200');
        if ($prefix === false) {
            return '';
        }
        $der = $prefix . $publicKey;
        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }
}

if (!function_exists('dizzy_webpush_hkdf_expand')) {
    function dizzy_webpush_hkdf_expand(string $prk, string $info, int $length): string
    {
        $result = '';
        $block = '';
        $counter = 1;
        while (strlen($result) < $length) {
            $block = hash_hmac('sha256', $block . $info . chr($counter), $prk, true);
            $result .= $block;
            $counter++;
        }
        return substr($result, 0, $length);
    }
}

if (!function_exists('dizzy_webpush_generate_vapid_keys')) {
    function dizzy_webpush_generate_vapid_keys(): ?array
    {
        if (!extension_loaded('openssl') || !function_exists('openssl_pkey_new') || !function_exists('openssl_pkey_export')) {
            return null;
        }

        $resource = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);

        if (!$resource) {
            return null;
        }

        $privateKeyPem = '';
        if (!openssl_pkey_export($resource, $privateKeyPem)) {
            return null;
        }

        $details = openssl_pkey_get_details($resource);
        if (!is_array($details) || !isset($details['ec']['x'], $details['ec']['y'])) {
            return null;
        }

        $publicRaw = "\x04" . $details['ec']['x'] . $details['ec']['y'];
        return [
            'public' => dizzy_webpush_base64url_encode($publicRaw),
            'private' => trim($privateKeyPem),
        ];
    }
}

if (!function_exists('dizzy_webpush_create_jwt')) {
    function dizzy_webpush_create_jwt(string $audience, string $subject, string $privateKeyPem): string
    {
        if (!function_exists('openssl_sign')) {
            return '';
        }
        $header = ['typ' => 'JWT', 'alg' => 'ES256'];
        $payload = [
            'aud' => $audience,
            'exp' => time() + 12 * 60 * 60,
            'sub' => $subject,
        ];

        $headerEncoded = dizzy_webpush_base64url_encode((string)json_encode($header));
        $payloadEncoded = dizzy_webpush_base64url_encode((string)json_encode($payload));
        $unsignedToken = $headerEncoded . '.' . $payloadEncoded;

        $derSignature = '';
        $signed = openssl_sign($unsignedToken, $derSignature, $privateKeyPem, OPENSSL_ALGO_SHA256);
        if (!$signed || $derSignature === '') {
            return '';
        }

        $joseSignature = dizzy_webpush_der_signature_to_jose($derSignature);
        if ($joseSignature === '') {
            return '';
        }

        return $unsignedToken . '.' . dizzy_webpush_base64url_encode($joseSignature);
    }
}

if (!function_exists('dizzy_webpush_encrypt_payload')) {
    function dizzy_webpush_encrypt_payload(string $payloadJson, string $receiverPublicKeyB64, string $authSecretB64): ?array
    {
        if (!function_exists('openssl_pkey_new') || !function_exists('openssl_pkey_derive') || !function_exists('openssl_encrypt')) {
            return null;
        }
        $receiverPublicRaw = dizzy_webpush_base64url_decode($receiverPublicKeyB64);
        $authSecret = dizzy_webpush_base64url_decode($authSecretB64);

        if (strlen($receiverPublicRaw) !== 65 || $receiverPublicRaw[0] !== "\x04") {
            return null;
        }
        if ($authSecret === '' || strlen($authSecret) < 12) {
            return null;
        }

        $localKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        if (!$localKey) {
            return null;
        }

        $localDetails = openssl_pkey_get_details($localKey);
        if (!is_array($localDetails) || !isset($localDetails['ec']['x'], $localDetails['ec']['y'])) {
            return null;
        }

        $localPublicRaw = "\x04" . $localDetails['ec']['x'] . $localDetails['ec']['y'];
        $receiverPublicPem = dizzy_webpush_uncompressed_public_to_pem($receiverPublicRaw);
        if ($receiverPublicPem === '') {
            return null;
        }

        $receiverPublicKey = openssl_pkey_get_public($receiverPublicPem);
        if (!$receiverPublicKey) {
            return null;
        }

        $sharedSecret = openssl_pkey_derive($receiverPublicKey, $localKey, 32);
        if (!is_string($sharedSecret) || $sharedSecret === '') {
            return null;
        }

        $salt = random_bytes(16);

        $prk = hash_hmac('sha256', $sharedSecret, $authSecret, true);
        $context = "WebPush: info\x00" . $receiverPublicRaw . $localPublicRaw;
        $ikm = dizzy_webpush_hkdf_expand($prk, $context, 32);

        $prk2 = hash_hmac('sha256', $ikm, $salt, true);
        $contentEncryptionKey = dizzy_webpush_hkdf_expand($prk2, "Content-Encoding: aes128gcm\x00", 16);
        $nonce = dizzy_webpush_hkdf_expand($prk2, "Content-Encoding: nonce\x00", 12);

        $plaintext = $payloadJson . "\x02";
        $cipherText = openssl_encrypt(
            $plaintext,
            'aes-128-gcm',
            $contentEncryptionKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if (!is_string($cipherText) || $cipherText === '' || !isset($tag)) {
            return null;
        }

        $recordSize = pack('N', 4096);
        $keyLength = chr(strlen($localPublicRaw));
        $body = $salt . $recordSize . $keyLength . $localPublicRaw . $cipherText . $tag;

        return [
            'body' => $body,
            'salt' => $salt,
            'local_public' => $localPublicRaw,
        ];
    }
}

if (!function_exists('dizzy_webpush_send_notification')) {
    function dizzy_webpush_send_notification(array $subscription, array $payload, array $settings): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'status' => 0, 'error' => 'curl_unavailable'];
        }
        $endpoint = trim((string)($subscription['endpoint'] ?? ''));
        if ($endpoint === '' || filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            return ['success' => false, 'status' => 0, 'error' => 'invalid_endpoint'];
        }

        $endpointParts = parse_url($endpoint);
        if (!is_array($endpointParts) || empty($endpointParts['scheme']) || empty($endpointParts['host'])) {
            return ['success' => false, 'status' => 0, 'error' => 'invalid_endpoint_parts'];
        }
        $audience = strtolower($endpointParts['scheme']) . '://' . strtolower($endpointParts['host']);
        if (!empty($endpointParts['port'])) {
            $audience .= ':' . (int)$endpointParts['port'];
        }

        $publicKey = trim((string)($settings['vapid_public'] ?? ''));
        $privateKey = trim((string)($settings['vapid_private'] ?? ''));
        $subject = trim((string)($settings['vapid_subject'] ?? ''));
        $ttl = isset($settings['ttl']) ? (int)$settings['ttl'] : 60;

        if ($publicKey === '' || $privateKey === '' || $subject === '') {
            return ['success' => false, 'status' => 0, 'error' => 'missing_vapid_settings'];
        }
        if ($ttl < 30) {
            $ttl = 30;
        }
        if ($ttl > 86400) {
            $ttl = 86400;
        }

        $jwt = dizzy_webpush_create_jwt($audience, $subject, $privateKey);
        if ($jwt === '') {
            return ['success' => false, 'status' => 0, 'error' => 'jwt_failed'];
        }

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($payloadJson)) {
            return ['success' => false, 'status' => 0, 'error' => 'payload_encode_failed'];
        }

        $encrypted = dizzy_webpush_encrypt_payload(
            $payloadJson,
            (string)($subscription['p256dh'] ?? ''),
            (string)($subscription['auth'] ?? '')
        );

        if (!$encrypted || empty($encrypted['body'])) {
            return ['success' => false, 'status' => 0, 'error' => 'encrypt_failed'];
        }

        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'TTL: ' . $ttl,
            'Authorization: vapid t=' . $jwt . ', k=' . $publicKey,
            'Urgency: normal',
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encrypted['body']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError !== '') {
            return ['success' => false, 'status' => $httpStatus, 'error' => $curlError];
        }

        $success = ($httpStatus >= 200 && $httpStatus < 300);
        return [
            'success' => $success,
            'status' => $httpStatus,
            'error' => $success ? '' : ('http_' . $httpStatus),
            'response' => is_string($response) ? $response : '',
        ];
    }
}
