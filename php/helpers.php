<?php

header("Content-Type: application/json; charset=UTF-8");

function jsonResponse(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getJsonBody(): array {
    $raw = file_get_contents("php://input");
    return json_decode($raw, true) ?? [];
}

function createToken(): string {
    return bin2hex(random_bytes(16));
}

function getBearerToken(): ?string {
    $headers = getallheaders();

    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;

    if (!$auth) return null;

    if (preg_match('/Bearer\s+(.*)$/i', $auth, $m)) {
        return trim($m[1]);
    }

    return null;
}
function createTokenV4(): string {
    $data = random_bytes(16);

    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf(
        '%s%s-%s-%s-%s-%s%s%s',
        str_split(bin2hex($data), 4)
    );
}