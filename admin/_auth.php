<?php
// /admin/_auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_role']) && !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function is_agent(): bool {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'agent';
}

function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function require_login(): void {
    if (!is_logged_in()) {
        header("Location: index.php?msg=" . urlencode("Please login"));
        exit();
    }
}

/**
 * Only admin can access admin-only pages
 */
function require_admin(): void {
    require_login();

    if (!is_admin()) {
        header("Location: agents_list.php?msg=" . urlencode("Admin access required"));
        exit();
    }
}

/**
 * Either admin or agent can access
 */
function require_agent_or_admin(): void {
    require_login();
    // if logged-in, you are either admin or agent in your system
}

/**
 * Permission: who can edit an agent profile?
 * - Admin can edit anyone
 * - Agent can edit only themselves
 */
function can_edit_agent(int $agent_id): bool {
    if (is_admin()) return true;
    if (is_agent() && current_user_id() === (int)$agent_id) return true;
    return false;
}

/**
 * Admin-only actions on agents (create/delete/disable)
 */
function agent_can_manage_agents(): bool {
    return is_admin();
}
