<?php
// Initialize session
session_start();
require_once 'config.php';

// Automatic flow redirection: If already logged in, redirect directly to Syafiqah's dashboard (The Main Hub)
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: syafiqah/matching/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTM Campus Lost & Found Assistant</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Premium Design System Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: var(--light-bg);
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="index.php" class="brand">
            🔍 UTM Campus Lost & Found
        </a>
        <div class="nav-links">
            <a href="tey/login.php" class="btn-custom btn-custom-primary" style="padding: 6px 16px; font-size: 12px;">🔑 Sign In</a>
        </div>
    </nav>

    <!-- ===== HEADER / HERO ===== -->
    <div class="header-hero">
        <h1>🔍 UTM Campus Lost & Found Assistant</h1>
        <p>Digitalizing lost-and-found recovery for students and faculty. Rapid keyword matching, secure proof verification, and automated status updates.</p>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="app-container" style="max-width: 800px;">
        
        <div class="glass-card text-center mb-4">
            <h2>👋 Welcome to the Assistant Portal</h2>
            <p class="text-muted mt-2">
                This platform helps the UTM campus community report lost items, match them with found possessions, and securely coordinate handovers.
            </p>
            
            <div class="row g-3 my-4 justify-content-center">
                <div class="col-md-6">
                    <div class="p-4 border rounded bg-white h-100 d-flex flex-column justify-content-between shadow-sm">
                        <div>
                            <span style="font-size: 40px;">🙋‍♂️</span>
                            <h4 style="font-size: 16px; font-weight: 700; margin-top: 10px;">For Students & Staff</h4>
                            <p class="text-muted" style="font-size: 12px; line-height: 1.4;">Sign in with your email to report a lost or found item, run the matching engine, and submit ownership proof claims.</p>
                        </div>
                        <a href="tey/login.php" class="btn-custom btn-custom-primary mt-3 w-100">Sign In / Register</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-4 border rounded bg-white h-100 d-flex flex-column justify-content-between shadow-sm">
                        <div>
                            <span style="font-size: 40px;">👮‍♂️</span>
                            <h4 style="font-size: 16px; font-weight: 700; margin-top: 10px;">For Campus Security</h4>
                            <p class="text-muted" style="font-size: 12px; line-height: 1.4;">Access the secure administrator portal to review submitted proof documents, verify claims, and log physical item handovers.</p>
                        </div>
                        <a href="tan/verify_claim.php" class="btn-custom btn-custom-secondary mt-3 w-100">Admin Portal</a>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-custom alert-custom-info text-start py-3 px-4 mb-0" style="font-size: 13px;">
                💡 <strong>How it works:</strong> Once you log in, you will be taken to your personal <strong>Workspace Dashboard</strong>, which serves as your main control panel. From there, you can file reports, trigger matching scans, and check claim statuses seamlessly.
            </div>
        </div>

    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Final Project</p>
        <div class="team-credits">
            <span><strong>Tey</strong> User Authentication</span>
            <span><strong>Lee</strong> Report Module</span>
            <span><strong>Syafiqah</strong> Matching & Notifications</span>
            <span><strong>Tan</strong> Claim Verification</span>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Conversational Chatbot Widget (Botpress Mockup) -->
    <script src="assets/js/assistant.js"></script>
</body>
</html>