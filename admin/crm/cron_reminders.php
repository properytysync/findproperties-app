<?php
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_notify.php";

/**
 * Sends reminders for due tasks:
 * - Email to admin.aemail
 * - In-app bell notification (crm_notifications)
 * - WhatsApp (optional, if enabled in CRM Settings)
 *
 * Uses:
 * crm_tasks(reminder_sent, due_at, status)
 */

$sql = "
SELECT 
    t.id AS task_id,
    t.title AS task_title,
    t.due_at,
    t.lead_id,
    t.assigned_admin_id,
    a.aemail,
    a.aphone,
    a.auser,
    l.name AS lead_name
FROM crm_tasks t
JOIN admin a ON a.aid = t.assigned_admin_id
LEFT JOIN crm_leads l ON l.id = t.lead_id
WHERE 
    t.status = 'open'
    AND t.reminder_sent = 0
    AND t.due_at <= NOW()
ORDER BY t.due_at ASC
LIMIT 200
";

$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {

    $taskId   = (int)$row['task_id'];
    $adminId  = (int)$row['assigned_admin_id'];
    $leadId   = (int)($row['lead_id'] ?? 0);
    $leadName = $row['lead_name'] ?: '-';

    // -------------------------
    // 1) EMAIL
    // -------------------------
    $to      = $row['aemail'];
    $subject = "CRM Reminder: " . $row['task_title'];

    $message = "Hello {$row['auser']},\n\n"
             . "You have a CRM task due:\n\n"
             . "Task: {$row['task_title']}\n"
             . "Lead: {$leadName}\n"
             . "Due: {$row['due_at']}\n\n"
             . "Please login to PropertySync CRM to follow up.\n\n"
             . "— PropertySync CRM\n";

    // NOTE: Replace with your real sender domain
    $headers  = "From: crm@yourdomain.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $emailOk = false;
    if (!empty($to)) {
        $emailOk = @mail($to, $subject, $message, $headers);
    }

    // -------------------------
    // 2) IN-APP BELL NOTIFICATION
    // -------------------------
    $link = ($leadId > 0) ? ("lead_view.php?id=" . $leadId) : ("tasks.php");
    $ntTitle = "Task due: " . $row['task_title'];
    $ntBody  = "Lead: {$leadName}\nDue: {$row['due_at']}";
    crm_notify($adminId, "task_due", $ntTitle, $ntBody, $link);

    // -------------------------
    // 3) WHATSAPP (optional)
    // -------------------------
    $phone = trim((string)($row['aphone'] ?? ''));
    if ($phone !== '' && str_starts_with($phone, '+')) {
        $waMsg = "⏰ CRM Task Due\n"
               . "Task: {$row['task_title']}\n"
               . "Lead: {$leadName}\n"
               . "Due: {$row['due_at']}\n\n"
               . "Login to CRM to follow up.";
        crm_whatsapp_send($phone, $waMsg);
    }

    // -------------------------
    // Mark reminder sent (so we don't spam)
    // -------------------------
    $upd = $conn->prepare("UPDATE crm_tasks SET reminder_sent=1, last_reminded_at=NOW() WHERE id=?");
    $upd->bind_param("i", $taskId);
    $upd->execute();
    $upd->close();
}
