<?php
/**
 * php/edit_applicant.php
 * Purpose: Admin-only page to edit applicant records (name, email, contact, next_of_kin, status, username)
 * Author: repo automation / commit: admin: add edit applicant
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

$error = '';
$success = '';
$id = $_GET['id'] ?? ($_POST['applicant_id'] ?? '');
if (!$id) {
    header('Location: manage_applicants.php');
    exit;
}

// Fetch applicant
$stmt = $conn->prepare('SELECT * FROM applicants WHERE applicant_id = ? LIMIT 1');
$stmt->bind_param('s', $id);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();
if (!$app) {
    header('Location: manage_applicants.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $next_of_kin_name = trim($_POST['next_of_kin_name'] ?? '');
    $next_of_kin_contact = trim($_POST['next_of_kin_contact'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $is_disabled = (trim($_POST['is_disabled'] ?? 'no') === 'yes') ? 1 : 0;
    $disability_details = trim($_POST['disability_details'] ?? '');

    // Basic validation
    if (strlen($name) < 2) $error = 'Name too short';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Invalid email';
    elseif (!preg_match('/^[0-9+()\\s\\-]{6,25}$/', $contact)) $error = 'Invalid contact';
    else {
        // Check if disability columns exist
        require_once __DIR__ . '/../includes/helpers.php';
        $hasDisabilityFields = column_exists_db($conn, 'applicants', 'is_disabled') && column_exists_db($conn, 'applicants', 'disability_details');
        
        if ($hasDisabilityFields) {
            $u = $conn->prepare('UPDATE applicants SET name = ?, email = ?, contact = ?, next_of_kin_name = ?, next_of_kin_contact = ?, username = ?, status = ?, is_disabled = ?, disability_details = ? WHERE applicant_id = ?');
            $u->bind_param('ssssssssss', $name, $email, $contact, $next_of_kin_name, $next_of_kin_contact, $username, $status, $is_disabled, $disability_details, $id);
        } else {
            $u = $conn->prepare('UPDATE applicants SET name = ?, email = ?, contact = ?, next_of_kin_name = ?, next_of_kin_contact = ?, username = ?, status = ? WHERE applicant_id = ?');
            $u->bind_param('ssssssss', $name, $email, $contact, $next_of_kin_name, $next_of_kin_contact, $username, $status, $id);
        }
        if ($u->execute()) {
            $success = 'Applicant updated.';
            // refresh
            $stmt->execute();
            $app = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Update failed: ' . $conn->error;
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Applicant</title>
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
        .card {
            background: #fff;
            padding: 24px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            border-radius: 8px;
        }
        .page-title { margin: 0 0 18px; font-size: 28px; color: #111; }
        .alert { padding: 12px 14px; border-radius: 6px; margin-bottom: 16px; }
        .alert-error { background: #e3f2fd; border: 1px solid #bbdefb; color: #1a237e; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .field-span-2 { grid-column: span 2; }
        label { display: block; font-weight: 600; margin-bottom: 6px; color: #333; }
        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            font: inherit;
        }
        textarea { resize: vertical; min-height: 110px; }
        .radio-group { display: flex; gap: 24px; padding-top: 8px; }
        .radio-group label { font-weight: 500; margin-bottom: 0; }
        .actions { display: flex; gap: 12px; margin-top: 20px; }
        .btn {
            display: inline-block;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 700;
            font: inherit;
        }
        .btn-primary { background: #006400; color: #fff; }
        .btn-secondary { background: #ff9800; color: #111; }
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
            .field-grid { grid-template-columns: 1fr; }
            .field-span-2 { grid-column: span 1; }
            .actions { flex-direction: column; }
        }
    </style>
    <link rel="stylesheet" href="../css/global.css">
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

    <section class="card">
        <h2 class="page-title">Edit Applicant <?= htmlspecialchars($app['applicant_id']) ?></h2>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="post">
            <input type="hidden" name="applicant_id" value="<?= htmlspecialchars($app['applicant_id']) ?>">
            <div class="field-grid">
                <div>
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($app['username'] ?? '') ?>">
                </div>
                <div>
                    <label>Full name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($app['name'] ?? '') ?>">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($app['email'] ?? '') ?>">
                </div>
                <div>
                    <label>Contact</label>
                    <input type="text" name="contact" value="<?= htmlspecialchars($app['contact'] ?? '') ?>">
                </div>
                <div>
                    <label>Next of Kin Name</label>
                    <input type="text" name="next_of_kin_name" value="<?= htmlspecialchars($app['next_of_kin_name'] ?? '') ?>">
                </div>
                <div>
                    <label>Next of Kin Contact</label>
                    <input type="text" name="next_of_kin_contact" value="<?= htmlspecialchars($app['next_of_kin_contact'] ?? '') ?>">
                </div>
                <div class="field-span-2">
                    <label>Status</label>
                    <input type="text" name="status" value="<?= htmlspecialchars($app['status'] ?? '') ?>">
                </div>
                <div class="field-span-2">
                    <label>Has Disability?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="is_disabled" value="no" <?= (empty($app['is_disabled']) || intval($app['is_disabled']) !== 1) ? 'checked' : '' ?>> No</label>
                        <label><input type="radio" name="is_disabled" value="yes" <?= (isset($app['is_disabled']) && intval($app['is_disabled']) === 1) ? 'checked' : '' ?>> Yes</label>
                    </div>
                </div>
                <div id="disability_details_wrap" class="field-span-2" style="<?= (isset($app['is_disabled']) && intval($app['is_disabled']) === 1) ? '' : 'display:none;' ?>">
                    <label for="disability_details">Disability Details</label>
                    <textarea id="disability_details" name="disability_details" rows="4" placeholder="Describe any disability and support needs"><?= htmlspecialchars($app['disability_details'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="manage_applicants.php" class="btn btn-secondary">Back to Manage Applicants</a>
            </div>
        </form>
    </section>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var disabilityRadios = document.querySelectorAll('input[name="is_disabled"]');
    var detailsWrap = document.getElementById('disability_details_wrap');

    disabilityRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            detailsWrap.style.display = this.value === 'yes' ? 'block' : 'none';
        });
    });
});

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}

document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);
</script>
</body>
</html>
