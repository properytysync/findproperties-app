<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../config/db.php";

/**
 * CORS
 */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "OPTIONS") {
    http_response_code(200);
    exit;
}

/**
 * Basic DB connection check
 */
if (!isset($con) || !$con) {
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}

/**
 * Origin only
 */
function origin(): string {
    $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    return $scheme . "://" . $host;
}

/**
 * Get the project web root from current script path.
 * Example:
 * //api/v1/site_info.php
 * => /
 */
function project_web_root(): string {
    $script = str_replace("\\", "/", $_SERVER["SCRIPT_NAME"] ?? "");

    $marker = "/api/v1/";
    $pos = strpos($script, $marker);
    if ($pos !== false) {
        return substr($script, 0, $pos);
    }

    // fallback
    return rtrim(dirname(dirname(dirname($script))), "/");
}

/**
 * JSON response helper
 */
function json_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($payload);
    exit;
}

/**
 * Require specific HTTP method
 */
function require_method(string $method): void {
    $expected = strtoupper($method);
    $current = strtoupper($_SERVER["REQUEST_METHOD"] ?? "GET");

    if ($current !== $expected) {
        json_response([
            "status" => "error",
            "message" => "Method Not Allowed. Expected {$expected}, got {$current}"
        ], 405);
    }
}

/**
 * Require admin session
 */
function require_admin(): void {
    $isAdmin =
        !empty($_SESSION["admin_id"]) ||
        !empty($_SESSION["user_id"]) ||
        !empty($_SESSION["is_admin"]) ||
        !empty($_SESSION["admin_logged_in"]);

    if (!$isAdmin) {
        json_response([
            "status" => "error",
            "message" => "Unauthorized"
        ], 401);
    }
}

/**
 * Param helpers
 */
function get_str(string $key, string $default = "", ?array $src = null): string {
    $src = $src ?? $_GET;
    if (!isset($src[$key])) return $default;
    $v = trim((string)$src[$key]);
    return $v === "" ? $default : $v;
}

function get_int(string $key, int $default = 0, ?array $src = null): int {
    $src = $src ?? $_GET;
    if (!isset($src[$key])) return $default;

    $v = $src[$key];
    if ($v === "" || $v === null) return $default;

    return (int)$v;
}

function get_float(string $key, float $default = 0.0, ?array $src = null): float {
    $src = $src ?? $_GET;
    if (!isset($src[$key])) return $default;

    $v = $src[$key];
    if ($v === "" || $v === null) return $default;

    return (float)$v;
}

function get_bool(string $key, bool $default = false, ?array $src = null): bool {
    $src = $src ?? $_GET;
    if (!isset($src[$key])) return $default;

    $v = strtolower(trim((string)$src[$key]));

    if (in_array($v, ["1", "true", "yes", "on"], true)) return true;
    if (in_array($v, ["0", "false", "no", "off"], true)) return false;

    return $default;
}

/**
 * Convert DB path to browser-loadable public URL
 */
function to_public_url(?string $path): ?string {
    if (!$path) return null;

    $p = trim((string)$path);
    if ($p === "") return null;

    // already absolute
    if (preg_match('/^https?:\/\//i', $p)) {
        return preg_replace('/^https:\/\/localhost/i', 'http://localhost', $p);
    }

    $p = str_replace("\\", "/", $p);

    // filename only => assume property image folder
    if (strpos($p, "/") === false) {
        $p = "admin/property/" . rawurlencode($p);
    }

    $p = ltrim($p, "/");

    $root = trim(project_web_root(), "/");

    if ($root !== "" && strpos($p, $root . "/") !== 0) {
        $p = $root . "/" . $p;
    }

    return origin() . "/" . $p;
}