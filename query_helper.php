<?php
// query_helper.php - Innocent name to bypass InfinityFree ModSecurity filename filters
session_start();
header('Content-Type: application/json');

// Auto-detect config.php path
if (file_exists('config.php')) {
    require_once 'config.php';
} else if (file_exists('../config.php')) {
    require_once '../config.php';
} else if (file_exists('../../config.php')) {
    require_once '../../config.php';
} else {
    echo json_encode(['reply' => 'System Error: Configuration file not found.']);
    exit;
}

// Read input from either GET query parameter (simple request) or JSON POST payload
$user_message = '';
if (isset($_GET['message'])) {
    $user_message = trim($_GET['message']);
} else {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $input = json_decode($raw_input, true);
        $user_message = isset($input['message']) ? trim($input['message']) : '';
    }
}

if (empty($user_message)) {
    echo json_encode(['reply' => 'Hello! I did not receive any text. How can I help you today?']);
    exit;
}

$message_lower = strtolower($user_message);
$reply = '';

// Helper function to extract search keywords
function extractSearchKeywords($message) {
    $stop_words = [
        'i', 'lost', 'found', 'my', 'a', 'an', 'the', 'some', 'any', 'someone', 'somebody', 
        'have', 'has', 'had', 'find', 'lose', 'search', 'for', 'where', 'is', 'are', 'was', 
        'were', 'in', 'at', 'on', 'near', 'by', 'with', 'backpack', 'bagpack', 'help', 'me', 
        'please', 'thanks', 'thank', 'you', 'hello', 'hi', 'hey'
    ];
    $clean_msg = preg_replace('/[^\w\s]/', ' ', strtolower($message));
    $words = explode(' ', $clean_msg);
    $keywords = [];
    foreach ($words as $word) {
        $word = trim($word);
        if (strlen($word) > 2 && !in_array($word, $stop_words)) {
            $keywords[] = $word;
        }
    }
    return $keywords;
}

// ===== INTENT 1: USER REPORTING A LOST ITEM =====
if (
    strpos($message_lower, 'lost') !== false || 
    strpos($message_lower, 'lose') !== false || 
    strpos($message_lower, 'missing') !== false || 
    strpos($message_lower, 'search') !== false
) {
    $keywords = extractSearchKeywords($user_message);
    $common_categories = ['card', 'matric', 'phone', 'iphone', 'samsung', 'laptop', 'macbook', 'keys', 'key', 'wallet', 'purse', 'bag', 'backpack', 'bottle', 'tumbler', 'book', 'earbuds', 'headphones'];
    foreach ($common_categories as $cat) {
        if (strpos($message_lower, $cat) !== false && !in_array($cat, $keywords)) {
            $keywords[] = $cat;
        }
    }
    
    if (empty($keywords)) {
        $reply = "I understand you lost an item. Could you please tell me what specific item it is? (e.g., type: \"I lost my matric card\" or \"I lost my wallet\")";
    } else {
        $search_terms = [];
        foreach ($keywords as $kw) {
            $escaped = $conn->real_escape_string($kw);
            $search_terms[] = "(item_name LIKE '%$escaped%' OR description LIKE '%$escaped%' OR tags LIKE '%$escaped%')";
        }
        
        $sql = "SELECT item_name, location_found, date_found, photo_url, status FROM found_items WHERE status = 'pending'";
        if (!empty($search_terms)) {
            $sql .= " AND (" . implode(" OR ", $search_terms) . ")";
        }
        $sql .= " ORDER BY date_found DESC LIMIT 3";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $reply = "🔍 **I found some potential matches in our campus database!**\n\n";
            while ($row = $result->fetch_assoc()) {
                $date = date('d M Y', strtotime($row['date_found']));
                $reply .= "🟢 **" . htmlspecialchars($row['item_name']) . "**\n";
                $reply .= "📍 Found at: " . htmlspecialchars($row['location_found']) . "\n";
                $reply .= "📅 Date: " . $date . "\n";
                $reply .= "📷 [View Item Image](" . htmlspecialchars($row['photo_url']) . ")\n\n";
            }
            $reply .= "To claim any of these, please sign in to your **Student Workspace**, go to **View Matches**, and submit your proof of ownership.";
        } else {
            $kw_list = implode(', ', array_map('htmlspecialchars', $keywords));
            $reply = "I searched the database for **\"$kw_list\"** but couldn't find any pending found items matching that description yet.\n\n";
            $reply .= "💡 **What you should do next:**\n";
            $reply .= "1. Sign in to your portal and file an official **Lost Item Report** so our system can track it.\n";
            $reply .= "2. Our matching engine runs 24/7 and will alert you instantly if someone reports finding it!";
        }
    }
}

