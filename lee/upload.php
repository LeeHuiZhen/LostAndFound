<?php
function handleUpload($file) {
    // Physically save to the uploads directory in the project root (one level up)
    $target_dir = "../uploads/"; 
    if (!is_dir($target_dir)) { 
        mkdir($target_dir, 0755, true); 
    }

    $fileExtension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $newFileName = uniqid("item_", true) . "." . $fileExtension;
    $target_file = $target_dir . $newFileName;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // Return a path relative to the project root for consistent database storage
        return "uploads/" . $newFileName;
    }
    return false;
}
?>
