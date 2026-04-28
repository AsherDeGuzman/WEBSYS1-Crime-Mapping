
<?php
    require_once __DIR__ . '/../public/conn.php';
    require_once __DIR__ . '/../public/oop.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if barangay user is logged in
    if (!isset($_SESSION['Username']) || !isset($_SESSION['Password'])) {
        header("Location: public/login.php");
        exit;
    }

    $oop = new Crime_Mapping(new Connection());

    $conn = $oop->getConnection();


    $stmt = $conn->prepare("SELECT u.*, b.barangay_name 
        FROM users u 
        LEFT JOIN barangays b ON u.barangay_id = b.barangay_id 
        WHERE u.username = ? AND u.password = ? AND u.role = 'barangay'");

    $stmt->execute([$_SESSION['Username'], $_SESSION['Password']]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: public/login.php");
        exit;
    }

    // Handle AJAX form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_submit'])) {
        header('Content-Type: application/json');

        try {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $latitude = floatval($_POST['latitude']);
            $longitude = floatval($_POST['longitude']);

            // Validation
            if (empty($title) || empty($description)) {
                throw new Exception('Title and description are required');
            }

            if ($latitude == 0 || $longitude == 0) {
                throw new Exception('Please select a location on the map');
            }

            // Insert incident
            $stmt = $conn->prepare("INSERT INTO incidents (title, description, barangay_id, latitude, longitude, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$title, $description, $user['barangay_id'], $latitude, $longitude]);
            $incident_id = $conn->lastInsertId();

            // Handle image uploads
            $uploaded_images = [];
            if (isset($_FILES['images'])) {
                $upload_dir = __DIR__ . '/../uploads/incidents/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['images']['name'][$key];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                        // Validate file type
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                        if (!in_array($file_ext, $allowed_exts)) {
                            continue; // Skip invalid files
                        }

                        // Generate unique filename
                        $new_filename = uniqid('incident_' . $incident_id . '_') . '.' . $file_ext;
                        $file_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($tmp_name, $file_path)) {
                            // Insert into attachments table
                            $stmt = $conn->prepare("INSERT INTO attachments (incident_id, file_path) VALUES (?, ?)");
                            $stmt->execute([$incident_id, 'uploads/incidents/' . $new_filename]);
                            $uploaded_images[] = 'uploads/incidents/' . $new_filename;
                        }
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Report submitted successfully! It will be reviewed by an admin before being published.',
                'incident_id' => $incident_id,
                'images' => $uploaded_images
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Get crime types for dropdown
    $crime_types = $oop->getCrimeTypes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Crime Report - <?php echo htmlspecialchars($user['barangay_name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        :root {
            color-scheme: light;
            font-family: 'Inter', system-ui, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --border: #e5e7eb;
            --shadow: 0 18px 50px rgba(15, 23, 42, 0.06);
            --accent: #111827;
            --blue: #2563eb;
            --green: #16a34a;
            --yellow: #f59e0b;
            --red: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        }

        .navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 64px;
        }

        .navbar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--accent);
            text-decoration: none;
            margin: 0;
        }

        .navbar-nav {
            display: flex;
            gap: 24px;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .navbar-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        .navbar-link:hover {
            color: var(--blue);
        }

        .navbar-user {
            background: var(--blue);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .page-shell {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
        }

        .page-title {
            margin: 0 0 8px;
            font-size: clamp(1.8rem, 2.2vw, 2.6rem);
            letter-spacing: -0.03em;
            color: var(--accent);
        }

        .page-subtitle {
            margin: 0 0 24px;
            color: #4b5563;
            font-size: 1rem;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 32px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-weight: 600;
            color: var(--accent);
            font-size: 0.95rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 0.95rem;
            background: var(--surface-soft);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .map-container {
            border: 1px solid var(--border);
            border-radius: 20px;
            background: linear-gradient(180deg, #f9fafb 0%, #eef2ff 100%);
            box-shadow: var(--shadow);
            padding: 0;
            margin-bottom: 24px;
            overflow: hidden;
        }

        #crime-map {
            width: 100%;
            height: 400px;
        }

        .map-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--blue);
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--green);
            color: #ffffff;
        }

        .btn-success:hover {
            background: #15803d;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--accent);
        }

        .btn-outline:hover {
            background: var(--surface-soft);
        }

        .file-upload {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            background: var(--surface-soft);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: var(--blue);
            background: rgba(37, 99, 235, 0.02);
        }

        .file-upload.dragover {
            border-color: var(--green);
            background: rgba(22, 163, 74, 0.02);
        }

        .file-input {
            display: none;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: var(--surface);
            border: 1px solid var(--border);
        }

        .preview-img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            display: block;
        }

        .upload-progress {
            margin-top: 16px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--surface-soft);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-fill {
            height: 100%;
            background: var(--blue);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(22, 163, 74, 0.1);
            border: 1px solid rgba(22, 163, 74, 0.2);
            color: #15803d;
        }

        .alert-error {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.2);
            color: #dc2626;
        }

        .alert-info {
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(37, 99, 235, 0.2);
            color: #2563eb;
        }

        .location-display {
            background: var(--surface-soft);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .page-shell {
                padding: 16px;
            }

            .panel {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            #crime-map {
                height: 300px;
            }

            .map-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 0 16px;
            }

            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <h1 class="navbar-brand">Crime Map</h1>
            <ul class="navbar-nav">
                <li><a href="../public/map.php" class="navbar-link">Map</a></li>
                <li><a href="../public/map.php" class="navbar-link">Logout</a></li>
                <li><span class="navbar-user"><?php echo htmlspecialchars($user['barangay_name']); ?> Officer</span></li>
            </ul>
        </div>
    </nav>

    <div class="page-shell">
        <h1 class="page-title">Submit Crime Report</h1>
        <p class="page-subtitle">Report a crime incident in <?php echo htmlspecialchars($user['barangay_name']); ?>. All reports will be reviewed by administrators before publication.</p>

        <div id="alert-container"></div>

        <form id="report-form" class="panel" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Crime Type <span style="color: var(--red);">*</span></label>
                    <select class="form-select" name="title" id="title" required>
                        <option value="">Select crime type...</option>
                        <?php foreach ($crime_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date & Time of Incident</label>
                    <input type="datetime-local" class="form-input" name="incident_datetime" id="incident_datetime">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description <span style="color: var(--red);">*</span></label>
                <textarea class="form-textarea" name="description" id="description" placeholder="Provide detailed description of the incident..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Location <span style="color: var(--red);">*</span></label>
                <div class="map-actions">
                    <button type="button" class="btn btn-success" id="get-location">
                        📍 Get My Location
                    </button>
                    <button type="button" class="btn btn-outline" id="reset-location">
                        🔄 Reset Location
                    </button>
                </div>
                <div class="map-container">
                    <div id="crime-map"></div>
                </div>
                <div class="location-display" id="location-display">
                    Click on the map or use "Get My Location" to set the incident location
                </div>
                <input type="hidden" name="latitude" id="latitude" value="0">
                <input type="hidden" name="longitude" id="longitude" value="0">
            </div>

            <div class="form-group">
                <label class="form-label">Upload Images (Optional)</label>
                <div class="file-upload" id="file-upload">
                    <div>
                        📷 Drop images here or click to browse<br>
                        <small style="color: #6b7280;">Maximum 3 images, up to 5MB each</small>
                    </div>
                    <input type="file" class="file-input" id="images" name="images[]" multiple accept="image/*">
                </div>
                <div class="preview-grid" id="preview-grid"></div>
            </div>

            <div class="upload-progress" id="upload-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
                </div>
                <small id="progress-text">Uploading images...</small>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px;">
                <button type="button" class="btn btn-outline" onclick="window.location.href='../map.php'">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submit-btn">
                    📤 Submit Report
                </button>
            </div>
        </form>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // Initialize map
        const map = L.map('crime-map', {
            center: [16.4023, 120.5960], // Baguio City center
            zoom: 14,
            scrollWheelZoom: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        let marker = null;

        // Function to update marker position
        function updateMarker(lat, lng) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lng]).addTo(map);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            document.getElementById('location-display').textContent = `Location: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }

        // Map click handler
        map.on('click', function(e) {
            updateMarker(e.latlng.lat, e.latlng.lng);
        });

        // Get current location
        document.getElementById('get-location').addEventListener('click', function() {
            if (!navigator.geolocation) {
                showAlert('Geolocation is not supported by your browser', 'error');
                return;
            }

            this.disabled = true;
            this.textContent = '📍 Getting location...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    map.setView([lat, lng], 18);
                    updateMarker(lat, lng);
                    this.disabled = false;
                    this.textContent = '📍 Get My Location';
                    showAlert('Location found and marked on map!', 'success');
                },
                (error) => {
                    this.disabled = false;
                    this.textContent = '📍 Get My Location';
                    let message = 'Unable to get your location';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            message = 'Location permission denied. Please enable location access.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message = 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            message = 'Location request timed out.';
                            break;
                    }
                    showAlert(message, 'error');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });

        // Reset location
        document.getElementById('reset-location').addEventListener('click', function() {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            document.getElementById('latitude').value = '0';
            document.getElementById('longitude').value = '0';
            document.getElementById('location-display').textContent = 'Click on the map or use "Get My Location" to set the incident location';
        });

        // File upload handling
        const fileUpload = document.getElementById('file-upload');
        const fileInput = document.getElementById('images');
        const previewGrid = document.getElementById('preview-grid');

        fileUpload.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', handleFileSelect);
        fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });
        fileUpload.addEventListener('dragleave', () => {
            fileUpload.classList.remove('dragover');
        });
        fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });

        function handleFileSelect(e) {
            const files = Array.from(e.target.files);
            handleFiles(files);
        }

        function handleFiles(files) {
            // Filter for images only and limit to 3
            const imageFiles = files.filter(file => file.type.startsWith('image/')).slice(0, 3);

            if (imageFiles.length === 0) {
                showAlert('Please select valid image files', 'error');
                return;
            }

            // Clear existing previews
            previewGrid.innerHTML = '';

            // Create previews
            imageFiles.forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `<img src="${e.target.result}" class="preview-img" alt="Preview">`;
                    previewGrid.appendChild(div);
                };
                reader.readAsDataURL(file);
            });

            // Update file input
            const dt = new DataTransfer();
            imageFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }

        // Form submission
        document.getElementById('report-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submit-btn');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '📤 Submitting...';

            try {
                const formData = new FormData(this);
                formData.append('ajax_submit', '1');

                // Show progress for image uploads
                const images = fileInput.files;
                if (images.length > 0) {
                    document.getElementById('upload-progress').style.display = 'block';
                    document.getElementById('progress-fill').style.width = '0%';
                    document.getElementById('progress-text').textContent = 'Preparing images...';
                }

                const response = await fetch('submit-report.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                    // Reset form
                    this.reset();
                    if (marker) {
                        map.removeLayer(marker);
                        marker = null;
                    }
                    document.getElementById('latitude').value = '0';
                    document.getElementById('longitude').value = '0';
                    document.getElementById('location-display').textContent = 'Click on the map or use "Get My Location" to set the incident location';
                    previewGrid.innerHTML = '';
                    document.getElementById('upload-progress').style.display = 'none';
                } else {
                    showAlert(result.message, 'error');
                }

            } catch (error) {
                showAlert('An error occurred while submitting the report. Please try again.', 'error');
                console.error('Submit error:', error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                document.getElementById('upload-progress').style.display = 'none';
            }
        });

        // Alert function
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-error' : 'alert-info';
            const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';

            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    <span>${icon}</span>
                    <span>${message}</span>
                </div>
            `;

            // Auto-hide success alerts after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    alertContainer.innerHTML = '';
                }, 5000);
            }
        }

        // Initialize map size
        setTimeout(() => {
            map.invalidateSize();
        }, 100);
    </script>
</body>
</html>
</content>