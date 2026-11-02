<?php
declare(strict_types=1);

/** Resolve only files in the existing complaint upload directory. */
function complaint_attachment_file(string $storedPath): ?string {
    if (!preg_match('~^uploads/complaints/[^/\\\\]+$~D', $storedPath)) {
        return null;
    }
    $directory = realpath(__DIR__ . '/../uploads/complaints');
    $file = realpath(__DIR__ . '/../' . $storedPath);
    if ($directory === false || $file === false || dirname($file) !== $directory || !is_file($file) || !is_readable($file)) {
        return null;
    }
    return $file;
}

/** Check both extension and file contents before allowing an inline preview. */
function complaint_attachment_image_mime(string $file): ?string {
    $types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $expected = $types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? null;
    if ($expected === null) {
        return null;
    }
    return (new finfo(FILEINFO_MIME_TYPE))->file($file) === $expected ? $expected : null;
}
