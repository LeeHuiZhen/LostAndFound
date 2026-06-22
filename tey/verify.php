<?php
require_once '../config.php';
$email = isset($_GET['email']) ? $_GET['email'] : '';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    // Auto-verify mock system for project demonstration purposes
    $sql = "UPDATE users SET is_verified = 1 WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $message = "Account successfully verified! Redirecting to login...";
            header("refresh:2; url=login.php");
        } else {
            $message = "Error updating verification status.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Account</title>
    <style>
        body { font: 14px sans-serif; display: flex; justify-content: center; margin-top: 50px; }
        .wrapper { width: 360px; padding: 20px; border: 1px solid #ccc; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Account Verification</h2>
        <p>A mock verification link has been initialized for <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
        <?php if(!empty($message)) echo '<p style="color:green;">'.$message.'</p>'; ?>
        
        <form action="verify.php" method="post">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <button type="submit">Simulate Email Link Verification Click</button>
        </form>
    </div>
</body>
</html>