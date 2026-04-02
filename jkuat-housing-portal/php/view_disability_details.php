<?php
/**
 * php/view_disability_details.php
 * Purpose: Admin-only page to view applicant disability details (read-only)
 * Author: repo automation
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_admin()) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$current = basename($_SERVER['PHP_SELF']);

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: manage_applicants.php');
    exit;
}

// Fetch applicant with all available data using direct query
$id_escaped = $conn->real_escape_string($id);
$result = $conn->query("SELECT * FROM applicants WHERE applicant_id = '" . $id_escaped . "' LIMIT 1");
$app = $result ? $result->fetch_assoc() : null;
if (!$app) {
    header('Location: manage_applicants.php');
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Disability Details</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #f4f4f4; }
        .sidebar-overlay { display: none; }
        .sidebar {
            width: 220px;
            background: #004225;
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            padding: 20px 0;
            overflow-y: auto;
        }
        .sidebar img { display: block; width: 74px; margin: 0 auto 12px; }
        .sidebar h2 { margin: 0; padding: 0 20px; font-size: 20px; }
        .sidebar p { margin: 10px 0 18px; padding: 0 20px; font-size: 13px; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar a {
            display: block;
            color: #fff;
            text-decoration: none;
            padding: 14px 20px;
            font-weight: 600;
        }
        .sidebar a:hover,
        .sidebar a.active { background: rgba(255,255,255,0.18); }
        .main-content { margin-left: 220px; padding: 24px; }
        .top-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: #fff;
            padding: 18px 22px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .top-header h1 { margin: 0; color: #006400; font-size: 24px; }
        .user-icon { width: 40px; height: 40px; border-radius: 50%; cursor: pointer; }
        .hamburger-menu {
            display: none;
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 4px;
            background: #004225;
            padding: 9px 8px;
            cursor: pointer;
        }
        .hamburger-menu span {
            display: block;
            height: 3px;
            background: #fff;
            margin: 5px 0;
            border-radius: 2px;
        }
        .container {
            max-width: 760px;
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            margin-top: 0;
            border-bottom: 2px solid #006400;
            padding-bottom: 12px;
        }
        .info-row {
            margin-bottom: 20px;
        }
        .label {
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
            display: block;
        }
        .value {
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            color: #333;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
            margin-top: 6px;
        }
        .status-yes {
            background-color: #fff3cd;
            color: #996c00;
        }
        .status-no {
            background-color: #ccffcc;
            color: #4CAF50;
        }
        .button-group {
            margin-top: 24px;
            text-align: center;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-back {
            background-color: #006400;
            color: white;
            margin-left: 8px;
        }
        .btn-back:hover {
            background-color: #004d00;
        }
        @media (max-width: 768px) {
            .sidebar-overlay.active {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.45);
                z-index: 999;
            }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s ease;
                z-index: 1000;
            }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .hamburger-menu { display: inline-block; }
            .top-header h1 { font-size: 18px; }
        }
    </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <img src="../images/2logo.png" alt="Logo">
    <h2>CS ADMIN</h2>
    <p>MANAGE APPLICANTS</p>
    <nav>
        <ul>
            <li><a href="csdashboard.php" class="<?= $current === 'csdashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="houses.php" class="<?= $current === 'houses.php' ? 'active' : '' ?>">Houses</a></li>
            <li><a href="tenants.php" class="<?= $current === 'tenants.php' ? 'active' : '' ?>">Tenants</a></li>
            <li><a href="service_requests.php" class="<?= $current === 'service_requests.php' ? 'active' : '' ?>">Service Requests</a></li>
            <li><a href="manage_applicants.php" class="active">Manage Applicants</a></li>
            <li><a href="notices.php" class="<?= $current === 'notices.php' ? 'active' : '' ?>">Notices</a></li>
            <li><a href="bills.php" class="<?= $current === 'bills.php' ? 'active' : '' ?>">Bills</a></li>
            <li><a href="reports.php" class="<?= $current === 'reports.php' ? 'active' : '' ?>">Reports</a></li>
        </ul>
    </nav>
</aside>
<main class="main-content">
    <header class="top-header">
        <button class="hamburger-menu" id="hamburgerBtn" type="button" onclick="toggleSidebar()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <h1>JKUAT STAFF HOUSING PORTAL</h1>
        <img src="../images/p-icon.png" alt="User Icon" class="user-icon" onclick="window.location.href='?logout=1'">
    </header>

    <div class="container">
        <h2>Disability Details - <?= htmlspecialchars($app['applicant_id']) ?></h2>

        <div class="info-row">
            <label class="label">Applicant Name</label>
            <div class="value"><?= htmlspecialchars($app['name'] ?? 'N/A') ?></div>
        </div>

        <div class="info-row">
            <label class="label">Has Disability</label>
            <div>
                <?php if (!empty($app['is_disabled']) && intval($app['is_disabled']) === 1): ?>
                    <span class="status-badge status-yes">Yes</span>
                <?php else: ?>
                    <span class="status-badge status-no">No</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($app['is_disabled']) && intval($app['is_disabled']) === 1): ?>
            <div class="info-row">
                <label class="label">Disability Details</label>
                <div class="value">
                    <?php 
                        $detailsRaw = isset($app['disability_details']) ? $app['disability_details'] : '';
                        $details = trim((string)$detailsRaw);
                        echo ($details !== '' && $details !== null) ? htmlspecialchars($details) : '<em>No details provided</em>';
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="button-group">
            <a href="manage_applicants.php" class="btn btn-back">Back to Manage Applicants</a>
        </div>
    </div>
</main>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}

document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);
</script>
</body>
</html>
