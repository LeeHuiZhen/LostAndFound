<?php
include '../config.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../tey/login.php");
    exit;
}

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$user_name = $_SESSION["user_name"];
$edit_mode = false;
$item_id = 0;
$item_name = '';
$description = '';
$location = '';
$date = '';
$photo_url = '';

if (isset($_GET['edit']) && $_GET['edit'] == '1' && isset($_GET['id'])) {
    $item_id = intval($_GET['id']);
    $edit_mode = true;
    $sql = "SELECT * FROM found_items WHERE item_id = ? AND user_id = ? LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $item_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $item_name = $row['item_name'];
            $description = $row['description'];
            $location = $row['location_found'];
            $date = $row['date_found'];
            $photo_url = $row['photo_url'];
            if (in_array($row['status'], ['verified', 'returned'])) {
                header("Location: ../syafiqah/matching/dashboard.php");
                exit();
            }
        } else {
            header("Location: ../syafiqah/matching/dashboard.php");
            exit();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found Item – UTM Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background: linear-gradient(rgba(2, 6, 23, 0.45), rgba(15, 23, 42, 0.55)),
                        url('../LostAndFound_dashboard.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        #map { height: 230px; border-radius: var(--radius-sm); border: 1.5px solid #cbd5e1; z-index: 1; margin-top: 10px; }
        .form-section-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
        }
    </style>
</head>
<body>

    <nav class="custom-navbar">
        <a href="../index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="../syafiqah/matching/dashboard.php">📊 Dashboard</a>
            <span>Hi, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 7px 16px; font-size: 12px;">Logout</a>
        </div>
    </nav>

    <div class="header-hero" style="padding: 50px 20px; background: linear-gradient(135deg, #064e3b, #065f46, #047857);">
        <h1>🟢 Report Found Item</h1>
        <p>You found something! Help return it to its owner by reporting it here.</p>
    </div>

    <div class="app-container" style="max-width: 660px;">
        <div class="glass-card">

            <div class="alert-custom alert-custom-success mb-4" style="font-size: 13px;">
                🙏 <strong>Thank you for your honesty!</strong> Your report will be cross-referenced instantly with all lost item reports to find the owner.
            </div>

            <form action="save_report.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" value="found">
                <input type="hidden" name="edit_mode" value="<?php echo $edit_mode ? '1' : '0'; ?>">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">

                <p class="form-section-title">Item Information</p>

                <div class="form-group">
                    <label for="item_name">Item Name *</label>
                    <input type="text" name="item_name" id="item_name" class="form-control" placeholder="e.g., UTM Matric Card, Samsung Galaxy Phone" required value="<?php echo htmlspecialchars($item_name); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea name="description" id="description" class="form-control" rows="4"
                        placeholder="Color, condition, brand, model. You may omit sensitive details (e.g., cash amount) to verify the owner's identity later..." required><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <p class="form-section-title mt-4">Location & Date</p>

                <div class="form-group">
                    <label for="location">Location Found *</label>
                    <input type="text" name="location" id="location" class="form-control" placeholder="e.g., Library Level 2, Block N28 Concourse" required value="<?php echo htmlspecialchars($location); ?>">
                    <div id="map"></div>
                    <small class="text-muted" style="font-size: 11px; display: block; margin-top: 6px;">💡 Click or drag the marker on the map to auto-fill the location field.</small>
                </div>

                <div class="form-group">
                    <label for="date">Date Found *</label>
                    <input type="date" name="date" id="date" class="form-control" required value="<?php echo htmlspecialchars($date); ?>">
                </div>

                <p class="form-section-title mt-4">Photo</p>

                <div class="form-group mb-4">
                    <label for="item_photo">Upload Item Photo <?php echo $edit_mode ? '(leave blank to keep existing photo)' : '*'; ?></label>
                    <input type="file" name="item_photo" id="item_photo" class="form-control" accept="image/*" <?php echo $edit_mode ? '' : 'required'; ?>>
                    <small class="text-muted" style="font-size: 11px; display: block; margin-top: 6px;">📷 <?php echo $edit_mode ? 'Upload a new photo only if you want to replace the current one.' : 'Upload a clear picture. Our AI system will analyze it to auto-tag the item. Max: 5MB.'; ?></small>
                </div>

                <div class="d-flex gap-3">
                    <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-secondary flex-fill text-center">Cancel</a>
                    <button type="submit" class="btn-custom btn-custom-success flex-fill"><?php echo $edit_mode ? 'Update Report →' : 'Submit Found Report →'; ?></button>
                </div>
            </form>
        </div>

        <div class="text-center mt-4">
            <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline py-2 px-5">← Dashboard</a>
        </div>
    </div>

    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const utmLat = 1.5615, utmLng = 103.6393;
        const map = L.map('map').setView([utmLat, utmLng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        const marker = L.marker([utmLat, utmLng], { draggable: true }).addTo(map);

        function updateLocation(lat, lng) {
            const input = document.getElementById('location');
            input.placeholder = 'Geocoding...';
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                headers: { 'User-Agent': 'UTMLostAndFoundAssistant/1.0' }
            }).then(r => r.json()).then(data => {
                const a = data.address || {};
                const loc = a.amenity || a.building || a.university || a.road || a.suburb || 'UTM Area';
                input.value = `${loc} (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
            }).catch(() => {
                input.value = `UTM Campus (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
            });
        }

        marker.on('dragend', e => { const p = marker.getLatLng(); updateLocation(p.lat, p.lng); });
        map.on('click', e => { marker.setLatLng(e.latlng); updateLocation(e.latlng.lat, e.latlng.lng); });
    </script>
    <script src="../assets/js/assistant.js"></script>
</body>
</html>
