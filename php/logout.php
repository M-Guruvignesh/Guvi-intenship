<?php

declare(strict_types=1);

require_once __DIR__ . "/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse(405, ["success" => false, "error" => "Method not allowed."]);
}

$token = getBearerToken();

try {
    $redis = getRedisClient();
    $redis->del(["session:" . $token]);
} catch (Throwable $exception) {
    jsonResponse(500, ["success" => false, "error" => "Failed to clear session."]);
}

jsonResponse(200, ["success" => true, "message" => "Logged out successfully."]);

