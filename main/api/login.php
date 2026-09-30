<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email/username and password are required.']);
    exit;
}

$authenticatedUser = null;

try {
    require_once dirname(__DIR__, 2) . '/database/db.php';
    $statement = idepDatabase()->prepare(
        'SELECT id, name, email, username, password, status
         FROM user
         WHERE deleted_at IS NULL AND (email = :identity OR username = :identity)
         LIMIT 1'
    );
    $statement->execute(['identity' => $username]);
    $user = $statement->fetch();

    if ($user !== false) {
        $storedRole = strtolower(trim((string) ($user['status'] ?? '')));
        $role = match ($storedRole) {
            'admin', 'manager' => $storedRole,
            '1' => (string) $user['username'] === 'admin123' ? 'admin' : 'manager',
            default => null,
        };
        $storedPassword = (string) $user['password'];
        $isHash = password_get_info($storedPassword)['algo'] !== null;
        $passwordMatches = $isHash
            ? password_verify($password, $storedPassword)
            : hash_equals($storedPassword, $password);

        if ($role !== null && $passwordMatches) {
            $user['role'] = $role;
            $authenticatedUser = $user;
        }
    }
} catch (Throwable) {
    $authenticatedUser = null;
}

// Temporary demonstration account for localhost only while the shared user table is empty.
$requestHost = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);
$isLocalhost = in_array($requestHost, ['127.0.0.1', 'localhost'], true);
if ($authenticatedUser === null && $isLocalhost && $username === 'admin123' && $password === 'admin123') {
    $authenticatedUser = [
        'id' => 0,
        'name' => 'Field Team',
        'email' => '',
        'username' => 'admin123',
        'role' => 'admin',
    ];
}

if ($authenticatedUser === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Incorrect email/username or password.']);
    exit;
}

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
session_regenerate_id(true);
$_SESSION['idep_admin'] = true;
$_SESSION['idep_user_id'] = (int) $authenticatedUser['id'];
$_SESSION['idep_user_name'] = (string) $authenticatedUser['name'];
$_SESSION['idep_role'] = (string) $authenticatedUser['role'];

echo json_encode([
    'success' => true,
    'message' => 'Signed in successfully.',
    'role' => (string) $authenticatedUser['role'],
    'redirect' => '/main/admin-dashboard.php',
]);
