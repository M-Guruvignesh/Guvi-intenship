<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

loadDotEnv(__DIR__ . "/../.env");

header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: no-referrer");
header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://code.jquery.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' https://code.jquery.com;");

$allowedOrigins = getenv("APP_ALLOWED_ORIGIN") ?: "*";
header("Access-Control-Allow-Origin: " . $allowedOrigins);
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

function loadDotEnv(string $filePath): void
{
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
            continue;
        }

        $name = $matches[1];
        $value = $matches[2];

        if (preg_match('/^"(.*)"$/', $value, $quoteMatch)) {
            $value = stripcslashes($quoteMatch[1]);
        } elseif (preg_match('/^\'(.*)\'$/', $value, $quoteMatch)) {
            $value = $quoteMatch[1];
        } else {
            $value = preg_replace('/\s+#.*$/', '', $value);
            $value = rtrim($value);
        }

        if (getenv($name) === false) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function envOrDefault(string $key, string $default): string
{
    $value = getenv($key);
    return $value !== false && $value !== "" ? $value : $default;
}

function jsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function getJsonBody(): array
{
    $input = file_get_contents("php://input");
    if ($input === false || $input === "") {
        return [];
    }

    $decoded = json_decode($input, true);
    if (!is_array($decoded)) {
        jsonResponse(400, ["success" => false, "error" => "Invalid JSON payload."]);
    }

    return $decoded;
}

