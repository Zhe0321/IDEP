<?php
declare(strict_types=1);

/**
 * CSRF token stored in the session. Pages print it in a <meta name="csrf-token">
 * tag and the dashboard scripts send it back in an X-CSRF-Token header.
 */
function idepCsrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Stops the request with a 403 unless a POST carries the session's CSRF token. */
function idepRequireCsrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    $sent = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? ''));
    $expected = (string) ($_SESSION['csrf_token'] ?? '');
    if ($expected === '' || $sent === '' || !hash_equals($expected, $sent)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Your session has expired. Please reload the page and try again.']);
        exit;
    }
}

function requireAdminJson(): void
{
    header('Content-Type: application/json; charset=utf-8');

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (($_SESSION['idep_admin'] ?? false) !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please sign in as a manager.']);
        exit;
    }

    idepRequireCsrf();
}

function requireAdministratorJson(): void
{
    requireAdminJson();

    if (($_SESSION['idep_role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Administrator access is required.']);
        exit;
    }
}