<?php
/**
 * Campus Resolve - Shared App Footer
 */
declare(strict_types=1);

$base = $base ?? './';
$flash = get_flash();
?>
  <div class="toast-region" aria-live="polite" aria-label="Notifications"></div>
  <script src="<?= $base ?>assets/js/app.js?v=<?= time() ?>" defer></script>
  <?php if ($flash): ?>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        if (typeof window.showToast === "function") {
          window.showToast(<?= json_encode($flash['message']) ?>, <?= json_encode($flash['type']) ?>);
        }
      });
    </script>
  <?php endif; ?>
</body>
</html>
