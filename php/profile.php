<?php

declare(strict_types=1);

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_mysql.php';
require_once __DIR__ . '/redis_client.php';

$mongoApiBaseUrl = 'https://mogodb-guvi.onrender.com';

function getMongoProfileFromApi(string $baseUrl, int $userId): array
{
    $url = rtrim($baseUrl, '/') . '/profile?user_id=' . urlencode((string)$userId);

    $response = @file_get_contents($url);

    if ($response === false) {
        return ['age' => '', 'contact' => '', 'dob' => ''];
    }

    $data = json_decode($response, true);

    if (!is_array($data) || !($data['success'] ?? false)) {
        return ['age' => '', 'contact' => '', 'dob' => ''];
    }

    $profile = $data['profile'] ?? [];

    return [
        'age' => $profile['age'] ?? '',
        'contact' => $profile['contact'] ?? '',
        'dob' => $profile['dob'] ?? ''
    ];
}

function saveMongoProfileToApi(string $baseUrl, int $userId, $age, string $contact, string $dob): array
{
    $url = rtrim($baseUrl, '/') . '/profile';

    $payload = json_encode([
        'user_id' => $userId,
        'age' => ($age === '' || $age === null) ? null : (int)$age,
        'contact' => $contact,
        'dob' => $dob
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'success' => false,
            'error' => $error ?: 'Unable to call Mongo API'
        ];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (!is_array($data)) {
        return [
            'success' => false,
            'error' => 'Invalid Mongo API response'
        ];
    }

    return $data;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    $input = $_GET;

    if ($method === 'POST') {
        $json = json_decode(file_get_contents('php://input'), true);

        if (is_array($json)) {
            $input = array_merge($input, $json);
        }
    }

    $token = trim((string)($input['token'] ?? ''));

    if ($token === '') {
        jsonResponse(401, [
            'success' => false,
            'error' => 'Missing token'
        ]);
    }

    $redis = getRedisClient();
    $sessionRaw = $redis->get('session:' . $token);

    if (!$sessionRaw) {
        jsonResponse(401, [
            'success' => false,
            'error' => 'Session expired or not found'
        ]);
    }

    $session = json_decode($sessionRaw, true);

    if (isset($session['value'])) {
        $session = json_decode((string)$session['value'], true);
    }

    if (is_string($session)) {
        $session = json_decode($session, true);
    }

    if (!is_array($session) || !isset($session['user_id'])) {
        jsonResponse(401, [
            'success' => false,
            'error' => 'Invalid session data'
        ]);
    }

    $userId = (int)$session['user_id'];

    if ($method === 'GET' && $action === 'get') {
        $conn = getMySqlConnection();

        $stmt = $conn->prepare('SELECT id, name, email FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            jsonResponse(404, [
                'success' => false,
                'error' => 'User not found'
            ]);
        }

        $mongoProfile = getMongoProfileFromApi($mongoApiBaseUrl, $userId);

        jsonResponse(200, [
            'success' => true,
            'user' => array_merge($user, $mongoProfile)
        ]);
    }

    if ($method === 'POST' && $action === 'update') {
        $name = trim((string)($input['name'] ?? ''));
        $age = $input['age'] ?? null;
        $dob = trim((string)($input['dob'] ?? ''));
        $contact = trim((string)($input['contact'] ?? ''));

        if ($name === '' || mb_strlen($name) > 100) {
            jsonResponse(400, [
                'success' => false,
                'error' => 'Valid name is required.'
            ]);
        }

        if ($age !== null && $age !== '' && ((int)$age < 1 || (int)$age > 120)) {
            jsonResponse(400, [
                'success' => false,
                'error' => 'Age must be between 1 and 120.'
            ]);
        }

        if ($dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            jsonResponse(400, [
                'success' => false,
                'error' => 'DOB must be in YYYY-MM-DD format.'
            ]);
        }

        // MySQL stores registered data only: name/email.
        $conn = getMySqlConnection();

        $stmt = $conn->prepare('UPDATE users SET name = ? WHERE id = ?');
        $stmt->bind_param('si', $name, $userId);
        $stmt->execute();

        // MongoDB stores only age/contact/dob through Render API.
        $mongoResult = saveMongoProfileToApi($mongoApiBaseUrl, $userId, $age, $contact, $dob);

        if (!($mongoResult['success'] ?? false)) {
            jsonResponse(500, [
                'success' => false,
                'error' => $mongoResult['error'] ?? 'Mongo profile save failed.'
            ]);
        }

        jsonResponse(200, [
            'success' => true,
            'message' => 'Profile updated successfully.'
        ]);
    }

    jsonResponse(405, [
        'success' => false,
        'error' => 'Invalid request'
    ]);

} catch (Throwable $e) {
    jsonResponse(500, [
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
