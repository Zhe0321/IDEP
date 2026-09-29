<?php
declare(strict_types=1);

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
}
