<?php
function handleUpload($file) {
    $target_dir = "../uploads/"; 
    if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }

    $fileExtension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $newFileName = uniqid("item_", true) . "." . $fileExtension;
    $target_file = $target_dir . $newFileName;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }
    return false;
}
?>