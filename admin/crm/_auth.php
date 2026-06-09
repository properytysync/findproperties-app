<?php
// /admin/crm/_auth.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ✅ Load the main auth helpers so CRM pages can use:
// is_logged_in(), is_admin(), is_agent(), current_user_id(), require_login(), can_edit_agent(), etc.
require_once __DIR__ . "/../_auth.php";

/**
 * CRM Access Rules:
 * - Admin: full access
 * - Agent: can access CRM (dashboard/leads/tasks), but not admin-only pages (reports, etc.)
 *
 * This file authenticates and exposes:
 *   $CRM_ADMIN_ID, $CRM_ADMIN_USER, $CRM_ROLE, $CRM_USER_ID
 */

// Ensure the user is logged in (uses main auth)
require_login();

// Determine role/user_id using the unified system first
$role = $_SESSION['user_role'] ?? '';
$uid  = (int)($_SESSION['user_id'] ?? 0);

// Backward compat (old system)
if ($role === '' && !empty($_SESSION['auser'])) {
    $role = 'admin';
    $uid  = (int)($_SESSION['admin_id'] ?? 0);
}

if ($role === '' && !empty($_SESSION['agent_id'])) {
    $role = 'agent';
    $uid  = (int)$_SESSION['agent_id'];
}

// Final validation
if ($role !== 'admin' && $role !== 'agent') {
    header("Location: ../index.php?msg=" . urlencode("Please login"));
    exit;
}

$CRM_ROLE    = $role;
$CRM_USER_ID = $uid;

// Variables used by CRM layout + activities/notes
if ($CRM_ROLE === 'admin') {
    $CRM_ADMIN_ID   = $uid ?: (int)($_SESSION['admin_id'] ?? 0);
    $CRM_ADMIN_USER = (string)($_SESSION['auser'] ?? 'Admin');
} else {
    // Agent inside CRM: we still want an actor id for logs,
    // but we will NOT treat agent as admin.
    $CRM_ADMIN_ID   = 0;
    $CRM_ADMIN_USER = (string)($_SESSION['agent_name'] ?? 'Agent');
}

/**
 * Helper: enforce admin-only access inside CRM pages
 */
function crm_require_admin(): void {
    if (!function_exists('is_admin')) {
        // extreme fallback (should never happen since we require ../_auth.php above)
        $role = $_SESSION['user_role'] ?? '';
        if ($role !== 'admin') {
            header("Location: index.php?msg=" . urlencode("Admins only"));
            exit;
        }
        return;
    }

    if (!is_admin()) {
        header("Location: index.php?msg=" . urlencode("Admins only"));
        exit;
    }
}

/**
 * ✅ CRM actor id for activities/notes/tasks attribution
 * Admin uses their admin id, Agent uses their agent id
 */
function crm_actor_id(): int {
    if (function_exists('current_user_id')) return current_user_id();
    return (int)($_SESSION['user_id'] ?? 0);
}
