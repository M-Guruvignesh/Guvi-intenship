<?php

declare(strict_types=1);

function getRedisClient(): object
{
    // Upstash REST URL and token. Keep these on the server only.
    $url = getenv('UPSTASH_REDIS_REST_URL');
    $token = getenv('UPSTASH_REDIS_REST_TOKEN');
    
    if (!$url || !$token) {
        throw new Exception('Redis credentials not configured in environment variables.');
    }

    return new class($url, $token) {
        private string $url;
        private string $token;

        public function __construct(string $url, string $token)
        {
            $this->url = rtrim($url, '/');
            $this->token = $token;
        }

        private function command(array $command): array
        {
            $ch = curl_init($this->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($command));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response === false || $response === '') {
                return ['error' => $error ?: 'Empty Redis response'];
            }

            $decoded = json_decode($response, true);
            return is_array($decoded) ? $decoded : ['error' => 'Invalid Redis response', 'raw' => $response];
        }

        public function setex(string $key, int $ttl, string $value): bool
        {
            $res = $this->command(['SET', $key, $value, 'EX', $ttl]);
            return isset($res['result']) && strtoupper((string)$res['result']) === 'OK';
        }

        public function get(string $key): ?string
        {
            $res = $this->command(['GET', $key]);
            if (!array_key_exists('result', $res) || $res['result'] === null) {
                return null;
            }
            return is_string($res['result']) ? $res['result'] : json_encode($res['result']);
        }

        public function del(string $key): bool
        {
            $res = $this->command(['DEL', $key]);
            return isset($res['result']);
        }
    };
}
