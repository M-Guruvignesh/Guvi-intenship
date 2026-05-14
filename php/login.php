<?php

require_once __DIR__ . "/helpers.php";
require_once __DIR__ . "/db_mysql.php";
require_once __DIR__ . "/redis_client.php";

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        jsonResponse(405, ["success" => false, "error" => "Method not allowed"]);
    }

    $body = getJsonBody();

    $email = trim($body["email"] ?? "");
    $password = $body["password"] ?? "";

    if ($email === "" || $password === "") {
        jsonResponse(400, ["success" => false, "error" => "Missing fields"]);
    }

    $conn = getMySqlConnection();

    if (!$conn) {
        jsonResponse(500, ["success" => false, "error" => "DB connection failed"]);
    }

    $stmt = $conn->prepare("SELECT id, name, email, password_hash FROM users WHERE email=?");

    if (!$stmt) {
        jsonResponse(500, ["success" => false, "error" => $conn->error]);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user["password_hash"])) {
        jsonResponse(401, ["success" => false, "error" => "Invalid credentials"]);
    }

    // ---------------- TOKEN ----------------
    $token = createTokenV4();   // ✅ FIXED

    $redis = getRedisClient();

    if (!$redis) {
        jsonResponse(500, ["success" => false, "error" => "Redis not available"]);
    }

    $sessionData = json_encode([
        "user_id" => (int)$user["id"],
        "email" => $user["email"],
        "name" => $user["name"]
    ]);

    $redis->setex("session:" . $token, 86400, $sessionData);

    // ---------------- RESPONSE (IMPORTANT) ----------------
   jsonResponse(200, [
    "success" => true,
    "sessionToken" => $token,
    "email" => $user["email"],
    "name" => $user["name"]
]);
} catch (Throwable $e) {
    jsonResponse(500, [
        "success" => false,
        "error" => $e->getMessage()
    ]);
}