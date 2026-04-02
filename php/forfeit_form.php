<?php
require_once '../includes/init.php';
require_once '../includes/db.php';

// Check authentication
if (!isset($_SESSION['applicant_id'])) {
    header('Location: applicantlogin.php');
    exit;
}

$applicant_id = $_SESSION['applicant_id'];

// Check if applicant profile is complete
$stmt = $conn->prepare("SELECT name, email, contact, role, photo FROM applicants WHERE applicant_id = ?");
$stmt->bind_param("s", $applicant_id);
$stmt->execute();
$profile_check = $stmt->get_result()->fetch_assoc();

if (empty($profile_check['name']) || empty($profile_check['email']) || empty($profile_check['contact'])) {
    header('Location: applicant_profile.php?redirect=forfeit_form.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: applicantlogin.php');
    exit;
}

// Get application ID from query parameter
$application_id = isset($_GET['app_id']) ? strtoupper(trim($_GET['app_id'])) : null;

if (!$application_id || !preg_match('/^AP\d+$/', $application_id)) {
    $_SESSION['flash_error'] = 'Invalid application ID.';
    header('Location: applicants.php');
    exit;
}

// Fetch the application and verify it belongs to this applicant
$app_stmt = $conn->prepare("SELECT application_id, applicant_id, category, house_no, date, status FROM applications WHERE application_id = ? AND applicant_id = ?");
$app_stmt->bind_param("ss", $application_id, $applicant_id);
$app_stmt->execute();
$application = $app_stmt->get_result()->fetch_assoc();

if (!$application) {
    $_SESSION['flash_error'] = 'Application not found.';
    header('Location: applicants.php');
    exit;
}

