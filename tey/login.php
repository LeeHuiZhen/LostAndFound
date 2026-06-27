<?php
session_start();

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: ../index.php");
    exit;
}

require_once '../config.php';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $sql = "SELECT id, name, password FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $name, $hashed_password);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["user_id"] = $id;
                    $_SESSION["user_name"] = $name;
                    
                    header("Location: ../index.php");
                    exit;
                } else {
                    $error = "Invalid password. Please try again.";
                }
            } else {
                $error = "No account found with that email.";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UTM Lost & Found Assistant</title>
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
        .login-card { max-width: 420px; width: 100%; }
    </style>
</head>
<body>
    <div class="login-card glass-card">
        <div class="text-center mb-4">
            <span style="font-size: 40px;">🔐</span>
            <h2 class="mt-2" style="border-bottom: none; padding-bottom: 0;">Sign In</h2>
            <p class="text-muted" style="font-size: 13px;">Access the UTM Lost & Found Portal</p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-custom alert-custom-danger py-2 px-3 mb-3" style="font-size: 13px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="username@utm.my" required>
            </div>
            <div class="form-group mb-4">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-custom btn-custom-primary w-100 py-2">Sign In</button>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="mb-2" style="font-size: 13px; color: var(--text-muted);">
                Don't have an account? <a href="register.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Sign up now</a>
            </p>
            <a href="../index.php" style="font-size: 13px; color: var(--text-muted); text-decoration: none;">🏠 Back to Home</a>
        </div>
    </div>
</body>
</html>