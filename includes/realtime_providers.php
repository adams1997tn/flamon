<?php

declare(strict_types=1);

/**
 * Provider-agnostic interfaces and default Agora implementations.
 * Default factory returns Agora for backward compatibility.
 */

interface LiveStreamingProviderInterface
{
    public function generateJoinToken(string $channel, int $userId, bool $isHost): string;

    public function startRecording(string $channel, int $liveId, array $options = []): bool;
}

interface VideoCallProviderInterface
{
    public function generateCallToken(string $channel, int $userId, bool $isHost = true): string;
}

interface ChatProviderInterface
{
    public function sendMessage(string $roomId, int $userId, string $message): bool;

    public function fetchMessages(string $roomId, int $afterId = 0): array;
}

final class AgoraLiveProvider implements LiveStreamingProviderInterface
{
    public function __construct(
        private string $appId,
        private string $certificate,
        private string $customerId
    ) {
    }

    public function generateJoinToken(string $channel, int $userId, bool $isHost): string
    {
        require_once __DIR__ . '/tokenGenerator.php';

        return agora_token_builder($this->appId, $this->certificate, $userId, $isHost, $channel);
    }

    public function startRecording(string $channel, int $liveId, array $options = []): bool
    {
        $vendor = (int) ($options['vendor'] ?? 0);
        $region = (int) ($options['region'] ?? 0);
        $bucket = (string) ($options['bucket'] ?? '');
        $accessKey = (string) ($options['accessKey'] ?? '');
        $secretKey = (string) ($options['secretKey'] ?? '');
        $uid = (string) ($options['uid'] ?? '1');

        $resourceId = null;

        // Acquire
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.agora.io/v1/apps/{$this->appId}/cloud_recording/acquire");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->customerId . ':' . $this->certificate),
            'Content-Type: application/json;charset=utf-8',
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'cname' => $channel,
            'uid' => $uid,
            'clientRequest' => new stdClass(),
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string) $response);
        $resourceId = $data->resourceId ?? null;

        if (!$resourceId) {
            return false;
        }

        // Start
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.agora.io/v1/apps/{$this->appId}/cloud_recording/resourceid/{$resourceId}/mode/mix/start");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->customerId . ':' . $this->certificate),
            'Content-Type: application/json;charset=utf-8',
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'cname' => $channel,
            'uid' => $uid,
            'clientRequest' => [
                'recordingConfig' => [
                    'channelType' => 1,
                    'streamTypes' => 2,
                    'audioProfile' => 1,
                    'videoStreamType' => 1,
                    'maxIdleTime' => 120,
                    'transcodingConfig' => [
                        'width' => 480,
                        'height' => 720,
                        'fps' => 24,
                        'bitrate' => 800,
                        'maxResolutionUid' => '1',
                        'mixedVideoLayout' => 1,
                    ],
                ],
                'storageConfig' => [
                    'vendor' => $vendor,
                    'region' => $region,
                    'bucket' => $bucket,
                    'accessKey' => $accessKey,
                    'secretKey' => $secretKey,
                    'fileNamePrefix' => [
                        'upload',
                        'videos',
                        date('Y'),
                        date('m'),
                    ],
                ],
            ],
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string) $response);

        if (!empty($data->sid)) {
            DB::exec(
                "UPDATE i_live SET a_resource_id = ?, a_sid = ? WHERE live_id = ?",
                [(string) $resourceId, (string) $data->sid, (int) $liveId]
            );
        }

        return true;
    }
}

final class LiveKitLiveProvider implements LiveStreamingProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $apiSecret,
        private string $wsUrl
    ) {
    }

    public function generateJoinToken(string $channel, int $userId, bool $isHost): string
    {
        if ($this->apiKey === '' || $this->apiSecret === '' || $this->wsUrl === '') {
            throw new \RuntimeException('LiveKit configuration is incomplete.');
        }

        require_once __DIR__ . '/livekit_token_helper.php';

        return livekit_generate_token(
            $this->apiKey,
            $this->apiSecret,
            $channel,
            (string) $userId,
            $isHost,
            true
        );
    }

    public function startRecording(string $channel, int $liveId, array $options = []): bool
    {
        // TODO: Implement LiveKit recording once available in the stack.
        return false;
    }

    public function getWsUrl(): string
    {
        return $this->wsUrl;
    }
}

