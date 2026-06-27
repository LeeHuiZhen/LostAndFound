<?php
include '../config.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../tey/login.php");
    exit;
}

$user_name = $_SESSION["user_name"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found Item - Lost & Found Assistant</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Leaflet Map CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background-color: var(--light-bg);
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="../index.php" class="brand">
            🔍 UTM Lost & Found
        </a>
        <div class="nav-links">
            <a href="../syafiqah/matching/dashboard.php" style="color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; margin-right: 15px;">📋 Dashboard</a>
            <span style="font-size: 14px; font-weight: 500; color: var(--text-muted); margin-right: 15px;">
                Welcome, <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($user_name); ?></strong>
            </span>
            <a href="../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 6px 16px; font-size: 12px;">🚪 Logout</a>
        </div>
    </nav>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="app-container" style="max-width: 650px;">
        <div class="glass-card">
            <div class="text-center mb-4">
                <span style="font-size: 44px;">🤝</span>
                <h2 class="mt-2" style="border-bottom: none; padding-bottom: 0;">Report Found Item</h2>
                <p class="text-muted" style="font-size: 13px;">Enter details of the item you found on campus to help its owner find it</p>
            </div>

            <form action="save_report.php" method="POST" enctype="multipart/form-data">
                <!-- Hidden type set to found -->
                <input type="hidden" name="type" value="found">

                <div class="form-group">
                    <label for="item_name">Item Name *</label>
                    <input type="text" name="item_name" id="item_name" class="form-control" placeholder="e.g., Student Card, Sony Wireless Headset" required>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description *</label>
                    <textarea name="description" id="description" class="form-control" rows="4" placeholder="Describe the item, such as color, keychains, physical condition. Feel free to leave out sensitive details (e.g. cash amount) to verify owner claims later..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Location Found *</label>
                    <input type="text" name="location" id="location" class="form-control" placeholder="e.g., near the Library Level 2 elevator, Block N28 concourse" required>
                    <div id="map" style="height: 220px; margin-top: 10px; border-radius: var(--radius-sm); border: 1px solid #cbd5e1; z-index: 1;"></div>
                    <small class="text-muted" style="font-size: 11px;">💡 Click or drag the marker on the map to automatically pinpoint the UTM campus location.</small>
                </div>

                <div class="form-group">
                    <label for="date">Date Found *</label>
                    <input type="date" name="date" id="date" class="form-control" required>
                </div>

                <div class="form-group mb-4">
                    <label for="item_photo">Upload Photo *</label>
                    <input type="file" name="item_photo" id="item_photo" class="form-control" accept="image/*" required>
                    <small class="text-muted" style="font-size: 11px;">Upload a clear picture of the found item. Our tagging system will analyze it (Max: 5MB).</small>
                </div>

                <div class="d-flex gap-3">
                    <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-secondary flex-1">Cancel</a>
                    <button type="submit" class="btn-custom btn-custom-success flex-1">Submit Found Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // UTM Skudai coordinates
        const utmLat = 1.5615;
        const utmLng = 103.6393;
        
        // Initialize map
        const map = L.map('map').setView([utmLat, utmLng], 16);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Add draggable marker
        const marker = L.marker([utmLat, utmLng], {
            draggable: true
        }).addTo(map);
        
        // Function to update input with address using free Nominatim API
        function updateLocationInput(lat, lng) {
            const locationInput = document.getElementById('location');
            locationInput.placeholder = "Geocoding coordinates...";
            
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                headers: {
                    'User-Agent': 'UTMLostAndFoundAssistant/1.0'
                }
            })
            .then(response => response.json())
            .then(data => {
                let locationName = "";
                if (data.address) {
                    locationName = data.address.amenity || data.address.building || data.address.university || data.address.road || data.address.suburb || "UTM Area";
                }
                locationInput.value = `${locationName} (Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)})`;
            })
            .catch(error => {
                locationInput.value = `UTM Campus (Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)})`;
            });
        }
        
        // Trigger on marker drag end
        marker.on('dragend', function (e) {
            const position = marker.getLatLng();
            updateLocationInput(position.lat, position.lng);
        });
        
        // Trigger on map click
        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            updateLocationInput(e.latlng.lat, e.latlng.lng);
        });
    </script>
</body>
</html>