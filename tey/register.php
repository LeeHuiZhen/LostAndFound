<?php
require_once '../config.php';
$error = ''; $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if (!empty($name) && !empty($email) && !empty($password)) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $error = "This email is already registered.";
                } else {
                    // Insert new user
                    $insert_sql = "INSERT INTO users (name, email, phone, password, is_verified) VALUES (?, ?, ?, ?, 0)";
                    if ($insert_stmt = $conn->prepare($insert_sql)) {
                        $insert_stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
                        if ($insert_stmt->execute()) {
                            $success = "Registration successful! Redirecting to verification...";
                            header("refresh:2; url=verify.php?email=" . urlencode($email));
                        } else {
                            $error = "Something went wrong. Please try again.";
                        }
                        $insert_stmt->close();
                    }
                }
            }
            $stmt->close();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Lost & Found</title>
    <style>
        body { font: 14px sans-serif; display: flex; justify-content: center; margin-top: 50px; }
        .wrapper { width: 360px; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; }
        .error { color: red; } .success { color: green; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Register Account</h2>
        <?php if(!empty($error)) echo '<p class="error">'.$error.'</p>'; ?>
        <?php if(!empty($success)) echo '<p class="success">'.$success.'</p>'; ?>
        
        <form action="register.php" method="post">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Sign Up</button>
            </div>
            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </form>
    </div>
</body>
</html>