<?php
/**
 * handleUpload() — Secure file upload handler.
 * Security Fix: Validates extension whitelist AND true MIME type to prevent RCE.
 */
function handleUpload($file) {
    // Reject if no file or upload error
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // 1. Extension whitelist — images only
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_extensions)) {
        return false;
    }

    // 2. Real MIME-type check via finfo (bypasses extension spoofing)
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed_mimes)) {
            return false;
        }
    }

    // 3. Size limit — 5 MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }

    // 4. Save to uploads/ with a random unique name
    $target_dir = '../uploads/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $new_filename = uniqid('item_', true) . '.' . $file_ext;
    $target_path  = $target_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Return path relative to project root for database storage
        return 'uploads/' . $new_filename;
    }

    return false;
}
?>
