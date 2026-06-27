<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php");
    exit;
}

// Fetch all pending lost items
$lost_query = "SELECT * FROM lost_items WHERE status = 'pending'";
$lost_result = $conn->query($lost_query);
$lost_items = [];
if ($lost_result) {
    while ($row = $lost_result->fetch_assoc()) {
        $lost_items[] = $row;
    }
}

// Fetch all pending found items
$found_query = "SELECT * FROM found_items WHERE status = 'pending'";
$found_result = $conn->query($found_query);
$found_items = [];
if ($found_result) {
    while ($row = $found_result->fetch_assoc()) {
        $found_items[] = $row;
    }
}

$new_matches_count = 0;
$updated_matches_count = 0;

foreach ($lost_items as $lost) {
    foreach ($found_items as $found) {
        $score = 0;
        
        // Name Match (max 40 pts)
        $lost_name = strtolower(trim($lost['item_name']));
        $found_name = strtolower(trim($found['item_name']));
        if ($lost_name === $found_name) {
            $score += 40;
        } else {
            $lost_words = explode(' ', $lost_name);
            $found_words = explode(' ', $found_name);
            $common_words = array_intersect($lost_words, $found_words);
            $trivial = ['the', 'a', 'of', 'in', 'at', 'on', 'with', 'utm', 'item', 'card'];
            $common_words = array_diff($common_words, $trivial);
            if (!empty($common_words)) {
                $score += 25;
            }
        }

        // Tag Match (max 30 pts)
        $lost_tags = array_map('trim', explode(',', strtolower($lost['tags'])));
        $found_tags = array_map('trim', explode(',', strtolower($found['tags'])));
        $common_tags = array_intersect($lost_tags, $found_tags);
        if (!empty($common_tags)) {
            $score += min(count($common_tags) * 15, 30);
        }

        // Location Match (max 20 pts)
        $lost_loc = strtolower($lost['location_lost']);
        $found_loc = strtolower($found['location_found']);
        $loc_keywords = ['library', 'cafeteria', 'n28', 'n24', 'block', 'lab', 'elevator', 'classroom', 'hall'];
        foreach ($loc_keywords as $word) {
            if (strpos($lost_loc, $word) !== false && strpos($found_loc, $word) !== false) {
                $score += 20;
                break; 
            }
        }

        // Description Similarity (max 10 pts)
        similar_text(strtolower($lost['description']), strtolower($found['description']), $pct);
        if ($pct > 50) {
            $score += 10;
        } elseif ($pct > 25) {
            $score += 5;
        }

        // Save match if combined score is 40 or higher
        if ($score >= 40) {
            $check_sql = "SELECT match_id FROM matches WHERE lost_item_id = ? AND found_item_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("ii", $lost['item_id'], $found['item_id']);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($match_id);
                $stmt->fetch();
                $stmt->close();
                
                $update_sql = "UPDATE matches SET match_score = ? WHERE match_id = ?";
                $up_stmt = $conn->prepare($update_sql);
                $up_stmt->bind_param("ii", $score, $match_id);
                $up_stmt->execute();
                $up_stmt->close();
                
                $updated_matches_count++;
            } else {
                $stmt->close();
                $insert_sql = "INSERT INTO matches (lost_item_id, found_item_id, match_score, status, notification_sent) VALUES (?, ?, ?, 'pending', 0)";
                $in_stmt = $conn->prepare($insert_sql);
                $in_stmt->bind_param("iii", $lost['item_id'], $found['item_id'], $score);
                $in_stmt->execute();
                $in_stmt->close();
                
                $new_matches_count++;
            }
        }
    }
}

header("Location: dashboard.php?action=run_matching");
exit;
?>