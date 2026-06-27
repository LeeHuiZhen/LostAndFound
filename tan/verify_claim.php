<?php
session_start();
require_once '../config.php';

// Demo convenience: allow toggling admin mode for project evaluation
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'enable_admin') {
        $_SESSION['is_admin'] = true;
        header("Location: verify_claim.php"); exit;
    } elseif ($_GET['action'] == 'disable_admin') {
        $_SESSION['is_admin'] = false;
        header("Location: verify_claim.php"); exit;
    }
}

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Handle verification actions
$msg = '';
if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['claim_action'])) {
    $cid = intval($_POST['claim_id']);
    $act = $_POST['claim_action']; // verify or reject

    if ($act == 'verify') {
        $u = $conn->prepare("UPDATE claims SET status = 'verified' WHERE claim_id = ?");
        $u->bind_param("i", $cid); $u->execute(); $u->close();

        // Also update match status to verified
        $stmt = $conn->prepare("SELECT match_id FROM claims WHERE claim_id = ?");
        $stmt->bind_param("i", $cid); $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($res) {
            $mid = $res['match_id'];
            $conn->query("UPDATE matches SET status = 'verified' WHERE match_id = $mid");
        }
        $msg = "✅ Claim #$cid has been verified successfully.";
    } elseif ($act == 'reject') {
        $u = $conn->prepare("UPDATE claims SET status = 'rejected' WHERE claim_id = ?");
        $u->bind_param("i", $cid); $u->execute(); $u->close();

        // Revert match status to pending
        $stmt = $conn->prepare("SELECT match_id FROM claims WHERE claim_id = ?");
        $stmt->bind_param("i", $cid); $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($res) {
            $mid = $res['match_id'];
            $conn->query("UPDATE matches SET status = 'pending' WHERE match_id = $mid");
        }
        $msg = "❌ Claim #$cid has been rejected.";
    }
}

// Fetch all claims
$claims_result = $conn->query("
    SELECT c.*, u.name AS owner_name, u.email AS owner_email,
           li.item_name AS lost_item, li.description AS lost_desc,
           fi.item_name AS found_item, fi.description AS found_desc
    FROM claims c
    JOIN users u ON c.owner_id = u.id
    JOIN matches m ON c.match_id = m.match_id
    JOIN lost_items li ON m.lost_item_id = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    ORDER BY c.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal — Claim Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: var(--bg-base); }
    </style>
