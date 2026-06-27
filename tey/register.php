<?php
require_once '../config.php';
$error = ''; $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if (!empty($name) && !empty($email) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

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
    <title>Register – UTM Lost & Found Assistant</title>
    <meta name="description" content="Create your UTM Lost and Found account to report and track items.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(rgba(15, 23, 42, 0.5), rgba(15, 23, 42, 0.7)),
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
            background: rgba(255, 255, 255, 0.96) !important;
            border: 1px solid rgba(255, 255, 255, 0.6) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.4) !important;
            animation: fadeInUp 0.6s ease both;
        }

        .register-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 32px 72px rgba(0, 0, 0, 0.5) !important;
        }

        .brand-bar {
            background: linear-gradient(135deg, #059669, #10b981);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .brand-bar span { font-size: 28px; }

        .brand-bar div h3 {
            font-size: 15px;
            font-weight: 800;
            color: white;
            margin: 0;
            letter-spacing: -0.3px;
        }

        .brand-bar div p {
            font-size: 11px;
            color: rgba(255,255,255,0.75);
            margin: 0;
        }

        .divider-text {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .divider-text::before,
        .divider-text::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }
    </style>
</head>
<body>

    <div class="register-card glass-card">

        <!-- Brand bar -->
        <div class="brand-bar">
            <span>📝</span>
            <div>
                <h3>Create Your Account</h3>
                <p>UTM Lost & Found Community</p>
            </div>
        </div>

        <div class="text-center mb-4">
            <span style="font-size: 38px;">✨</span>
            <h2 class="mt-2" style="border-bottom: none; padding-bottom: 0; font-size: 22px;">Join the Portal</h2>
            <p class="text-muted" style="font-size: 13px;">Sign up to report and track lost items on campus</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-custom alert-custom-danger mb-4" style="font-size: 13px;">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-custom alert-custom-success mb-4" style="font-size: 13px;">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="post">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="e.g., Ahmad bin Razak" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="johndoe@utm.my" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number <span style="color: var(--text-muted); font-weight: 400;">(optional)</span></label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="012-3456789">
            </div>
            <div class="form-group mb-4">
                <label for="password">Password *</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" required>
            </div>
            <button type="submit" class="btn-custom btn-custom-success w-100 py-2" style="font-size: 15px;">
                Create Account →
            </button>
        </form>

        <div class="divider-text">or</div>

        <div class="text-center">
            <p class="mb-2" style="font-size: 13px; color: var(--text-muted);">
                Already have an account?
                <a href="login.php" style="color: var(--primary-color); font-weight: 700; text-decoration: none;">Login here</a>
            </p>
            <a href="../index.php" style="font-size: 12px; color: var(--text-muted); text-decoration: none;">🏠 Back to Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
