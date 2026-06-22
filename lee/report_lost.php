<?php
include '../config.php';
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../tey/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Lost Item</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        input, textarea { width: 100%; padding: 8px; margin-top: 5px; }
    </style>
</head>
<body>
    <h2>Report Lost Item</h2>
    <form action="save_report.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="type" value="lost">
        <div class="form-group">
            <label>Item Name:</label>
            <input type="text" name="item_name" required>
        </div>
        <div class="form-group">
            <label>Description:</label>
            <textarea name="description" rows="4" required></textarea>
        </div>
        <div class="form-group">
            <label>Location Lost:</label>
            <input type="text" name="location" required>
        </div>
        <div class="form-group">
            <label>Date:</label>
            <input type="date" name="date" required>
        </div>
        <div class="form-group">
            <label>Photo:</label>
            <input type="file" name="item_photo" accept="image/*" required>
        </div>
        <button type="submit">Submit Report</button>
    </form>
</body>
</html>
