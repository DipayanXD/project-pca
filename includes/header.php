<?php
/**
 * Campus Resolve - Top App Header Component
 */
declare(strict_types=1);

$base = $base ?? './';
$user = current_user();
$name = $user['name'] ?? 'User';
$first_name = explode(' ', trim($name))[0];
$initials = user_initials($name);
$profile_link = is_admin() ? "{$base}admin/profile.php" : "{$base}student/profile.php";
$crumb_title = $crumb_title ?? 'Dashboard';
?>
<header class="app-header">
  <button class="mobile-menu" data-mobile-nav aria-label="Open navigation">
    <svg class="icon"><use href="<?= $base ?>icons.svg#i-menu" /></svg>
  </button>
  <div class="crumb">Campus Resolve <span>/</span> <?= e($crumb_title) ?></div>
  <div class="header-actions">
    <button class="icon-button" data-toast="Notifications are active." aria-label="Notifications">
      <svg class="icon"><use href="<?= $base ?>icons.svg#i-bell" /></svg><i></i>
    </button>
    <div class="user-dropdown-wrapper">
      <button class="user-menu" type="button" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
        <span class="avatar"><?= e($initials) ?></span>
        <span class="user-name"><?= e($first_name) ?></span>
        <svg class="icon chevron" width="14" height="14" aria-hidden="true"><use href="<?= $base ?>icons.svg#i-chevron" /></svg>
      </button>
      <div class="user-dropdown-menu" id="user-dropdown-menu" role="menu" aria-labelledby="user-menu-button">
        <div class="user-dropdown-header">
          <div class="user-dropdown-avatar"><?= e($initials) ?></div>
          <div class="user-dropdown-info">
            <strong><?= e($name) ?></strong>
            <span><?= e($user['email'] ?? '') ?></span>
            <span class="role-badge <?= is_admin() ? 'admin' : 'student' ?>"><?= is_admin() ? 'Administrator' : 'Student' ?></span>
          </div>
        </div>
        <div class="user-dropdown-divider"></div>
        <a class="user-dropdown-item" href="<?= e($profile_link) ?>" role="menuitem">
          <svg class="icon" width="16" height="16"><use href="<?= $base ?>icons.svg#i-user" /></svg>
          Your Profile
        </a>
        <a class="user-dropdown-item" href="<?= is_admin() ? "{$base}admin/dashboard.php" : "{$base}student/dashboard.php" ?>" role="menuitem">
          <svg class="icon" width="16" height="16"><use href="<?= $base ?>icons.svg#i-dashboard" /></svg>
          Dashboard
        </a>
        <div class="user-dropdown-divider"></div>
        <a class="user-dropdown-item danger" href="<?= $base ?>auth/logout.php" role="menuitem">
          <svg class="icon" width="16" height="16"><use href="<?= $base ?>icons.svg#i-arrow-left" /></svg>
          Sign out
        </a>
      </div>
    </div>
  </div>
</header>
