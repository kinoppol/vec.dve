<?php
function cors(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function json_ok(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_err(string $msg, int $code = 400): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function body(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function method(string $m): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($m);
}

function require_auth(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        json_err('Unauthorized', 401);
    }
    return $_SESSION;
}

function thai_year(string $gregorianDate): string
{
    // Convert YYYY-MM-DD → DD/MM/YYYY+543
    if (!$gregorianDate) return '';
    [$y, $m, $d] = explode('-', substr($gregorianDate, 0, 10));
    return "$d/$m/" . ((int)$y + 543);
}

function relative_time(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'เมื่อกี้';
    if ($diff < 3600)  return floor($diff / 60) . ' นาทีที่แล้ว';
    if ($diff < 86400) return floor($diff / 3600) . ' ชั่วโมงที่แล้ว';
    return floor($diff / 86400) . ' วันที่แล้ว';
}
