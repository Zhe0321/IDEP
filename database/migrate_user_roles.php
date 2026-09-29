<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = idepDatabase();
$pdo->beginTransaction();

try {
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
        ':password' => 'admin123',
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
        ':password' => 'manager123',
    ]);

    $pdo->commit();
    echo "User roles and demonstration accounts are ready.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "User role migration failed: {$error->getMessage()}\n");
    exit(1);
}
