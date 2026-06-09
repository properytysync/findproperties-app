<?php
// /admin/crm/_notify.php
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_settings.php";

function crm_notify(int $admin_id, string $type, string $title, string $body = '', string $link = ''): void {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO crm_notifications (admin_id, type, title, body, link, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, 0, NOW())
    ");
    $stmt->bind_param("issss", $admin_id, $type, $title, $body, $link);
    $stmt->execute();
    $stmt->close();
}

function crm_whatsapp_send(string $to_phone_e164, string $message): bool {
    // WhatsApp Business Cloud API (Meta)
    $enabled = (string)crm_setting_get('wa_enabled', '0') === '1';
    if (!$enabled) return false;

    $phoneId = trim((string)crm_setting_get('wa_phone_id', ''));
    $token   = trim((string)crm_setting_get('wa_token', ''));

    if ($phoneId === '' || $token === '') return false;

    // WhatsApp requires phone in international format without "+" for "to"
    $to = ltrim($to_phone_e164, '+');

    $url = "https://graph.facebook.com/v19.0/{$phoneId}/messages";

    $payload = [
        "messaging_product" => "whatsapp",
        "to" => $to,
        "type" => "text",
        "text" => ["body" => $message]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$token}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return false;
    return ($code >= 200 && $code < 300);
}
