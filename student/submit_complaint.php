<?php
/**
 * Campus Resolve - Student Complaint Submission & Edit Page
 * 
 * Allows students to submit new complaints or edit their pending complaints.
 * Features:
 *  - Clear, straightforward PHP & MySQL code structure (easy to read & maintain)
 *  - Complete server-side input validation and error feedback
 *  - Secure file attachment handling (PDF, JPG, PNG up to 10 MB)
 *  - Transactional database inserts/updates with automatic timeline tracking
 *  - Draft saving via localStorage matching prototype-4 design
 */

declare(strict_types=1);

$base = '../';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check_student.php';

// -----------------------------------------------------------------------------
// 1. Authenticated Student Context & Available Categories
// -----------------------------------------------------------------------------
$user = current_user();
$userId = (int)($user['id'] ?? 0);
$categories = ['Infrastructure', 'IT', 'Facilities', 'Laboratory', 'Other'];

// -----------------------------------------------------------------------------
// 2. Check If Editing an Existing Complaint (via GET or POST id)
// -----------------------------------------------------------------------------
$complaintId = (int)($_GET['id'] ?? $_POST['complaint_id'] ?? 0);
$isEditing = false;
$existing = null;

// Form values (default empty or populated from database)
$values = [
    'title'       => '',
    'category'    => '',
    'location'    => '',
    'description' => ''
];
$errors = [];
$fieldErrors = [];

if ($complaintId > 0) {
    // Fetch complaint and verify student ownership and Pending status
    $stmt = $pdo->prepare("SELECT * FROM `complaints` WHERE `id` = ? AND `user_id` = ?");
    $stmt->execute([$complaintId, $userId]);
    $existing = $stmt->fetch();

    if (!$existing) {
        set_flash('Complaint not found.', 'error');
        header('Location: complaints.php');
        exit;
    }

    if ($existing['status'] !== 'Pending') {
        set_flash('Only pending complaints can be edited. This complaint is already ' . $existing['status'] . '.', 'error');
        header('Location: complaint_details.php?id=' . $complaintId);
        exit;
    }

    $isEditing = true;
    $values = [
        'title'       => $existing['title'],
        'category'    => $existing['category'],
        'location'    => $existing['location'],
        'description' => $existing['description']
    ];
}