final class AgoraVideoCallProvider implements VideoCallProviderInterface
{
    public function __construct(
        private string $appId,
        private string $certificate
    ) {
    }

    public function generateCallToken(string $channel, int $userId, bool $isHost = true): string
    {
        require_once __DIR__ . '/tokenGenerator.php';

        return agora_token_builder($this->appId, $this->certificate, $userId, $isHost, $channel);
    }
}

final class LiveKitVideoCallProvider implements VideoCallProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $apiSecret,
        private string $wsUrl
    ) {
    }

    public function generateCallToken(string $channel, int $userId, bool $isHost = true): string
    {
        if ($this->apiKey === '' || $this->apiSecret === '' || $this->wsUrl === '') {
            throw new \RuntimeException('LiveKit configuration is incomplete.');
        }

        require_once __DIR__ . '/livekit_token_helper.php';

        return livekit_generate_token(
            $this->apiKey,
            $this->apiSecret,
            $channel,
            (string) $userId,
            $isHost,
            true
        );
    }

    public function getWsUrl(): string
    {
        return $this->wsUrl;
    }
}

final class IsometrikVideoCallProvider implements VideoCallProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $apiSecret,
        private string $projectId,
        private string $wsUrl
    ) {
    }

    public function generateCallToken(string $channel, int $userId, bool $isHost = true): string
    {
        if ($this->apiKey === '' || $this->apiSecret === '' || $this->projectId === '' || $this->wsUrl === '') {
            throw new \RuntimeException('Isometrik configuration is incomplete.');
        }

        require_once __DIR__ . '/isometrik_token_helper.php';

        return isometrik_generate_call_token(
            $this->apiKey,
            $this->apiSecret,
            $this->projectId,
            $channel,
            (string) $userId
        );
    }

    public function getWsUrl(): string
    {
        return $this->wsUrl;
    }
}

final class DefaultChatProvider implements ChatProviderInterface
{
    public function sendMessage(string $roomId, int $userId, string $message): bool
    {
        // DB-backed chat is already handled elsewhere (requests/request.php).
        return true;
    }

    public function fetchMessages(string $roomId, int $afterId = 0): array
    {
        return [];
    }
}

final class LiveProviderFactory
{
    public static function makeLiveProvider(
        string $appId,
        string $certificate,
        string $customerId,
        string $provider = 'agora',
        ?string $livekitApiKey = null,
        ?string $livekitApiSecret = null,
        ?string $livekitWsUrl = null
    ): LiveStreamingProviderInterface {
        $providerKey = strtolower(trim($provider));
        if (
            $providerKey === 'livekit' &&
            !empty($livekitApiKey) &&
            !empty($livekitApiSecret) &&
            !empty($livekitWsUrl)
        ) {
            return new LiveKitLiveProvider($livekitApiKey, $livekitApiSecret, $livekitWsUrl);
        }

        return new AgoraLiveProvider($appId, $certificate, $customerId);
    }

    public static function makeVideoCallProvider(
        string $appId,
        string $certificate,
        string $provider = 'agora',
        ?string $livekitApiKey = null,
        ?string $livekitApiSecret = null,
        ?string $livekitWsUrl = null,
        ?string $isometrikApiKey = null,
        ?string $isometrikApiSecret = null,
        ?string $isometrikProjectId = null,
        ?string $isometrikWsUrl = null
    ): VideoCallProviderInterface {
        $providerKey = strtolower(trim($provider));

        if ($providerKey === 'livekit'
            && !empty($livekitApiKey)
            && !empty($livekitApiSecret)
            && !empty($livekitWsUrl)
        ) {
            return new LiveKitVideoCallProvider(
                $livekitApiKey,
                $livekitApiSecret,
                $livekitWsUrl
            );
        }

        if ($providerKey === 'isometrik'
            && !empty($isometrikApiKey)
            && !empty($isometrikApiSecret)
            && !empty($isometrikProjectId)
            && !empty($isometrikWsUrl)
        ) {
            return new IsometrikVideoCallProvider(
                $isometrikApiKey,
                $isometrikApiSecret,
                $isometrikProjectId,
                $isometrikWsUrl
            );
        }

        // Placeholder for future LiveKit video calls; default remains Agora.
        return new AgoraVideoCallProvider($appId, $certificate);
    }

    public static function makeChatProvider(): ChatProviderInterface
    {
        return new DefaultChatProvider();
    }
}
