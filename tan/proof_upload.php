<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /tey/login.php");
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
    mkdir($target_dir, 0777, true);
}

$file_name = time() . "_" . basename($_FILES["proof_file"]["name"]);
$target_file = $target_dir . $file_name;

// Get domain for URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$proof_url = $protocol . $host . "/uploads/proofs/" . $file_name;

$file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
$allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx');

if (!in_array($file_type, $allowed_types)) {
    die("Invalid file type. Allowed: JPG, PNG, GIF, PDF, DOC.");
}

if ($_FILES["proof_file"]["size"] > 5000000) {
    die("File too large. Maximum size: 5MB.");
}

if (move_uploaded_file($_FILES["proof_file"]["tmp_name"], $target_file)) {
    $sql = "INSERT INTO claims (match_id, owner_id, proof_url, proof_description, status) 
            VALUES ($match_id, $user_id, '$proof_url', '$proof_description', 'pending')";

    if ($conn->query($sql) === TRUE) {
        $update_sql = "UPDATE matches SET status = 'claimed' WHERE match_id = $match_id";
        $conn->query($update_sql);
        $claim_id = $conn->insert_id;
        ?>
        <!DOCTYPE html>
        <html>
        <head><title>Claim Submitted</title>
        <style>
            body { font-family: Arial; text-align: center; padding: 50px; }
            .success { color: #28a745; font-size: 24px; }
            .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px; }
            .btn:hover { background: #0056b3; }
        </style>
        </head>
        <body>
            <div class="success">✅ Claim submitted successfully!</div>
            <p>Your claim ID: <strong><?php echo $claim_id; ?></strong></p>
            <p>Please wait for admin verification.</p>
            <a href="claim_status.php" class="btn">Check Claim Status</a>
            <a href="/index.php" class="btn">Back to Home</a>
        </body>
        </html>
        <?php
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Sorry, there was an error uploading your file.";
}
?>