// -----------------------------------------------------------------------------
// 3. Handle Form Submission (POST)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step A: Validate CSRF Token
    if (!validate_csrf()) {
        $errors[] = 'Your session token expired. Please refresh the page and try again.';
    }

    // Step B: Sanitize and Collect Input Values
    $values['title']       = trim((string)($_POST['title'] ?? ''));
    $values['category']    = trim((string)($_POST['category'] ?? ''));
    $values['location']    = trim((string)($_POST['location'] ?? ''));
    $values['description'] = trim((string)($_POST['description'] ?? ''));
    $removeAttachment      = !empty($_POST['remove_attachment']);

    // Step C: Server-Side Field Validation Rules
    if ($values['title'] === '') {
        $fieldErrors['title'] = 'Complaint title is required.';
        $errors[] = 'Please enter a complaint title.';
    } elseif (mb_strlen($values['title']) < 5) {
        $fieldErrors['title'] = 'Title must be at least 5 characters.';
        $errors[] = 'Title must be at least 5 characters.';
    } elseif (mb_strlen($values['title']) > 200) {
        $fieldErrors['title'] = 'Title cannot exceed 200 characters.';
        $errors[] = 'Title cannot exceed 200 characters.';
    }

    if ($values['category'] === '') {
        $fieldErrors['category'] = 'Please choose a category.';
        $errors[] = 'Please select a valid complaint category.';
    } elseif (!in_array($values['category'], $categories, true)) {
        $fieldErrors['category'] = 'Invalid category selected.';
        $errors[] = 'Please choose a valid category from the list.';
    }

    if ($values['location'] === '') {
        $fieldErrors['location'] = 'Location / department is required.';
        $errors[] = 'Please specify the location or department.';
    } elseif (mb_strlen($values['location']) > 150) {
        $fieldErrors['location'] = 'Location cannot exceed 150 characters.';
        $errors[] = 'Location cannot exceed 150 characters.';
    }

    if ($values['description'] === '') {
        $fieldErrors['description'] = 'Description is required.';
        $errors[] = 'Please provide details in the description.';
    } elseif (mb_strlen($values['description']) < 10) {
        $fieldErrors['description'] = 'Description must be at least 10 characters.';
        $errors[] = 'Description must be at least 10 characters long.';
    } elseif (mb_strlen($values['description']) > 5000) {
        $fieldErrors['description'] = 'Description cannot exceed 5,000 characters.';
        $errors[] = 'Description cannot exceed 5,000 characters.';
    }

    // Step D: Validate File Attachment (Optional)
    $uploadedFilePath = null;
    $file = $_FILES['attachment'] ?? null;

    if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $fieldErrors['attachment'] = 'File upload failed. Please try again.';
            $errors[] = 'The attachment could not be uploaded.';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $fieldErrors['attachment'] = 'File exceeds maximum 10 MB limit.';
            $errors[] = 'Attachment must be 10 MB or smaller.';
        } else {
            // Check file MIME type safely
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowedMimes = [
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/png'       => 'png'
            ];

            if (!isset($allowedMimes[$mime])) {
                $fieldErrors['attachment'] = 'Only PDF, JPG, or PNG files are accepted.';
                $errors[] = 'Attachment must be a valid PDF, JPG, or PNG file.';
            } else {
                // Prepare upload folder and unique filename
                $uploadDir = dirname(__DIR__) . '/uploads/complaints';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileExt = $allowedMimes[$mime];
                $uniqueName = bin2hex(random_bytes(16)) . '.' . $fileExt;
                $targetPath = $uploadDir . '/' . $uniqueName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $uploadedFilePath = 'uploads/complaints/' . $uniqueName;
                } else {
                    $fieldErrors['attachment'] = 'Failed to save attachment file.';
                    $errors[] = 'The attachment could not be saved to server.';
                }
            }
        }
    }

    // Step E: Save to Database (If no validation errors)
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($isEditing) {
                // -------------------------------------------------------------
                // UPDATE Existing Pending Complaint
                // -------------------------------------------------------------
                $attachmentToSave = $existing['attachment'];
                $oldFileToDelete = null;

                if ($uploadedFilePath !== null) {
                    $attachmentToSave = $uploadedFilePath;
                    if (!empty($existing['attachment'])) {
                        $oldFileToDelete = dirname(__DIR__) . '/' . $existing['attachment'];
                    }
                } elseif ($removeAttachment) {
                    $attachmentToSave = null;
                    if (!empty($existing['attachment'])) {
                        $oldFileToDelete = dirname(__DIR__) . '/' . $existing['attachment'];
                    }
                }

                $stmt = $pdo->prepare("
                    UPDATE `complaints` 
                    SET `title` = ?, `category` = ?, `location` = ?, `description` = ?, `attachment` = ? 
                    WHERE `id` = ? AND `user_id` = ? AND `status` = 'Pending'
                ");
                $stmt->execute([
                    $values['title'],
                    $values['category'],
                    $values['location'],
                    $values['description'],
                    $attachmentToSave,
                    $complaintId,
                    $userId
                ]);

                // Record timeline note for modification
                $stmt = $pdo->prepare("
                    INSERT INTO `complaint_updates` (`complaint_id`, `admin_id`, `status`, `remark`) 
                    VALUES (?, NULL, 'Submitted', 'Complaint details updated by student.')
                ");
                $stmt->execute([$complaintId]);

                $pdo->commit();

                // Clean up replaced/removed file
                if ($oldFileToDelete && is_file($oldFileToDelete)) {
                    @unlink($oldFileToDelete);
                }

                set_flash('Complaint updated successfully.', 'success');
                header('Location: complaint_details.php?id=' . $complaintId);
                exit;

            } else {
                // -------------------------------------------------------------
                // INSERT New Complaint
                // -------------------------------------------------------------
                // Generate next readable complaint code (e.g. CR-1043)
                $codeStmt = $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(complaint_code, 4) AS UNSIGNED)), 1040) AS max_num FROM `complaints` WHERE `complaint_code` REGEXP '^CR-[0-9]+$'");
                $maxNum = (int)($codeStmt->fetchColumn() ?: 1040);
                $nextCode = 'CR-' . (max($maxNum, 1040) + 1);

                $stmt = $pdo->prepare("
                    INSERT INTO `complaints` 
                        (`complaint_code`, `user_id`, `title`, `category`, `location`, `description`, `attachment`, `status`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
                ");
                $stmt->execute([
                    $nextCode,
                    $userId,
                    $values['title'],
                    $values['category'],
                    $values['location'],
                    $values['description'],
                    $uploadedFilePath
                ]);

                $newComplaintId = (int)$pdo->lastInsertId();

                // Insert initial timeline event
                $stmt = $pdo->prepare("
                    INSERT INTO `complaint_updates` (`complaint_id`, `admin_id`, `status`, `remark`) 
                    VALUES (?, NULL, 'Submitted', 'Complaint registered in system.')
                ");
                $stmt->execute([$newComplaintId]);

                $pdo->commit();

                set_flash('Complaint submitted successfully. Reference code: ' . $nextCode, 'success');
                header('Location: complaint_details.php?id=' . $newComplaintId);
                exit;
            }

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Remove newly uploaded file if DB failed
            if ($uploadedFilePath) {
                $newFile = dirname(__DIR__) . '/' . $uploadedFilePath;
                if (is_file($newFile)) @unlink($newFile);
            }
            error_log('Complaint submission error: ' . $e->getMessage());
            $errors[] = 'A database error occurred while saving your complaint. Please try again.';
        }
    }
}

