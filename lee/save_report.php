<?php
include '../config.php';
include 'upload.php';
include 'vision_api.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../tey/login.php");
    exit;
}

$success = false;
$error_msg = '';
$tags = '';
$item_name = '';
$type = '';
$location = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $date = $_POST['date'];
    $type = $_POST['type'];

    $photo_url = handleUpload($_FILES['item_photo']);

    if ($photo_url === false) {
        $error_msg = "Failed to upload item photo. Please check the file size and format.";
    } else {
        // Pass the physical path of the uploaded file for Google Vision analysis (fallback to local if no API key)
        $tags = getVisionTags($item_name . " " . $description, "../" . $photo_url);
        $table = ($type == 'lost') ? 'lost_items' : 'found_items';
        $col_loc = ($type == 'lost') ? 'location_lost' : 'location_found';
        $col_date = ($type == 'lost') ? 'date_lost' : 'date_found';
        
        $sql = "INSERT INTO $table (user_id, item_name, description, $col_loc, $col_date, photo_url, tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issssss", $user_id, $item_name, $description, $location, $date, $photo_url, $tags);
            
            if ($stmt->execute()) {
                $success = true;
                $new_item_id = $conn->insert_id;
                
                // ===== REAL-TIME AUTO-MATCHING ENGINE & NOTIFICATIONS =====
                // 1. Fetch opposite items to check for matches instantly
                $opposite_table = ($type == 'lost') ? 'found_items' : 'lost_items';
                $opp_col_loc = ($type == 'lost') ? 'location_found' : 'location_lost';
                $opp_col_date = ($type == 'lost') ? 'date_found' : 'date_lost';
                
                $opp_query = "SELECT * FROM $opposite_table WHERE status = 'pending'";
                $opp_result = $conn->query($opp_query);
                
                if ($opp_result && $opp_result->num_rows > 0) {
                    while ($opp_item = $opp_result->fetch_assoc()) {
                        $score = 0;
                        
                        // Compare Name (max 40 pts)
                        $name1 = strtolower(trim($item_name));
                        $name2 = strtolower(trim($opp_item['item_name']));
                        if ($name1 === $name2) {
                            $score += 40;
                        } else {
                            $words1 = explode(' ', $name1);
                            $words2 = explode(' ', $name2);
                            $common_words = array_intersect($words1, $words2);
                            $trivial = ['the', 'a', 'of', 'in', 'at', 'on', 'with', 'utm', 'item', 'card'];
                            $common_words = array_diff($common_words, $trivial);
                            if (!empty($common_words)) {
                                $score += 25;
                            }
                        }
                        
                        // Compare Tags (max 30 pts)
                        $tags1 = array_map('trim', explode(',', strtolower($tags)));
                        $tags2 = array_map('trim', explode(',', strtolower($opp_item['tags'])));
                        $common_tags = array_intersect($tags1, $tags2);
                        if (!empty($common_tags)) {
                            $score += min(count($common_tags) * 15, 30);
                        }
                        
                        // Compare Location (max 20 pts)
                        $loc1 = strtolower($location);
                        $loc2 = strtolower($opp_item[$opp_col_loc]);
                        $loc_keywords = ['library', 'cafeteria', 'n28', 'n24', 'block', 'lab', 'elevator', 'classroom', 'hall'];
                        foreach ($loc_keywords as $word) {
                            if (strpos($loc1, $word) !== false && strpos($loc2, $word) !== false) {
                                $score += 20;
                                break;
                            }
                        }
                        
                        // Compare Description (max 10 pts)
                        similar_text(strtolower($description), strtolower($opp_item['description']), $pct);
                        if ($pct > 50) {
                            $score += 10;
                        } elseif ($pct > 25) {
                            $score += 5;
                        }
                        
                        // If combined score is a match (>= 40%)
                        if ($score >= 40) {
                            $lost_id = ($type == 'lost') ? $new_item_id : $opp_item['item_id'];
                            $found_id = ($type == 'lost') ? $opp_item['item_id'] : $new_item_id;
                            
                            // Check if this match already exists
                            $check_sql = "SELECT match_id FROM matches WHERE lost_item_id = ? AND found_item_id = ?";
                            $m_check = $conn->prepare($check_sql);
                            $m_check->bind_param("ii", $lost_id, $found_id);
                            $m_check->execute();
                            $m_check->store_result();
                            
                            if ($m_check->num_rows == 0) {
                                $m_check->close();
                                // Insert new match record
                                $ins_match = "INSERT INTO matches (lost_item_id, found_item_id, match_score, status, notification_sent) VALUES (?, ?, ?, 'pending', 0)";
                                $m_ins = $conn->prepare($ins_match);
                                $m_ins->bind_param("iii", $lost_id, $found_id, $score);
                                $m_ins->execute();
                                $m_ins->close();
                                
                                // ===== TRIGGER AUTOMATED EMAIL NOTIFICATION (SIMULATED LOG) =====
                                // Fetch owner (lost reporter) and finder (found reporter) details
                                $lost_user_query = "SELECT u.name, u.email FROM lost_items li JOIN users u ON li.user_id = u.id WHERE li.item_id = ?";
                                $lu_stmt = $conn->prepare($lost_user_query);
                                $lu_stmt->bind_param("i", $lost_id);
                                $lu_stmt->execute();
                                $lu_res = $lu_stmt->get_result()->fetch_assoc();
                                $lu_stmt->close();
                                
                                $found_user_query = "SELECT u.name, u.email FROM found_items fi JOIN users u ON fi.user_id = u.id WHERE fi.item_id = ?";
                                $fu_stmt = $conn->prepare($found_user_query);
                                $fu_stmt->bind_param("i", $found_id);
                                $fu_stmt->execute();
                                $fu_res = $fu_stmt->get_result()->fetch_assoc();
                                $fu_stmt->close();
                                
                                if ($lu_res && $fu_res) {
                                    $timestamp = date('Y-m-d H:i:s');
                                    $email_log = "========================================================================\n";
                                    $email_log .= "AUTOMATED EMAIL ALERT - UTM Campus Lost & Found Recovery System\n";
                                    $email_log .= "Timestamp: $timestamp\n";
                                    $email_log .= "To: " . $lu_res['email'] . " (" . $lu_res['name'] . ")\n";
                                    $email_log .= "Subject: 🎉 Potential Match Found for Your Lost Item: " . (($type == 'lost') ? $item_name : $opp_item['item_name']) . "\n\n";
                                    $email_log .= "Dear " . $lu_res['name'] . ",\n\n";
                                    $email_log .= "Good news! A new item has been reported on campus that matches your lost item:\n";
                                    $email_log .= "- Your Lost Item: " . (($type == 'lost') ? $item_name : $opp_item['item_name']) . "\n";
                                    $email_log .= "- Matching Found Item: " . (($type == 'found') ? $item_name : $opp_item['item_name']) . "\n";
                                    $email_log .= "- Reported Location: " . (($type == 'found') ? $location : $opp_item[$opp_col_loc]) . "\n";
                                    $email_log .= "- Matching Confidence: " . $score . "%\n\n";
                                    $email_log .= "Please sign in to your Student Workspace (http://localhost/syafiqah/matching/dashboard.php),\n";
                                    $email_log .= "go to your 'Active Match Alerts' or 'My Portal Modules > View Matches' section,\n";
                                    $email_log .= "and submit your proof of ownership claim to recover your item.\n\n";
                                    $email_log .= "Best regards,\n";
                                    $email_log .= "UTM Campus Security & Lost/Found Recovery Team\n";
                                    $email_log .= "========================================================================\n\n";
                                    
                                    // Append to local log file in the project root
                                    file_put_contents("../email_alerts.log", $email_log, FILE_APPEND);
                                }
                            } else {
                                $m_check->close();
                            }
                        }
                    }
                }
            } else {
                $error_msg = "Database Error: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_msg = "Statement preparation failed: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Status - Lost & Found Assistant</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Style -->
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
        .status-card {
            max-width: 500px;
            width: 100%;
        }
    </style>
</head>
<body>

    <div class="status-card glass-card text-center">
        <?php if ($success): ?>
            <div class="mb-4">
                <span style="font-size: 50px;">✅</span>
                <h2 class="mt-2 text-success" style="border-bottom: none; padding-bottom: 0;">Report Submitted!</h2>
                <p class="text-muted">Your report has been successfully recorded.</p>
            </div>
            
            <div class="alert alert-success alert-custom alert-custom-success py-3 px-4 mb-4 text-start" style="font-size: 13px;">
                <strong>Item Name:</strong> <?php echo htmlspecialchars($item_name); ?><br>
                <strong>Type:</strong> <?php echo ($type == 'lost') ? 'Lost Item' : 'Found Item'; ?><br>
                <strong>Location:</strong> <?php echo htmlspecialchars($location); ?><br>
                <strong>Tags Generated:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($tags); ?></span>
            </div>

            <p class="mb-4" style="font-size: 14px; color: var(--text-muted);">
                Our matching engine has analyzed the report. You can review matches instantly on your workspace dashboard.
            </p>

            <div class="d-flex flex-column gap-2">
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-success py-2">
                    📋 Go to Student Workspace
                </a>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <span style="font-size: 50px;">❌</span>
                <h2 class="mt-2 text-danger" style="border-bottom: none; padding-bottom: 0;">Submission Failed</h2>
                <p class="text-muted">An error occurred while saving your report.</p>
            </div>
            
            <div class="alert alert-danger alert-custom alert-custom-danger py-3 px-4 mb-4 text-start" style="font-size: 13px;">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>

            <div class="d-flex flex-column gap-2">
                <a href="javascript:history.back()" class="btn-custom btn-custom-primary py-2">
                    ⬅ Go Back and Try Again
                </a>
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline py-2">
                    🏠 Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>