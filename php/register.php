<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/helpers.php";
require_once __DIR__ . "/db_mysql.php";

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        jsonResponse(405, ["success" => false, "error" => "Method not allowed"]);
    }

    $body = getJsonBody();

    $name = trim($body["name"] ?? "");
    $email = trim($body["email"] ?? "");
    $password = trim($body["password"] ?? "");

    if ($name === "" || $email === "" || $password === "") {
        jsonResponse(400, ["success" => false, "error" => "All fields required"]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(400, ["success" => false, "error" => "Invalid email"]);
    }

    $conn = getMySqlConnection();

    if (!$conn) {
        jsonResponse(500, ["success" => false, "error" => "DB connection failed"]);
    }

    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    if (!$check) {
        jsonResponse(500, ["success" => false, "error" => $conn->error]);
    }

    $check->bind_param("s", $email);
    $check->execute();

    if ($check->get_result()->fetch_assoc()) {
        jsonResponse(409, ["success" => false, "error" => "User already exists"]);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users(name,email,password_hash) VALUES(?,?,?)");

    if (!$stmt) {
        jsonResponse(500, ["success" => false, "error" => $conn->error]);
    }

    $stmt->bind_param("sss", $name, $email, $hash);

    if (!$stmt->execute()) {
        jsonResponse(500, ["success" => false, "error" => $stmt->error]);
    }

    jsonResponse(200, ["success" => true, "message" => "Registered"]);

} catch (Throwable $e) {
    jsonResponse(500, [
        "success" => false,
        "error" => $e->getMessage()
    ]);
}

