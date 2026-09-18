<?php
/**
 * Campus Resolve - Student Sidebar Component
 */
declare(strict_types=1);

$base = $base ?? './';
$user = current_user();
$name = $user['name'] ?? 'Student';
$initials = user_initials($name);
$active_nav = $active_nav ?? '';
?>
<aside class="sidebar">
  <div>
    <a class="brand" href="<?= $base ?>index.php">
      <span class="brand-mark"><svg class="icon" width="15" height="15"><use href="<?= $base ?>icons.svg#i-check" /></svg></span>Campus Resolve
    </a>
    <p class="workspace-label">Student workspace</p>
    <nav aria-label="Student navigation">
      <a class="side-link <?= $active_nav === 'dashboard' ? 'active' : '' ?>" href="<?= $base ?>student/dashboard.php">
        <svg class="icon"><use href="<?= $base ?>icons.svg#i-dashboard" /></svg>Dashboard
      </a>
      <a class="side-link <?= $active_nav === 'complaints' ? 'active' : '' ?>" href="<?= $base ?>student/complaints.php">
        <svg class="icon"><use href="<?= $base ?>icons.svg#i-doc" /></svg>My Complaints
      </a>
      <a class="side-link <?= $active_nav === 'submit-complaint' ? 'active' : '' ?>" href="<?= $base ?>student/submit_complaint.php">
        <svg class="icon"><use href="<?= $base ?>icons.svg#i-file-plus" /></svg>Submit Complaint
      </a>
      <a class="side-link <?= $active_nav === 'profile' ? 'active' : '' ?>" href="<?= $base ?>student/profile.php">
        <svg class="icon"><use href="<?= $base ?>icons.svg#i-user" /></svg>Profile
      </a>
    </nav>
  </div>
  <div class="sidebar-bottom">
    <?php if (is_admin()): ?>
      <a class="side-link" href="<?= $base ?>admin/dashboard.php">
        <svg class="icon"><use href="<?= $base ?>icons.svg#i-settings" /></svg>Admin workspace
      </a>
    <?php endif; ?>
    <a class="side-link" href="<?= $base ?>auth/logout.php">
      <svg class="icon"><use href="<?= $base ?>icons.svg#i-arrow-left" /></svg>Sign out
    </a>
    <div class="sidebar-user">
      <span class="avatar"><?= e($initials) ?></span>
      <div><strong><?= e($name) ?></strong><small>Student</small></div>
    </div>
  </div>
</aside>
