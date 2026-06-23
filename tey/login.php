<?php
session_start();
$_SESSION["loggedin"] = true;
require_once '../config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $name, $email_db, $hashed_password);
                    $stmt->fetch();
                    
                    if (password_verify($password, $hashed_password)) {
                        session_start();
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $id;
                        $_SESSION["user_name"] = $name;
                        $_SESSION["user_email"] = $email_db;
                        
                        header("location: ../index.php");
                        exit;
                    } else {
                        $error = "Invalid password.";
                    }
                } else {
                    $error = "No account found with that email.";
                }
            }
            $stmt->close();
        }
    } else {
        $error = "Please enter both email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Lost & Found</title>
    <style>
        body { font: 14px sans-serif; display: flex; justify-content: center; margin-top: 50px; }
        .wrapper { width: 360px; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Login</h2>
        <?php if(!empty($error)) echo '<p class="error">'.$error.'</p>'; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Login</button>
            </div>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
        </form>
    </div>
</body>
</html>
