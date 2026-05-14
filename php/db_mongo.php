<?php

declare(strict_types=1);

/*
 * InfinityFree cannot run the MongoDB PHP extension. This file therefore talks
 * to a separate Node.js API that writes to MongoDB Atlas.
 * Configure the API URL in .env.local:
 * MONGO_API_BASE_URL=https://your-render-service.onrender.com
 */

function readLocalEnvValue(string $key, string $default = ''): string
{
    $paths = [__DIR__ . '/../.env.local', __DIR__ . '/../.env'];
    foreach ($paths as $path) {
        if (!is_readable($path)) {
            continue;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            continue;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if ($name === $key) {
                return trim($value, " \t\n\r\0\x0B\"'");
            }
        }
    }
    $env = getenv($key);
    return $env !== false && $env !== '' ? $env : $default;
}

function mongoApiBaseUrl(): string
{
    return rtrim(readLocalEnvValue('https://mogodb-guvi.onrender.com', ''), '/');
}

function mongoApiRequest(string $method, string $path, ?array $payload = null): array
{
    $baseUrl = mongoApiBaseUrl();
    if ($baseUrl === '') {
        return ['success' => false, 'error' => 'Mongo API URL is not configured.'];
    }

    $url = $baseUrl . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $response === '') {
        return ['success' => false, 'error' => $error ?: 'Empty Mongo API response'];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['success' => false, 'error' => 'Invalid Mongo API JSON', 'raw' => $response];
    }

    if ($httpCode >= 400) {
        $decoded['success'] = false;
        $decoded['http_code'] = $httpCode;
    }

    return $decoded;
}

function getMongoProfile(int $userId): array
{
    $result = mongoApiRequest('GET', '/profile?user_id=' . urlencode((string)$userId));
    if (!($result['success'] ?? false)) {
        return ['age' => '', 'contact' => '', 'dob' => ''];
    }

    $profile = $result['profile'] ?? [];
    if (!is_array($profile)) {
        $profile = [];
    }

    return [
        'age' => $profile['age'] ?? '',
        'contact' => $profile['contact'] ?? '',
        'dob' => $profile['dob'] ?? ''
    ];
}

function saveMongoProfile(int $userId, $age, string $contact, string $dob): array
{
    return mongoApiRequest('POST', '/profile', [
        'user_id' => $userId,
        'age' => $age === '' || $age === null ? null : (int)$age,
        'contact' => $contact,
        'dob' => $dob
    ]);
}