// Navigation & page breadcrumb state
$active_nav = 'submit-complaint';
$crumb_title = $isEditing ? 'Edit Complaint' : 'Submit Complaint';
$flash = get_flash();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?= $isEditing ? 'Edit Complaint' : 'Submit Complaint' ?> — Campus Resolve</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css" />
  </head>
  <body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-shell">
      <?php require __DIR__ . '/../includes/student-sidebar.php'; ?>

      <div class="app-body">
        <?php require __DIR__ . '/../includes/header.php'; ?>

        <main id="main-content" class="app-main">
          <section class="form-page">
            <div class="page-head">
              <p class="eyebrow"><?= $isEditing ? 'Edit complaint' : 'New complaint' ?></p>
              <h1><?= $isEditing ? 'Update your complaint.' : 'Tell us what needs attention.' ?></h1>
              <p>Provide a few details so the right campus team can help.</p>
            </div>

            <!-- Server Flash Feedback -->
            <?php if ($flash): ?>
              <div class="form-alert <?= $flash['type'] === 'success' ? 'success' : '' ?>" role="status">
                <?= e($flash['message']) ?>
              </div>
            <?php endif; ?>

            <!-- Validation Errors Summary -->
            <?php if (!empty($errors)): ?>
              <div class="form-alert" role="alert">
                <strong>Please check the following before submitting:</strong>
                <ul>
                  <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <!-- Draft Notice Prompt (Add Mode) -->
            <?php if (!$isEditing): ?>
              <div id="draft-banner" class="form-alert success" style="display: none; align-items: center; justify-content: space-between;">
                <span>You have an unsaved complaint draft saved on this device.</span>
                <div style="display: flex; gap: 8px;">
                  <button type="button" class="button small" id="btn-restore-draft">Restore draft</button>
                  <button type="button" class="button secondary small" id="btn-dismiss-draft">Dismiss</button>
                </div>
              </div>
            <?php endif; ?>

            <form
              class="complaint-form panel"
              method="post"
              action="<?= e($_SERVER['PHP_SELF']) ?><?= $isEditing ? '?id=' . $complaintId : '' ?>"
              enctype="multipart/form-data"
              data-validate
              novalidate
            >
              <?= csrf_field() ?>
              <input type="hidden" name="complaint_id" value="<?= $complaintId ?>" />

              <div class="form-grid">
                <!-- Complaint Title -->
                <label class="field full">
                  <span>Complaint title</span>
                  <input
                    name="title"
                    id="complaint-title"
                    placeholder="e.g. Classroom fan not working"
                    value="<?= e($values['title']) ?>"
                    minlength="5"
                    maxlength="200"
                    required
                    aria-invalid="<?= !empty($fieldErrors['title']) ? 'true' : 'false' ?>"
                  />
                  <small class="error"><?= e($fieldErrors['title'] ?? '') ?></small>
                </label>

                <!-- Category -->
                <label class="field">
                  <span>Category</span>
                  <select
                    name="category"
                    id="complaint-category"
                    required
                    aria-invalid="<?= !empty($fieldErrors['category']) ? 'true' : 'false' ?>"
                  >
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?= e($cat) ?>" <?= $values['category'] === $cat ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="error"><?= e($fieldErrors['category'] ?? '') ?></small>
                </label>

                <!-- Location / Department -->
                <label class="field">
                  <span>Location / department</span>
                  <input
                    name="location"
                    id="complaint-location"
                    placeholder="e.g. Block B · Room 204"
                    value="<?= e($values['location']) ?>"
                    maxlength="150"
                    required
                    aria-invalid="<?= !empty($fieldErrors['location']) ? 'true' : 'false' ?>"
                  />
                  <small class="error"><?= e($fieldErrors['location'] ?? '') ?></small>
                </label>

                <!-- Description -->
                <label class="field full">
                  <span>Description</span>
                  <textarea
                    name="description"
                    id="complaint-description"
                    placeholder="What happened? Include anything that would help the team understand the issue."
                    minlength="10"
                    maxlength="5000"
                    required
                    aria-invalid="<?= !empty($fieldErrors['description']) ? 'true' : 'false' ?>"
                  ><?= e($values['description']) ?></textarea>
                  <small class="error"><?= e($fieldErrors['description'] ?? '') ?></small>
                </label>

                <!-- Attachment Upload Dropzone Matching Prototype-4 -->
                <div class="field full">
                  <span>Attachment</span>
                  <label class="attachment full <?= (!empty($existing['attachment']) && empty($removeAttachment)) ? 'has-file' : '' ?>" id="attachment-dropzone">
                    <input
                      type="file"
                      name="attachment"
                      id="attachment-input"
                      accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                      aria-label="Add attachment"
                    />
                    <span>
                      <svg class="icon" width="20" height="20">
                        <use href="<?= $base ?>icons.svg#i-paperclip" />
                      </svg>
                    </span>
                    <div>
                      <strong id="attachment-name">
                        <?php if (!empty($existing['attachment']) && empty($removeAttachment)): ?>
                          Current file: <?= e(basename($existing['attachment'])) ?>
                        <?php else: ?>
                          Add an attachment
                        <?php endif; ?>
                      </strong>
                      <small id="attachment-hint">
                        <?php if (!empty($existing['attachment']) && empty($removeAttachment)): ?>
                          Click to replace with a new PDF, JPG, or PNG up to 10 MB
                        <?php else: ?>
                          Optional · PDF, JPG, or PNG up to 10 MB
                        <?php endif; ?>
                      </small>
                    </div>
                    <em id="attachment-action"><?= !empty($existing['attachment']) ? 'Change' : 'Browse' ?></em>
                  </label>
                  <small class="error"><?= e($fieldErrors['attachment'] ?? '') ?></small>

                  <?php if (!empty($existing['attachment'])): ?>
                    <div style="margin-top: 8px; font-size: 12px; display: flex; align-items: center; gap: 16px;">
                      <a href="<?= $base . e($existing['attachment']) ?>" target="_blank" rel="noopener" class="view-link">
                        View current attachment
                        <svg class="icon" width="14" height="14"><use href="<?= $base ?>icons.svg#i-arrow" /></svg>
                      </a>
                      <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: var(--muted);">
                        <input type="checkbox" name="remove_attachment" value="1" id="remove-attachment-check" />
                        Remove current attachment
                      </label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Form Actions Matching Prototype-4 -->
              <div class="form-actions">
                <?php if (!$isEditing): ?>
                  <button
                    class="button secondary"
                    type="button"
                    id="btn-save-draft"
                  >
                    Save as draft
                  </button>
                <?php else: ?>
                  <a class="button secondary" href="complaint_details.php?id=<?= $complaintId ?>">
                    Cancel
                  </a>
                <?php endif; ?>

                <button class="button primary" type="submit" id="btn-submit-complaint">
                  <?= $isEditing ? 'Save changes' : 'Submit complaint' ?>
                  <svg class="icon" width="18" height="18">
                    <use href="<?= $base ?>icons.svg#i-arrow" />
                  </svg>
                </button>
              </div>
            </form>
          </section>
        </main>
      </div>
    </div>

    <!-- Progressive enhancement script for file picker and draft management -->
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const fileInput = document.getElementById("attachment-input");
        const dropzone = document.getElementById("attachment-dropzone");
        const nameLabel = document.getElementById("attachment-name");
        const hintLabel = document.getElementById("attachment-hint");
        const actionLabel = document.getElementById("attachment-action");
        const removeCheck = document.getElementById("remove-attachment-check");

        // Format file sizes nicely
        function formatBytes(bytes) {
          if (bytes < 1024) return bytes + " B";
          if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " KB";
          return (bytes / 1048576).toFixed(1) + " MB";
        }

        // Handle attachment file selection change
        if (fileInput) {
          fileInput.addEventListener("change", function () {
            if (this.files && this.files.length > 0) {
              const file = this.files[0];
              nameLabel.textContent = file.name;
              hintLabel.textContent = "Selected: " + formatBytes(file.size) + " · Ready to upload";
              actionLabel.textContent = "Replace";
              dropzone.classList.add("has-file");
              if (removeCheck) removeCheck.checked = false;
            }
          });
        }

        if (removeCheck) {
          removeCheck.addEventListener("change", function () {
            if (this.checked) {
              nameLabel.textContent = "Attachment will be removed upon saving";
              hintLabel.textContent = "Select a file above if you wish to upload a new one instead";
              dropzone.classList.remove("has-file");
            }
          });
        }

        // Draft storage management (New complaint mode)
        const isEditing = <?= json_encode($isEditing) ?>;
        const draftKey = "campus_resolve_student_complaint_draft";

        if (!isEditing) {
          const draftBanner = document.getElementById("draft-banner");
          const saveDraftBtn = document.getElementById("btn-save-draft");
          const restoreDraftBtn = document.getElementById("btn-restore-draft");
          const dismissDraftBtn = document.getElementById("btn-dismiss-draft");

          const titleInput = document.getElementById("complaint-title");
          const categoryInput = document.getElementById("complaint-category");
          const locationInput = document.getElementById("complaint-location");
          const descInput = document.getElementById("complaint-description");

          // Check if existing saved draft exists in localStorage
          try {
            const savedDraft = JSON.parse(localStorage.getItem(draftKey));
            if (savedDraft && (savedDraft.title || savedDraft.description)) {
              // Only offer restore if inputs are currently blank
              if (!titleInput.value && !descInput.value && draftBanner) {
                draftBanner.style.display = "flex";
              }
            }
          } catch (e) {}

          // Save as draft button
          if (saveDraftBtn) {
            saveDraftBtn.addEventListener("click", function () {
              const draftData = {
                title: titleInput.value.trim(),
                category: categoryInput.value,
                location: locationInput.value.trim(),
                description: descInput.value.trim(),
                savedAt: new Date().toISOString()
              };

              localStorage.setItem(draftKey, JSON.stringify(draftData));
              if (typeof window.showToast === "function") {
                window.showToast("Draft saved on this device.", "info");
              } else {
                alert("Draft saved on this device.");
              }
            });
          }

          // Restore draft button
          if (restoreDraftBtn) {
            restoreDraftBtn.addEventListener("click", function () {
              try {
                const savedDraft = JSON.parse(localStorage.getItem(draftKey));
                if (savedDraft) {
                  if (savedDraft.title) titleInput.value = savedDraft.title;
                  if (savedDraft.category) categoryInput.value = savedDraft.category;
                  if (savedDraft.location) locationInput.value = savedDraft.location;
                  if (savedDraft.description) descInput.value = savedDraft.description;
                  if (draftBanner) draftBanner.style.display = "none";
                  if (typeof window.showToast === "function") {
                    window.showToast("Draft restored.", "success");
                  }
                }
              } catch (e) {}
            });
          }

          // Dismiss draft banner
          if (dismissDraftBtn) {
            dismissDraftBtn.addEventListener("click", function () {
              if (draftBanner) draftBanner.style.display = "none";
            });
          }

          // Clear draft on successful submit
          const form = document.querySelector(".complaint-form");
          if (form) {
            form.addEventListener("submit", function () {
              localStorage.removeItem(draftKey);
            });
          }
        }
      });
    </script>

    <?php require __DIR__ . '/../includes/footer.php'; ?>