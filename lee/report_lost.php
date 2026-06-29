<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php");
    exit();
}

$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item – UTM Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background: linear-gradient(rgba(15, 23, 42, 0.05), rgba(15, 23, 42, 0.08)),
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

    <div class="header-hero" style="padding: 50px 20px;">
        <h1>🔴 Report Lost Item</h1>
        <p>Describe the item you lost and our system will automatically search for matches.</p>
    </div>

    <div class="app-container" style="max-width: 660px;">
        <div class="glass-card">

            <form action="save_report.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" value="lost">

                <p class="form-section-title">Item Information</p>

                <div class="form-group">
                    <label for="item_name">Item Name *</label>
                    <input type="text" name="item_name" id="item_name" class="form-control" placeholder="e.g., UTM Matric Card, Sony WH-1000XM4 Headphones" required>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea name="description" id="description" class="form-control" rows="4"
                        placeholder="Describe the color, brand, model, unique stickers, keychains, scratches, or any identifying features..." required></textarea>
                </div>

                <p class="form-section-title mt-4">Location & Date</p>

                <div class="form-group">
                    <label for="location">Location Lost *</label>
                    <input type="text" name="location" id="location" class="form-control" placeholder="e.g., UTM Library, Block N28 Level 3" required>
                    <div id="map"></div>
                    <small class="text-muted" style="font-size: 11px; display: block; margin-top: 6px;">💡 Click or drag the marker to auto-fill the location field from the map.</small>
                </div>

                <div class="form-group">
                    <label for="date">Date Lost *</label>
                    <input type="date" name="date" id="date" class="form-control" required>
                </div>

                <p class="form-section-title mt-4">Photo</p>

                <div class="form-group mb-4">
                    <label for="item_photo">Upload Item Photo *</label>
                    <input type="file" name="item_photo" id="item_photo" class="form-control" accept="image/*" required>
                    <small class="text-muted" style="font-size: 11px; display: block; margin-top: 6px;">📷 Provide a clear photo of the item or a similar reference image. Max: 5MB.</small>
                </div>

                <div class="d-flex gap-3">
                    <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-secondary flex-fill text-center">Cancel</a>
                    <button type="submit" class="btn-custom btn-custom-primary flex-fill">Submit Report →</button>
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
