<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = idepDatabase();
$pdo->beginTransaction();

try {
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $managerPassword = password_hash('manager123', PASSWORD_DEFAULT);

    $normaliseRoles = $pdo->prepare(
        "UPDATE user
         SET status = CASE
             WHEN username = 'admin123' THEN 'admin'
             WHEN lower(CAST(status AS TEXT)) = 'admin' THEN 'admin'
             WHEN lower(CAST(status AS TEXT)) = 'manager' THEN 'manager'
             ELSE 'manager'
         END,
         updated_at = CURRENT_TIMESTAMP"
    );
    $normaliseRoles->execute();

    $adminStatement = $pdo->prepare(
        "INSERT INTO user (name, username, email, password, status, created_at)
         SELECT :name, :username, :email, :password, 'admin', CURRENT_TIMESTAMP
         WHERE NOT EXISTS (SELECT 1 FROM user WHERE username = :username)"
    );
    $adminStatement->execute([
        ':name' => 'Field Team',
        ':username' => 'admin123',
        ':email' => 'admin@ideptraining.com',
        ':password' => $adminPassword,
    ]);

    $managerStatement = $pdo->prepare(
        "INSERT INTO user (name, username, email, password, status, created_at)
         SELECT :name, :username, :email, :password, 'manager', CURRENT_TIMESTAMP
         WHERE NOT EXISTS (SELECT 1 FROM user WHERE username = :username)"
    );
    $managerStatement->execute([
        ':name' => 'Field Manager',
        ':username' => 'manager123',
        ':email' => 'manager@ideptraining.com',
        ':password' => $managerPassword,
    ]);

    $users = $pdo->query('SELECT id, password FROM user')->fetchAll();
    $passwordUpdate = $pdo->prepare(
        'UPDATE user SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
    );
    foreach ($users as $user) {
        $storedPassword = (string) $user['password'];
        if (password_get_info($storedPassword)['algo'] !== null) {
            continue;
        }

        $passwordUpdate->execute([
            ':id' => (int) $user['id'],
            ':password' => password_hash($storedPassword, PASSWORD_DEFAULT),
        ]);
    }

    $pdo->commit();
    echo "User roles and demonstration accounts are ready.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "User role migration failed: {$error->getMessage()}\n");
    exit(1);
}
