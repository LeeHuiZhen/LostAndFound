<?php
include '../config.php';
include 'upload.php';
include 'vision_api.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $photo_url = handleUpload($_FILES['item_photo']);

    if ($photo_url === false) {
        die("Upload failed.");
    }

    $tags = getVisionTags($photo_url);
    $table = ($_POST['type'] == 'lost') ? 'lost_items' : 'found_items';
    $col_loc = ($_POST['type'] == 'lost') ? 'location_lost' : 'location_found';
    $col_date = ($_POST['type'] == 'lost') ? 'date_lost' : 'date_found';
    
    $sql = "INSERT INTO $table (user_id, item_name, description, $col_loc, $col_date, photo_url, tags) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $user_id, $_POST['item_name'], $_POST['description'], $_POST['location'], $_POST['date'], $photo_url, $tags);
    
    if ($stmt->execute()) {
        echo "Report saved successfully! <a href='../syafiqah/matching/dashboard.php'>Back to Dashboard</a>";
    } else {
        echo "Database Error: " . $conn->error;
    }
}
?>