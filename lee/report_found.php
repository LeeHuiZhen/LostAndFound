<?php
include '../config.php';
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../tey/login.php"); exit;
}
$user_name = $_SESSION["user_name"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found Item — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { background-color: var(--bg-base); }
        #map { height: 240px; margin-top: 10px; border-radius: var(--r-md); border: 1px solid var(--border); z-index: 1; }
    </style>
</head>
<body>
    <nav class="custom-navbar">
        <a href="../index.php" class="brand">🔍 UTM Lost &amp; Found</a>
        <div class="nav-links">
            <a href="../syafiqah/matching/dashboard.php" style="font-size:13px; color:var(--text-muted);">📋 Dashboard</a>
            <span class="nav-user-badge">👤 <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../tey/logout.php" class="btn-custom btn-custom-secondary" style="padding:7px 16px; font-size:12px;">Logout</a>
        </div>
    </nav>

    <div class="header-hero" style="padding:50px 20px;">
        <h1 style="font-size:32px; margin-bottom:8px;">🤝 Report a Found Item</h1>
        <p>You've made someone's day — fill in the details so the rightful owner can find their way back to it.</p>
    </div>

    <div class="app-container" style="max-width:680px;">
        <div class="glass-card animate-fade-up">
            <form action="save_report.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" value="found">

                <div class="form-group">
                    <label for="item_name">Item Name *</label>
                    <input type="text" name="item_name" id="item_name" class="form-control"
                           placeholder="e.g., Student Card, Sony Wireless Headset, Black Wallet" required>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea name="description" id="description" class="form-control" rows="4"
                              placeholder="Describe colour, brand, keychains, condition. You may omit sensitive details (e.g., cash amount) to verify owner claims later..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Location Found *</label>
                    <input type="text" name="location" id="location" class="form-control"
                           placeholder="e.g., Near Library Level 2 elevator, Block N28 concourse" required>
                    <div id="map"></div>
                    <small style="font-size:11px; color:var(--text-muted); margin-top:6px; display:block;">
                        💡 Click or drag the marker to auto-fill the location.
                    </small>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date">Date Found *</label>
                            <input type="date" name="date" id="date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="item_photo">Photo of Item *</label>
                            <input type="file" name="item_photo" id="item_photo" class="form-control" accept="image/*" required>
                            <small style="font-size:11px; color:var(--text-muted); margin-top:4px; display:block;">JPG/PNG/GIF, max 5 MB</small>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:12px; margin-top:8px;">
                    <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-secondary flex-fill">Cancel</a>
                    <button type="submit" class="btn-custom btn-custom-success flex-fill">🟢 Submit Found Report</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="custom-footer mt-5"><p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming</p></footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([1.5615, 103.6393], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
        const marker = L.marker([1.5615, 103.6393], { draggable: true }).addTo(map);
        function revGeocode(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                headers: { 'User-Agent': 'UTMLostAndFoundAssistant/1.0' }
            }).then(r => r.json()).then(d => {
                const loc = d.address;
                const name = loc?.amenity || loc?.building || loc?.university || loc?.road || loc?.suburb || 'UTM Area';
                document.getElementById('location').value = `${name} (${lat.toFixed(5)}, ${lng.toFixed(5)})`;
            }).catch(() => {
                document.getElementById('location').value = `UTM Campus (${lat.toFixed(5)}, ${lng.toFixed(5)})`;
            });
        }
        marker.on('dragend', () => { const { lat, lng } = marker.getLatLng(); revGeocode(lat, lng); });
        map.on('click', e => { marker.setLatLng(e.latlng); revGeocode(e.latlng.lat, e.latlng.lng); });
    </script>
    <script src="../assets/js/assistant.js"></script>
</body>
</html>
