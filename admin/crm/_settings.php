<?php
// /admin/crm/_settings.php
require_once __DIR__ . "/_db.php";

function crm_setting_get(string $key, $default = null) {
    global $conn;
    $stmt = $conn->prepare("SELECT value FROM crm_settings WHERE `key`=? LIMIT 1");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? $row['value'] : $default;
}

function crm_setting_set(string $key, string $value): void {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO crm_settings (`key`, `value`)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE value=VALUES(value)
    ");
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
    $stmt->close();
}
