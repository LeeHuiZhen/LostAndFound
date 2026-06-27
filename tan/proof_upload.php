<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// If accessed directly without POST, redirect to claim status
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: claim_status.php");
    exit();
}

$match_id = isset($_POST['match_id']) ? intval($_POST['match_id']) : 0;
$proof_description = isset($_POST['proof_description']) ? $conn->real_escape_string($_POST['proof_description']) : '';

if ($match_id == 0 || empty($proof_description)) {
    die("Please fill in all required fields.");
}

$check_sql = "SELECT * FROM matches WHERE match_id = $match_id AND status = 'pending'";
$check_result = $conn->query($check_sql);
if ($check_result->num_rows == 0) {
    die("Invalid match or already claimed.");
}

// Create uploads directory if it doesn't exist
$target_dir = __DIR__ . '/../uploads/proofs/';
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$file_name = time() . "_" . basename($_FILES["proof_file"]["name"]);
$target_file = $target_dir . $file_name;

// Store relative URL for database portability (works on localhost/subfolders/infinityfree)
$proof_db_path = "uploads/proofs/" . $file_name;

$file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
$allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx');

if (!in_array($file_type, $allowed_types)) {
    die("Invalid file type. Allowed: JPG, PNG, GIF, PDF, DOC.");
}

if ($_FILES["proof_file"]["size"] > 5000000) {
    die("File too large. Maximum size: 5MB.");
}

$success = false;
$error_msg = '';
$claim_id = 0;

if (move_uploaded_file($_FILES["proof_file"]["tmp_name"], $target_file)) {
    $sql = "INSERT INTO claims (match_id, owner_id, proof_url, proof_description, status) 
            VALUES ($match_id, $user_id, '$proof_db_path', '$proof_description', 'pending')";

    if ($conn->query($sql) === TRUE) {
        $update_sql = "UPDATE matches SET status = 'claimed' WHERE match_id = $match_id";
        $conn->query($update_sql);
        $claim_id = $conn->insert_id;
        $success = true;
    } else {
        $error_msg = "Database Error: " . $conn->error;
    }
} else {
    $error_msg = "Sorry, there was an error uploading your proof file. Please check folder permissions.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Status - Lost & Found Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: var(--light-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .status-card { max-width: 500px; width: 100%; }
    </style>
</head>
<body>
    <div class="status-card glass-card text-center">
        <?php if ($success): ?>
            <div class="mb-4">
                <span style="font-size: 50px;">✅</span>
                <h2 class="mt-2 text-success">Claim Submitted!</h2>
                <p class="text-muted">Your claim has been successfully recorded.</p>
            </div>
            
            <div class="alert alert-success alert-custom alert-custom-success py-3 px-4 mb-4 text-start" style="font-size: 13px;">
                <strong>Claim ID:</strong> #<?php echo $claim_id; ?><br>
                <strong>Match ID:</strong> #<?php echo $match_id; ?><br>
                <strong>Status:</strong> <span class="badge bg-secondary">Pending Admin Review</span>
            </div>

            <p class="mb-4" style="font-size: 14px; color: var(--text-muted);">
                Our campus security administrators will review your proof of ownership. You can check the status at any time.
            </p>

            <div class="d-flex flex-column gap-2">
                <a href="claim_status.php" class="btn-custom btn-custom-success py-2">📋 View Claim Status</a>
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline py-2">🏠 Back to Workspace</a>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <span style="font-size: 50px;">❌</span>
                <h2 class="mt-2 text-danger">Submission Failed</h2>
                <p class="text-muted">An error occurred while uploading your proof document.</p>
            </div>
            
            <div class="alert alert-danger alert-custom alert-custom-danger py-3 px-4 mb-4 text-start" style="font-size: 13px;">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>

            <div class="d-flex flex-column gap-2">
                <a href="javascript:history.back()" class="btn-custom btn-custom-primary py-2">⬅ Go Back and Try Again</a>
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline py-2">🏠 Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>