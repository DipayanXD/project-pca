<?php
/**
 * Campus Resolve - Admin Sidebar Component
 */
declare(strict_types=1);

$base = $base ?? './';
$user = current_user();
$name = $user['name'] ?? 'Administrator';
$initials = user_initials($name);
$active_nav = $active_nav ?? '';
?>
<aside class="sidebar">
  <div>
    <a class="brand" href="<?= $base ?>index.php">
      <span class="brand-mark"><svg class="icon" width="15" height="15"><use href="<?= $base ?>icons.svg#i-check" /></svg></span>Campus Resolve
    </a>
    <p class="workspace-label">Admin workspace</p>
    <nav aria-label="Administrator navigation">
      <a class="side-link <?= $active_nav === 'admin-dashboard' ? 'active' : '' ?>" href="<?= $base ?>admin/dashboard.php">
        <svg class="icon"><use href="<?= $base ?>icons.svg#i-dashboard" /></svg>Dashboard
      </a>
      <a class="side-link <?= $active_nav === 'admin-complaints' ? 'active' : '' ?>" href="<?= $base ?>admin/complaints/list.php">
        <svg class="icon"><use href="<?= $base ?>icons.svg#i-doc" /></svg>All Complaints
      </a>
      <a class="side-link <?= $active_nav === 'users' ? 'active' : '' ?>" href="<?= $base ?>admin/users.php">
        <svg class="icon"><use href="<?= $base ?>icons.svg#i-users" /></svg>Users
      </a>
      <a class="side-link <?= $active_nav === 'export' ? 'active' : '' ?>" href="<?= $base ?>admin/complaints/export.php">
        <svg class="icon"><use href="<?= $base ?>icons.svg#i-doc" /></svg>Export CSV
      </a>
      <a class="side-link <?= $active_nav === 'admin-profile' ? 'active' : '' ?>" href="<?= $base ?>admin/profile.php">
        <svg class="icon"><use href="<?= $base ?>icons.svg#i-user" /></svg>Profile
      </a>
    </nav>
  </div>
  <div class="sidebar-bottom">
    <a class="side-link" href="<?= $base ?>student/dashboard.php">
      <svg class="icon"><use href="<?= $base ?>icons.svg#i-dashboard" /></svg>Student view
    </a>
    <a class="side-link" href="<?= $base ?>auth/logout.php">
      <svg class="icon"><use href="<?= $base ?>icons.svg#i-arrow-left" /></svg>Sign out
    </a>
    <div class="sidebar-user">
      <span class="avatar"><?= e($initials) ?></span>
      <div><strong><?= e($name) ?></strong><small>Administrator</small></div>
    </div>
  </div>
</aside>
