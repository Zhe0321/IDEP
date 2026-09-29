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
if ($userId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'User account was not found.']);
    exit;
}
if ($userId === (int) ($_SESSION['idep_user_id'] ?? 0)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
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

    if (strtolower((string) $user['status']) === 'admin') {
        $adminCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM user WHERE lower(CAST(status AS TEXT)) = 'admin'"
        )->fetchColumn();
        if ($adminCount <= 1) {
            throw new InvalidArgumentException('The last administrator cannot be deleted.');
        }
    }

    $statement = $pdo->prepare('DELETE FROM user WHERE id = :id');
    $statement->execute([':id' => $userId]);
    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    $isValidationError = $error instanceof InvalidArgumentException;
    if (!$isValidationError) {
        error_log('User delete failed: ' . $error->getMessage());
    }
    http_response_code($isValidationError ? 422 : 500);
    echo json_encode([
        'success' => false,
        'message' => $isValidationError ? $error->getMessage() : 'Unable to delete the user account.',
    ]);
}
