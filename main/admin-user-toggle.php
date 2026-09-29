<?php
declare(strict_types=1);

require __DIR__ . '/includes/admin-endpoint.php';
require __DIR__ . '/../database/db.php';
requireAdministratorJson();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = (int) ($_POST['user_id'] ?? 0);
$action = strtolower(trim((string) ($_POST['action'] ?? '')));
if ($userId <= 0 || !in_array($action, ['activate', 'deactivate'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid account action.']);
    exit;
}
if ($userId === (int) ($_SESSION['idep_user_id'] ?? 0) && $action === 'deactivate') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account.']);
    exit;
}

try {
    $pdo = idepDatabase();
    $account = $pdo->prepare('SELECT id, status FROM user WHERE id = :id LIMIT 1');
    $account->execute([':id' => $userId]);
    $user = $account->fetch();
    if ($user === false) {
        throw new InvalidArgumentException('User account was not found.');
    }

    if ($action === 'deactivate' && strtolower((string) $user['status']) === 'admin') {
        $activeAdminCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM user WHERE lower(CAST(status AS TEXT)) = 'admin' AND deleted_at IS NULL"
        )->fetchColumn();
        if ($activeAdminCount <= 1) {
            throw new InvalidArgumentException('The last active administrator cannot be deactivated.');
        }
    }

    $statement = $pdo->prepare(
        $action === 'activate'
            ? 'UPDATE user SET deleted_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            : 'UPDATE user SET deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
    );
    $statement->execute([':id' => $userId]);
    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    $isValidationError = $error instanceof InvalidArgumentException;
    if (!$isValidationError) {
        error_log('User access toggle failed: ' . $error->getMessage());
    }
    http_response_code($isValidationError ? 422 : 500);
    echo json_encode([
        'success' => false,
        'message' => $isValidationError ? $error->getMessage() : 'Unable to change account access.',
    ]);
}
