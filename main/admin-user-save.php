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
$name = trim((string) ($_POST['name'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$role = strtolower(trim((string) ($_POST['status'] ?? 'manager')));

if ($name === '' || $username === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Name, username and a valid email are required.']);
    exit;
}
if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Username must be 3–60 characters using letters, numbers, dots, dashes or underscores.']);
    exit;
}
if (!in_array($role, ['admin', 'manager'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please choose a valid role.']);
    exit;
}
if (($userId === 0 && strlen($password) < 6) || ($password !== '' && strlen($password) < 6)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}
if ($userId === (int) ($_SESSION['idep_user_id'] ?? 0) && $role !== 'admin') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'You cannot remove your own administrator role.']);
    exit;
}

try {
    $pdo = idepDatabase();
    if ($userId > 0) {
        $existing = $pdo->prepare('SELECT id FROM user WHERE id = :id LIMIT 1');
        $existing->execute([':id' => $userId]);
        if (!$existing->fetchColumn()) {
            throw new InvalidArgumentException('User account was not found.');
        }

        $sql = 'UPDATE user SET name = :name, username = :username, email = :email,
                status = :status, updated_at = CURRENT_TIMESTAMP';
        $values = [
            ':name' => $name,
            ':username' => $username,
            ':email' => $email,
            ':status' => $role,
            ':id' => $userId,
        ];
        if ($password !== '') {
            // Plain text is retained for this classroom prototype, as requested.
            $sql .= ', password = :password';
            $values[':password'] = $password;
        }
        $sql .= ' WHERE id = :id';
        $statement = $pdo->prepare($sql);
        $statement->execute($values);
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO user (name, username, email, password, status, created_at)
             VALUES (:name, :username, :email, :password, :status, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            ':name' => $name,
            ':username' => $username,
            ':email' => $email,
            ':password' => $password,
            ':status' => $role,
        ]);
        $userId = (int) $pdo->lastInsertId();
    }

    echo json_encode(['success' => true, 'user_id' => $userId]);
} catch (Throwable $error) {
    $message = $error instanceof InvalidArgumentException
        ? $error->getMessage()
        : (str_contains($error->getMessage(), 'UNIQUE constraint failed')
            ? 'That username or email is already in use.'
            : 'Unable to save the user account.');
    if (!$error instanceof InvalidArgumentException) {
        error_log('User save failed: ' . $error->getMessage());
    }
    http_response_code($error instanceof InvalidArgumentException ? 404 : 422);
    echo json_encode(['success' => false, 'message' => $message]);
}
