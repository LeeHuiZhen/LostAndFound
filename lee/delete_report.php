<?php
include '../config.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../tey/login.php");
    exit;
}

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location: ../syafiqah/matching/dashboard.php");
    exit;
}

$type = isset($_POST['type']) ? $_POST['type'] : '';
$item_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($item_id <= 0 || !in_array($type, ['lost', 'found'])) {
    header("location: ../syafiqah/matching/dashboard.php");
    exit;
}

$table = $type === 'lost' ? 'lost_items' : 'found_items';
$item_column = $type === 'lost' ? 'lost_item_id' : 'found_item_id';

$conn->begin_transaction();

try {
    // Delete any claims tied to matches for this item.
    $claim_delete_sql = "DELETE c FROM claims c JOIN matches m ON c.match_id = m.match_id WHERE m.$item_column = ?";
    $claim_stmt = $conn->prepare($claim_delete_sql);
    $claim_stmt->bind_param('i', $item_id);
    $claim_stmt->execute();
    $claim_stmt->close();

    // Delete any matching rows tied to this item.
    $match_delete_sql = "DELETE FROM matches WHERE $item_column = ?";
    $match_stmt = $conn->prepare($match_delete_sql);
    $match_stmt->bind_param('i', $item_id);
    $match_stmt->execute();
    $match_stmt->close();

    // Verify ownership before deletion.
    $item_select_sql = "SELECT item_id FROM $table WHERE item_id = ? AND user_id = ? LIMIT 1";
    $item_stmt = $conn->prepare($item_select_sql);
    $item_stmt->bind_param('ii', $item_id, $user_id);
    $item_stmt->execute();
    $item_stmt->store_result();

    if ($item_stmt->num_rows === 1) {
        $item_stmt->close();
        $delete_sql = "DELETE FROM $table WHERE item_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param('ii', $item_id, $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        $conn->commit();
    } else {
        $item_stmt->close();
        $conn->rollback();
    }
} catch (Exception $e) {
    $conn->rollback();
}

header("location: ../syafiqah/matching/dashboard.php");
exit;
