<?php
// /admin/crm/_helpers.php

// Prevent fatal error if /admin/_auth.php already declared h()
if (!function_exists('h')) {
    function h($text): string {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}

function flash_get($key) {
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

function flash_set($key, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = $message;
}

function format_date($date, $format = 'M j, Y g:i A') {
    if (empty($date) || $date == '0000-00-00 00:00:00') {
        return '—';
    }
    return date($format, strtotime($date));
}

function is_json($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Redirect with optional flash message (safe)
 */
function redirect(string $url, ?string $message = null): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // You should already have session_start() in your pages,
        // but this prevents notices if you forget.
        @session_start();
    }

    if ($message !== null && $message !== '') {
        flash_set('success', $message);
    }

    // If headers already sent, fall back to JS/meta refresh to avoid warning
    if (headers_sent()) {
        $safeUrl = h($url);
        echo "<script>window.location.href=" . json_encode($url) . ";</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url={$safeUrl}'></noscript>";
        exit;
    }

    header("Location: " . $url);
    exit;
}

function dt_input_to_sql(string $dtLocal): ?string {
    $dtLocal = trim($dtLocal);
    if ($dtLocal === "") return null;

    $dtLocal = str_replace("T", " ", $dtLocal);

    if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $dtLocal)) {
        $dtLocal .= ":00";
    }

    $d = DateTime::createFromFormat('Y-m-d H:i:s', $dtLocal);
    if (!$d) return null;

    return $d->format('Y-m-d H:i:s');
}

function sql_to_dt_input(?string $sqlDt): string {
    $sqlDt = trim((string)$sqlDt);

    if ($sqlDt === "" || $sqlDt === "0000-00-00 00:00:00") {
        return "";
    }

    $d = DateTime::createFromFormat('Y-m-d H:i:s', $sqlDt);

    if (!$d) {
        $d = DateTime::createFromFormat('Y-m-d H:i', $sqlDt);
        if (!$d) return "";
    }

    return $d->format('Y-m-d\TH:i');
}
