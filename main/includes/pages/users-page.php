<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/database/db.php';

$managedUsers = [];
$userLoadError = '';
try {
    $managedUsers = idepDatabase()->query(
        "SELECT id, name, username, email, status, created_at, deleted_at
         FROM user
         ORDER BY deleted_at IS NOT NULL, name COLLATE NOCASE"
    )->fetchAll();
} catch (Throwable $error) {
    $userLoadError = 'User accounts could not be loaded.';
    error_log('User management load failed: ' . $error->getMessage());
}
?>
<section class="user-management-layout">
  <form class="panel user-editor" data-user-form autocomplete="off">
    <div class="user-section-heading">
      <div>
        <p class="eyebrow">ADMINISTRATOR ONLY</p>
        <h2 data-user-form-title>Create user</h2>
        <p>Add a manager or another administrator to the monitoring workspace.</p>
      </div>
      <span class="role-lock" aria-label="Administrator protected">Admin access</span>
    </div>

    <input type="hidden" name="user_id" value="">
    <div class="user-form-grid">
      <label><span>Name</span><input name="name" required maxlength="100" placeholder="Full name"></label>
      <label><span>Username</span><input name="username" required maxlength="60" placeholder="Username" autocapitalize="none" autocomplete="off" data-1p-ignore data-lpignore="true"></label>
      <label><span>Email</span><input name="email" type="email" required maxlength="160" placeholder="name@example.com"></label>
      <label><span>Password</span><input name="password" type="password" minlength="6" placeholder="At least 6 characters" autocomplete="new-password" data-1p-ignore data-lpignore="true"><small data-password-help>Required for a new user.</small></label>
      <label><span>Role</span><select name="status" required><option value="manager">Manager</option><option value="admin">Administrator</option></select></label>
    </div>

    <div class="user-form-actions">
      <button class="primary-action" type="submit" data-user-submit>Save user</button>
      <button class="secondary-action" type="button" data-user-cancel hidden>Cancel editing</button>
      <span class="form-feedback" data-user-message hidden></span>
    </div>
  </form>

  <section class="panel user-directory">
    <div class="user-section-heading">
      <div>
        <h2>Workspace users</h2>
        <p>Managers have every operational feature. Only administrators can open this page.</p>
      </div>
      <strong><?= count($managedUsers) ?> accounts</strong>
    </div>

    <?php if ($userLoadError !== ''): ?>
      <p class="form-feedback is-error"><?= htmlspecialchars($userLoadError) ?></p>
    <?php elseif ($managedUsers === []): ?>
      <p class="empty-state">No accounts have been created yet.</p>
    <?php else: ?>
      <div class="table-scroll">
        <table class="operations-table user-table">
          <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Access</th><th>Actions</th></tr></thead>
          <tbody data-user-table>
            <?php foreach ($managedUsers as $account): ?>
              <?php
                $role = strtolower((string) $account['status']) === 'admin' ? 'admin' : 'manager';
                $isActive = $account['deleted_at'] === null;
                $isCurrentUser = (int) $account['id'] === (int) ($_SESSION['idep_user_id'] ?? 0);
              ?>
              <tr data-user-row
                  data-user-id="<?= (int) $account['id'] ?>"
                  data-name="<?= htmlspecialchars((string) $account['name']) ?>"
                  data-username="<?= htmlspecialchars((string) $account['username']) ?>"
                  data-email="<?= htmlspecialchars((string) $account['email']) ?>"
                  data-role="<?= htmlspecialchars($role) ?>">
                <td><strong><?= htmlspecialchars((string) $account['name']) ?></strong><?= $isCurrentUser ? '<small class="current-user-label">You</small>' : '' ?></td>
                <td><?= htmlspecialchars((string) $account['username']) ?></td>
                <td><?= htmlspecialchars((string) $account['email']) ?></td>
                <td><span class="role-pill role-pill--<?= htmlspecialchars($role) ?>"><?= $role === 'admin' ? 'Administrator' : 'Manager' ?></span></td>
                <td><span class="access-pill access-pill--<?= $isActive ? 'active' : 'inactive' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></td>
                <td class="user-actions">
                  <button type="button" data-user-edit>Edit</button>
                  <button type="button" data-user-toggle data-action="<?= $isActive ? 'deactivate' : 'activate' ?>" <?= $isCurrentUser ? 'disabled title="You cannot deactivate your own account"' : '' ?>><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                  <button class="danger-action" type="button" data-user-delete <?= $isCurrentUser ? 'disabled title="You cannot delete your own account"' : '' ?>>Delete</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</section>