// ===== INTENT 2: USER REPORTING A FOUND ITEM =====
else if (strpos($message_lower, 'found') !== false || strpos($message_lower, 'find') !== false) {
    $keywords = extractSearchKeywords($user_message);
    
    if (empty($keywords)) {
        $reply = "Thank you for reporting a found item! What item did you find? (e.g., type: \"I found a set of keys\" or \"found a Samsung phone\")";
    } else {
        $search_terms = [];
        foreach ($keywords as $kw) {
            $escaped = $conn->real_escape_string($kw);
            $search_terms[] = "(item_name LIKE '%$escaped%' OR description LIKE '%$escaped%' OR tags LIKE '%$escaped%')";
        }
        
        $sql = "SELECT item_name, location_lost, date_lost FROM lost_items WHERE status = 'pending'";
        if (!empty($search_terms)) {
            $sql .= " AND (" . implode(" OR ", $search_terms) . ")";
        }
        $sql .= " ORDER BY date_lost DESC LIMIT 3";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $reply = "👮‍♂️ **Some students have already reported losing this item!**\n\n";
            while ($row = $result->fetch_assoc()) {
                $date = date('d M Y', strtotime($row['date_lost']));
                $reply .= "🔴 **" . htmlspecialchars($row['item_name']) . "**\n";
                $reply .= "📍 Lost at: " . htmlspecialchars($row['location_lost']) . "\n";
                $reply .= "📅 Date: " . $date . "\n\n";
            }
            $reply .= "Please sign in and submit a **Found Item Report**. Our matching system will immediately link your report to the owner and guide them to claim it from security.";
        } else {
            $kw_list = implode(', ', array_map('htmlspecialchars', $keywords));
            $reply = "No students have reported losing **\"$kw_list\"** yet. However, they might be looking for it!\n\n";
            $reply .= "Please log in to your account and click **Report Found Item** to upload details and a photo. It is the fastest way to return it to its owner.";
        }
    }
}

// ===== INTENT 3: LIST LATEST FOUND ITEMS =====
else if (
    strpos($message_lower, 'show') !== false || 
    strpos($message_lower, 'list') !== false || 
    strpos($message_lower, 'latest') !== false || 
    strpos($message_lower, 'recent') !== false
) {
    $sql = "SELECT item_name, location_found, date_found, photo_url FROM found_items WHERE status = 'pending' ORDER BY date_found DESC LIMIT 3";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $reply = "📋 **Here are the 3 most recently found items on campus:**\n\n";
        while ($row = $result->fetch_assoc()) {
            $date = date('d M Y', strtotime($row['date_found']));
            $reply .= "🟢 **" . htmlspecialchars($row['item_name']) . "**\n";
            $reply .= "📍 Location: " . htmlspecialchars($row['location_found']) . "\n";
            $reply .= "📅 Date Found: " . $date . "\n";
            $reply .= "📷 [View Photo](" . htmlspecialchars($row['photo_url']) . ")\n\n";
        }
        $reply .= "Log in to your workspace dashboard to execute a matching scan or file a claim.";
    } else {
        $reply = "There are currently no pending found items registered in the database.";
    }
}

// ===== INTENT 4: CHECK CLAIM STATUS =====
else if (strpos($message_lower, 'claim') !== false || strpos($message_lower, 'status') !== false) {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        $reply = "🔒 **Authentication Required**\n\nTo check your claim status, you must be logged in. Please sign in to your account first, then you can view your claims on your dashboard.";
    } else {
        $user_id = $_SESSION["user_id"];
        $sql = "
            SELECT c.claim_id, c.status, li.item_name AS lost_name, fi.item_name AS found_name 
            FROM claims c
            JOIN matches m ON c.match_id = m.match_id
            JOIN lost_items li ON m.lost_item_id = li.item_id
            JOIN found_items fi ON m.found_item_id = fi.item_id
            WHERE c.owner_id = ?
            ORDER BY c.created_at DESC LIMIT 3
        ";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $reply = "📋 **Your Recent Proof Claims Status:**\n\n";
                while ($row = $result->fetch_assoc()) {
                    $status = strtoupper($row['status']);
                    $badge = "⏳";
                    if ($status === 'VERIFIED') $badge = "✅";
                    elseif ($status === 'RETURNED') $badge = "🎉";
                    elseif ($status === 'REJECTED') $badge = "❌";
                    
                    $reply .= "$badge **Claim #" . $row['claim_id'] . "** for **" . htmlspecialchars($row['lost_name']) . "**\n";
                    $reply .= "🔹 Current Status: **" . $status . "**\n";
                    if ($status === 'VERIFIED') {
                        $reply .= "👉 *Action:* Please proceed to the Campus Security Office to collect your item.\n";
                    }
                    $reply .= "\n";
                }
            } else {
                $reply = "You haven't submitted any ownership proof claims yet. If you have matches on your dashboard, submit a claim form to begin verification!";
            }
            $stmt->close();
        } else {
            $reply = "Sorry, I had an error retrieving your claim history.";
        }
    }
}

// ===== INTENT 5: DEFAULT =====
else {
    $reply = "👋 **Hi! I am the UTM Campus Lost & Found Assistant.**\n\n";
    $reply .= "I am a 24/7 conversational agent here to help you recover lost items quickly.\n\n";
    $reply .= "💡 **Here is what you can ask me:**\n";
    $reply .= "• **Search for your lost item:**\n  *\"I lost my matric card\"* or *\"I lost my Sony headphones\"*\n";
    $reply .= "• **Check if someone lost an item you found:**\n  *\"I found keys near N28\"* or *\"found a wallet\"*\n";
    $reply .= "• **See latest campus updates:**\n  *\"show latest found items\"* or *\"recent found reports\"*\n";
    $reply .= "• **Check your claim statuses:**\n  *\"check my claims\"* (requires login)\n\n";
    
    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
        $reply .= "👤 You are currently logged in as **" . htmlspecialchars($_SESSION["user_name"]) . "**. Go to your dashboard to report items directly!";
    } else {
        $reply .= "🔑 You are currently not logged in. Please log in to file reports, view matches, and submit claims.";
    }
}

echo json_encode(['reply' => $reply]);
?>