// Current page detection for sidebar
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Forfeit Application | JKUAT Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', 'Inter', 'Roboto', Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
        }
        .sidebar {
            width: 220px;
            background-color: #004225;
            color: white;
            height: 100vh;
            position: fixed;
            padding: 20px 0;
            overflow-y: auto;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 22px;
            font-weight: bold;
        }
        .sidebar a {
            display: block;
            padding: 14px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
            margin: 10px 0;
            border-radius: 4px;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background-color: #006400;
        }
        .main-content {
            margin-left: 220px;
            padding: 40px;
            width: calc(100% - 220px);
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .top-bar h1 {
            margin: 0;
            font-size: 24px;
            color: #004225;
        }
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        .profile-btn {
            background: #004225;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .profile-btn:hover {
            background: #006400;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 180px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            z-index: 1000;
        }
        .dropdown-menu.active {
            display: block;
        }
        .dropdown-menu a,
        .dropdown-menu button {
            display: block;
            width: 100%;
            padding: 12px 16px;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            border-bottom: 1px solid #eee;
        }
        .dropdown-menu a:last-child,
        .dropdown-menu button:last-child {
            border-bottom: none;
        }
        .dropdown-menu a:hover,
        .dropdown-menu button:hover {
            background: #f5f5f5;
        }
        .header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .application-details {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #333;
            min-width: 150px;
        }
        .detail-value {
            color: #666;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group textarea,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group textarea:focus,
        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #004225;
            box-shadow: 0 0 0 2px rgba(0, 66, 37, 0.1);
        }
        .file-help {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        .btn-primary {
            background: #004225;
            color: white;
        }
        .btn-primary:hover {
            background: #006400;
        }
        .btn-secondary {
            background: #ddd;
            color: #333;
        }
        .btn-secondary:hover {
            background: #bbb;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .hamburger {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 10px 0;
            }
            .sidebar h2 {
                margin-bottom: 10px;
            }
            .sidebar a {
                display: none;
                padding: 12px 20px;
                margin: 0;
            }
            .sidebar.active a {
                display: block;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .hamburger {
                display: block;
                position: absolute;
                top: 15px;
                right: 20px;
            }
            .button-group {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="../css/global.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <h2>Applicant Portal</h2>
        <a href="applicants.php" class="<?= $current === 'applicants.php' ? 'active' : '' ?>">Apply</a>
        <a href="ballot.php" class="<?= $current === 'ballot.php' ? 'active' : '' ?>">Balloting</a>
        <a href="notifications.php" class="<?= $current === 'notifications.php' ? 'active' : '' ?>">Notifications</a>
        <a href="my_notices.php" class="<?= $current === 'my_notices.php' ? 'active' : '' ?>">Notices</a>
        <a href="my_bills.php" class="<?= $current === 'my_bills.php' ? 'active' : '' ?>">Bills</a>
        <a href="my_service_requests.php" class="<?= $current === 'my_service_requests.php' ? 'active' : '' ?>">Service Requests</a>
        <a href="my_tenant.php" class="<?= $current === 'my_tenant.php' ? 'active' : '' ?>">House</a>
        <a href="applicant_profile.php" class="<?= $current === 'applicant_profile.php' ? 'active' : '' ?>">Profile</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1>JKUAT STAFF HOUSING PORTAL</h1>
            <button class="hamburger" id="hamburger">☰</button>
            <div class="profile-dropdown">
                <button class="profile-btn" id="profileBtn">
                    👤 <?= htmlspecialchars($profile_check['role'] ?? 'User') ?>
                </button>
                <div class="dropdown-menu" id="dropdownMenu">
                    <a href="applicant_profile.php">Profile</a>
                    <button onclick="logout()">Logout</button>
                </div>
            </div>
        </div>

        <!-- Page Header -->
        <div class="header">Forfeit Application</div>

        <!-- Content -->
        <div class="container">
            <div class="alert alert-info">
                Please provide details about your forfeit request. The CS Admin will review and approve or reject your request.
            </div>

            <!-- Application Details -->
            <div class="application-details">
                <h3 style="margin-top: 0; color: #004225;">Application Details</h3>
                <div class="detail-row">
                    <div class="detail-label">Application ID:</div>
                    <div class="detail-value"><?= htmlspecialchars($application['application_id']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Category:</div>
                    <div class="detail-value"><?= htmlspecialchars($application['category']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">House No:</div>
                    <div class="detail-value"><?= !empty($application['house_no']) ? htmlspecialchars($application['house_no']) : '-' ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date Applied:</div>
                    <div class="detail-value"><?= htmlspecialchars(date('Y-m-d', strtotime($application['date']))) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value"><?= htmlspecialchars(ucfirst($application['status'])) ?></div>
                </div>
            </div>

            <!-- Forfeit Form -->
            <form id="forfeitForm" method="POST" enctype="multipart/form-data" onsubmit="return submitForfeitForm(event)">
                <input type="hidden" name="application_id" value="<?= htmlspecialchars($application_id) ?>">
                <input type="hidden" id="appCategory" value="<?= htmlspecialchars($application['category']) ?>">
                <input type="hidden" id="appId" value="<?= htmlspecialchars($application['application_id']) ?>">

                <div class="form-group">
                    <label for="reason">Reason for Forfeiting *</label>
                    <textarea
                        id="reason"
                        name="reason"
                        placeholder="Please provide a brief reason for forfeiting this application..."
                        required></textarea>
                    <div class="file-help">Maximum 1000 characters</div>
                </div>

                <div class="form-group">
                    <label for="attachment">Supporting Attachment (Optional)</label>
                    <input
                        type="file"
                        id="attachment"
                        name="attachment"
                        accept=".jpg,.jpeg,.png,.pdf">
                    <div class="file-help">Accepted formats: JPG, PNG, PDF. Maximum size: 3MB</div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Submit Forfeit Request</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='applicants.php'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Hamburger menu toggle
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        if (hamburger) {
            hamburger.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }

        // Profile dropdown toggle
        const profileBtn = document.getElementById('profileBtn');
        const dropdownMenu = document.getElementById('dropdownMenu');
        if (profileBtn && dropdownMenu) {
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('active');
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.profile-dropdown')) {
                    dropdownMenu.classList.remove('active');
                }
            });
        }

        function logout() {
            window.location.href = '<?= htmlspecialchars("applicants.php?logout=1") ?>';
        }

        // Form submission
        function submitForfeitForm(event) {
            event.preventDefault();

            const applicationId = document.querySelector('input[name="application_id"]').value;
            const reason = document.getElementById('reason').value.trim();
            const attachment = document.getElementById('attachment').files[0];

            // Validation
            if (!reason) {
                alert('Please provide a reason for forfeiting.');
                return false;
            }

            if (reason.length > 1000) {
                alert('Reason must not exceed 1000 characters.');
                return false;
            }

            if (attachment && attachment.size > 3 * 1024 * 1024) {
                alert('File size must not exceed 3MB.');
                return false;
            }

            // CONFIRMATION DIALOG: Prevent accidental submission
            const category = document.getElementById('appCategory').value;
            const appId = document.getElementById('appId').value;
            const confirmMessage = '⚠️ WARNING: Forfeit Application\n\n' + 
                                   'Application ID: ' + appId + '\n' +
                                   'Category: ' + category + '\n\n' +
                                   'You will need CS Admin approval before you can reapply for this category.\n\n' +
                                   'Are you sure you want to forfeit this application?';
            
            if (!confirm(confirmMessage)) {
                return false;
            }

            // Create FormData object
            const formData = new FormData();
            formData.append('application_id', applicationId);
            formData.append('reason', reason);
            if (attachment) {
                formData.append('attachment', attachment);
            }

            // Submit to forfeit_application.php
            fetch('forfeit_application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Forfeit request submitted successfully! Request ID: ' + data.request_id);
                    window.location.href = 'applicants.php';
                } else {
                    alert('Error: ' + (data.error || 'Failed to submit forfeit request'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting forfeit request: ' + error.message);
            });

            return false;
        }

        // Character counter for reason
        const reasonTextarea = document.getElementById('reason');
        if (reasonTextarea) {
            reasonTextarea.addEventListener('input', function() {
                if (this.value.length > 1000) {
                    this.value = this.value.substring(0, 1000);
                }
            });
        }
    </script>
</body>
</html>
