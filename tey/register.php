<?php
require_once '../config.php';
$error = ''; $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if (!empty($name) && !empty($email) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $error = "This email is already registered.";
                } else {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UTM Lost & Found Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.65)), 
                        url('../LostAndFound_background.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-card {
            max-width: 460px;
            width: 100%;
            background: rgba(255, 255, 255, 0.94) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            backdrop-filter: blur(8px);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.3) !important;
        }
    </style>
</head>
<body>
    <div class="register-card glass-card">
        <div class="text-center mb-4">
            <span style="font-size: 40px;">📝</span>
            <h2 class="mt-2" style="border-bottom: none; padding-bottom: 0;">Create Account</h2>
            <p class="text-muted" style="font-size: 13px;">Sign up to report and track items</p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-custom alert-custom-danger py-2 px-3 mb-3" style="font-size: 13px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success alert-custom alert-custom-success py-2 px-3 mb-3" style="font-size: 13px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form action="register.php" method="post">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="John Doe" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="johndoe@utm.my" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="012-3456789">
            </div>
            <div class="form-group mb-4">
                <label for="password">Password *</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-custom btn-custom-primary w-100 py-2">Sign Up</button>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="mb-2" style="font-size: 13px; color: var(--text-muted);">
                Already have an account? <a href="login.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Login here</a>
            </p>
            <a href="../index.php" style="font-size: 13px; color: var(--text-muted); text-decoration: none;">🏠 Back to Home</a>
        </div>
    </div>
</body>
</html>