</head>
<body>
    <nav class="custom-navbar">
        <a href="../index.php" class="brand">🔍 UTM Lost &amp; Found · Admin Workspace</a>
        <div class="nav-links">
            <a href="../index.php" style="font-size:13px; color:var(--text-muted);">🏠 Home</a>
            <?php if ($is_admin): ?>
                <a href="verify_claim.php?action=disable_admin" class="btn-custom btn-custom-secondary" style="padding:7px 16px; font-size:12px;">Exit Admin Mode</a>
            <?php else: ?>
                <a href="verify_claim.php?action=enable_admin" class="btn-custom btn-custom-primary" style="padding:7px 16px; font-size:12px;">Enter Admin Mode</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="header-hero" style="padding:50px 20px 40px; background:linear-gradient(135deg, #070b14 0%, #1c0f35 50%, #070b14 100%); border-bottom: 1px solid rgba(99,102,241,0.2);">
        <h1 style="font-size:32px; margin-bottom:8px;">👮 Campus Security Admin Portal</h1>
        <p>Review submitted ownership claims, verify item details, and log physical handovers.</p>
    </div>

    <div class="app-container" style="max-width:1000px;">

        <!-- Demo Mode Banner -->
        <div class="alert-custom alert-custom-info" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong>🛠️ Project Demo Assistant:</strong>
                <?php if ($is_admin): ?>
                    You are currently in <strong style="color:#22d3ee;">Admin Mode</strong>. You have full access to approve, reject, and log handovers.
                <?php else: ?>
                    You are in <strong style="color:#fb7185;">Visitor Mode</strong>. You can view claims but cannot modify them.
                <?php endif; ?>
            </div>
            <div>
                <?php if ($is_admin): ?>
                    <a href="verify_claim.php?action=disable_admin" class="btn-custom btn-custom-secondary" style="padding:6px 12px; font-size:11px;">Switch to Visitor</a>
                <?php else: ?>
                    <a href="verify_claim.php?action=enable_admin" class="btn-custom btn-custom-primary" style="padding:6px 12px; font-size:11px;">Switch to Admin</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert-custom alert-custom-success mb-4"><?php echo $msg; ?></div>
        <?php endif; ?>

        <h3 class="section-header">📋 Submitted Claims Log</h3>

        <?php if ($claims_result && $claims_result->num_rows > 0): ?>
            <?php while ($row = $claims_result->fetch_assoc()): ?>
                <div class="admin-card animate-fade-up" style="border-left-color: <?php
                    $s = $row['status'];
                    if ($s == 'pending') echo 'var(--warning)';
                    elseif ($s == 'verified') echo 'var(--success)';
                    elseif ($s == 'returned') echo 'var(--secondary)';
                    else echo 'var(--danger)';
                ?>;">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                                <span style="font-size:13px; font-weight:700; color:var(--text-primary);">Claim #<?php echo $row['claim_id']; ?></span>
                                <span class="status-badge <?php
                                    if ($s == 'pending') echo 'status-badge-pending';
                                    elseif ($s == 'verified') echo 'status-badge-verified';
                                    elseif ($s == 'returned') echo 'status-badge-returned';
                                    else echo 'status-badge-rejected';
                                ?>"><?php echo $s; ?></span>
                                <span style="font-size:12px; color:var(--text-muted);">Filed: <?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></span>
                            </div>

                            <div style="font-size:13px; color:var(--text-secondary); margin-bottom:14px;">
                                <strong>Claimant:</strong> <?php echo htmlspecialchars($row['owner_name']); ?>
                                (<span style="color:var(--primary-light);"><?php echo htmlspecialchars($row['owner_email']); ?></span>)
                            </div>

                            <!-- Match Details Row -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border); border-radius:var(--r-sm); padding:10px;">
                                        <small style="color:var(--text-muted); font-size:10px; font-weight:600; text-transform:uppercase;">Lost Report Description</small>
                                        <div style="font-weight:700; font-size:13px; color:var(--text-primary); margin-top:2px;"><?php echo htmlspecialchars($row['lost_item']); ?></div>
                                        <div style="font-size:12px; color:var(--text-muted); margin-top:2px;"><?php echo htmlspecialchars($row['lost_desc']); ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border); border-radius:var(--r-sm); padding:10px;">
                                        <small style="color:var(--text-muted); font-size:10px; font-weight:600; text-transform:uppercase;">Found Report Description</small>
                                        <div style="font-weight:700; font-size:13px; color:var(--text-primary); margin-top:2px;"><?php echo htmlspecialchars($row['found_item']); ?></div>
                                        <div style="font-size:12px; color:var(--text-muted); margin-top:2px;"><?php echo htmlspecialchars($row['found_desc']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Proof Details -->
                            <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:var(--r-md); padding:14px; font-size:13px;">
                                <strong style="color:var(--primary-light); font-size:11px; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:6px;">Submitted Ownership Proof</strong>
                                <p style="margin:0 0 10px; line-height:1.5; color:var(--text-secondary);"><?php echo htmlspecialchars($row['proof_description']); ?></p>

                                <?php if ($row['proof_url']): ?>
                                    <a href="../<?php echo htmlspecialchars($row['proof_url']); ?>" target="_blank"
                                       class="btn-custom btn-custom-secondary" style="padding:6px 12px; font-size:12px; display:inline-flex; gap:6px;">
                                        📁 View Proof Document
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Action Column -->
                        <div class="col-lg-4 d-flex flex-column justify-content-center" style="border-left: 1px solid var(--border); padding-left:24px;">
                            <?php if (!$is_admin): ?>
                                <div style="text-align:center; color:var(--text-muted); font-size:12px;">
                                    🔒 Enable Admin Mode to take action
                                </div>
                            <?php else: ?>
                                <?php if ($s == 'pending'): ?>
                                    <form method="POST" style="margin:0; display:flex; flex-direction:column; gap:8px;">
                                        <input type="hidden" name="claim_id" value="<?php echo $row['claim_id']; ?>">
                                        <button type="submit" name="claim_action" value="verify" class="btn-custom btn-custom-success w-100" style="font-size:13px; padding:10px;">
                                            ✅ Verify &amp; Approve Claim
                                        </button>
                                        <button type="submit" name="claim_action" value="reject" class="btn-custom btn-custom-danger w-100" style="font-size:13px; padding:10px;">
                                            ❌ Reject Claim
                                        </button>
                                    </form>
                                <?php elseif ($s == 'verified'): ?>
                                    <a href="return_item.php?claim_id=<?php echo $row['claim_id']; ?>"
                                       class="btn-custom btn-custom-primary w-100" style="font-size:13px; padding:10px; display:flex; justify-content:center; align-items:center;">
                                        🤝 Log Handover (Return Item)
                                    </a>
                                <?php elseif ($s == 'returned'): ?>
                                    <div style="text-align:center; color:var(--success); font-weight:700; font-size:13px;">
                                        🎉 Case Resolved &amp; Returned
                                    </div>
                                <?php else: ?>
                                    <div style="text-align:center; color:var(--text-muted); font-size:13px;">
                                        Claim Rejected
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="glass-card text-center" style="padding:60px 20px;">
                <span style="font-size:48px; display:block; margin-bottom:16px;">👮</span>
                <h3 style="font-weight:700; margin-bottom:8px;">No Claims Logged</h3>
                <p style="color:var(--text-muted); font-size:14px;">No ownership claims have been submitted by users yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <footer class="custom-footer mt-5"><p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming</p></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
