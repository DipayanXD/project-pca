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
    <a class="user-menu" href="<?= e($profile_link) ?>">
      <span class="avatar"><?= e($initials) ?></span>
      <span><?= e($first_name) ?></span>
      <svg class="icon" width="15" height="15"><use href="<?= $base ?>icons.svg#i-chevron" /></svg>
    </a>
  </div>
</header>
