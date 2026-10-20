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

// ---- Brute-force protection: 5 failed attempts per IP, then locked for 15 minutes ----
const LOGIN_MAX_FAILURES = 5;
const LOGIN_WINDOW_SECONDS = 900;
$clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$attemptsPdo = null;

try {
    require_once dirname(__DIR__, 2) . '/database/db.php';
    $attemptsPdo = idepDatabase();
    $attemptsPdo->exec(
        'CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            attempted_at INTEGER NOT NULL
        )'
    );
    $recent = $attemptsPdo->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE ip = :ip AND attempted_at > :since'
    );
    $recent->execute([':ip' => $clientIp, ':since' => time() - LOGIN_WINDOW_SECONDS]);
    if ((int) $recent->fetchColumn() >= LOGIN_MAX_FAILURES) {
        http_response_code(429);
        header('Retry-After: ' . LOGIN_WINDOW_SECONDS);
        echo json_encode([
            'success' => false,
            'message' => 'Too many failed sign-in attempts. Please wait 15 minutes and try again.',
        ]);
        exit;
    }
} catch (Throwable) {
    $attemptsPdo = null;
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

if ($authenticatedUser === null) {
    if ($attemptsPdo !== null) {
        try {
            $attemptsPdo->prepare('INSERT INTO login_attempts (ip, attempted_at) VALUES (:ip, :now)')
                ->execute([':ip' => $clientIp, ':now' => time()]);
            $attemptsPdo->prepare('DELETE FROM login_attempts WHERE attempted_at < :old')
                ->execute([':old' => time() - 86400]);
        } catch (Throwable) {
        }
    }
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Incorrect email/username or password.']);
    exit;
}

if ($attemptsPdo !== null) {
    try {
        $attemptsPdo->prepare('DELETE FROM login_attempts WHERE ip = :ip')->execute([':ip' => $clientIp]);
    } catch (Throwable) {
    }
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
