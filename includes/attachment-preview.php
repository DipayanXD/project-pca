<?php
/** Shared by the student and admin complaint detail pages, after complaint authorization. */
require_once __DIR__ . '/attachments.php';
$attachmentUrl = $base . 'attachment.php?id=' . (int)$complaint['id'];
$attachmentName = basename($complaint['attachment']);
$attachmentFile = complaint_attachment_file($complaint['attachment']);
$isPreviewImage = $attachmentFile !== null && complaint_attachment_image_mime($attachmentFile) !== null;
?>
<?php if ($isPreviewImage): ?>
  <a class="attachment-thumbnail" href="<?= e($attachmentUrl) ?>" data-image-preview aria-haspopup="dialog" aria-controls="attachment-preview" aria-label="Preview attached image">
    <img src="<?= e($attachmentUrl) ?>" alt="Image attached to <?= e($complaint['complaint_code']) ?>" width="160" height="112" loading="lazy" />
    <span>View image</span>
  </a>
  <dialog class="attachment-preview" id="attachment-preview" aria-labelledby="attachment-preview-title">
    <div class="attachment-preview-toolbar">
      <h2 id="attachment-preview-title">Image preview</h2>
      <div class="attachment-preview-actions">
        <button type="button" class="button secondary small" data-preview-zoom-out aria-label="Zoom out">−<span>Zoom Out</span></button>
        <output data-preview-zoom aria-live="polite">100%</output>
        <button type="button" class="button secondary small" data-preview-zoom-in aria-label="Zoom in">+<span>Zoom In</span></button>
        <a class="button secondary small" href="<?= e($attachmentUrl . '&download=1') ?>" download>Download</a>
        <button type="button" class="button secondary small" data-preview-close autofocus>Close</button>
      </div>
    </div>
    <div class="attachment-preview-stage" data-preview-stage>
      <p data-preview-status role="status">Loading image…</p>
      <img data-preview-image alt="Image attached to <?= e($complaint['complaint_code']) ?>" hidden />
    </div>
    <p class="attachment-preview-caption">Scroll to explore when zoomed. Click outside the image or press Esc to close.</p>
  </dialog>
<?php else: ?>
  <a class="view-link" href="<?= e($attachmentUrl . '&download=1') ?>" download>
    <svg class="icon" width="16" height="16"><use href="<?= $base ?>icons.svg#i-paperclip" /></svg>
    Download attachment (<?= e($attachmentName) ?>)
  </a>
<?php endif; ?>
