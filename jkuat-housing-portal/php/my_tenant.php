<?php
require_once '../includes/init.php';
require_once '../includes/db.php';
// `includes/init.php` already starts the session; avoid calling session_start() again to prevent notices.

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
    header('Location: applicant_profile.php?redirect=my_tenant.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: applicantlogin.php');
    exit;
}

$tenant_id = $_SESSION['tenant_id'] ?? null;
// If tenant_id not present in session, try to resolve from DB by applicant_id so rights apply immediately
if (!$tenant_id && !empty($_SESSION['applicant_id'])) {
    require_once __DIR__ . '/../includes/helpers.php';
    $t = get_tenant_for_applicant($conn, $_SESSION['applicant_id']);
    if ($t) {
        $tenant_id = $t['tenant_id'];
        $_SESSION['tenant_id'] = $tenant_id;
    }
}
if (!$tenant_id) {
    header('Location: applicants.php');
    exit;
}

$q = $conn->prepare("SELECT t.tenant_id, t.house_no, t.move_in_date, t.move_out_date, t.status AS tenant_status, a.pf_no, a.name, a.email, a.photo FROM tenants t JOIN applicants a ON t.applicant_id = a.applicant_id WHERE t.tenant_id = ? LIMIT 1");
$q->bind_param('s', $tenant_id);
$q->execute();
$row = $q->get_result()->fetch_assoc();

// Active page detection
$current = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tenant Info | JKUAT Housing</title>
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

        .header {
            font-size: 24px;
            color: #006400;
            margin-bottom: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .portal-title {
            font-size: 26px;
            color: #004225;
            font-weight: bold;
        }

        .profile-dropdown {
            position: relative;
            display: inline-block;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f7f7f7;
            min-width: 120px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.08);
            border-radius: 5px;
            z-index: 1;
        }

        .dropdown-content a {
            color: #004225;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            font-weight: bold;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .card {
            background: #fff;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            max-width: 900px;
        }

        .avatar {
            width: 96px;
            height: 96px;
            border-radius: 8px;
            background: #eee;
            display: inline-block;
            vertical-align: middle;
            margin-right: 16px;
            object-fit: cover;
        }

        .kv {
            font-weight: 700;
            color: #006400;
            margin-right: 6px;
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 16px 0;
        }

        .info-list {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .info-item {
            padding: 6px 0;
        }

        .status-pill {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #e6f4ea;
            color: #006400;
            font-weight: 600;
        }

        /* Hamburger Menu Styles */
        .hamburger-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 5px;
            background: none;
            border: none;
            padding: 10px;
        }
        .hamburger-menu span {
            width: 25px;
            height: 3px;
            background-color: #004225;
            border-radius: 2px;
            transition: 0.3s;
        }
        .sidebar.active {
            left: 0;
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }
        .sidebar-overlay.active {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -220px;
                z-index: 100;
                transition: left 0.3s ease;
            }
            .hamburger-menu {
                display: flex;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .portal-title {
                font-size: 18px;
            }
            .info-list {
                grid-template-columns: 1fr;
            }
            .meta-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        @media (max-width: 480px) {
            .sidebar {
                width: 200px;
            }
            .top-bar {
                flex-direction: column;
                gap: 10px;
            }
            .portal-title {
                font-size: 16px;
            }
        }
    </style>
    <link rel="stylesheet" href="../css/global.css">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="../images/2logo.png" alt="JKUAT Logo" style="width: 60px; height: auto;">
    </div>
    <h2><strong>Applicant Portal</strong></h2>
    <p style="color: #ccc; font-size: 12px; margin: 10px 0 20px 0;">Navigation</p>
    <a href="applicants.php" class="<?= $current === 'applicants.php' ? 'active' : '' ?>">Apply</a>
    <a href="ballot.php" class="<?= $current === 'ballot.php' ? 'active' : '' ?>">Balloting</a>
    <a href="notifications.php" class="<?= $current === 'notifications.php' ? 'active' : '' ?>">Notifications</a>
    <a href="my_notices.php" class="<?= $current === 'my_notices.php' ? 'active' : '' ?>">Notices</a>
    <a href="my_bills.php" class="<?= $current === 'my_bills.php' ? 'active' : '' ?>">Bills</a>
    <a href="my_service_requests.php" class="<?= $current === 'my_service_requests.php' ? 'active' : '' ?>">Service Requests</a>
    <a href="applicant_profile.php" class="<?= $current === 'applicant_profile.php' ? 'active' : '' ?>">Profile</a>
    <a href="my_tenant.php" class="<?= $current === 'my_tenant.php' ? 'active' : '' ?>">House</a>
</div>

<div class="main-content">

    <div class="top-bar">
        <button class="hamburger-menu" id="hamburgerBtn" onclick="toggleSidebar()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="portal-title">JKUAT STAFF HOUSING PORTAL</div>
        <div class="profile-dropdown">
                <?php
                    $profileSrc = '../images/p-icon.png';
                    if (!empty($profile_check['photo'])) {
                        $profileSrc = '../' . $profile_check['photo'];
                    }
                ?>
                <img src="<?= htmlspecialchars($profileSrc) ?>" class="profile-icon" alt="Profile" onclick="toggleProfileMenu()">
                <div class="dropdown-content" id="profileMenu">
                    <div style="padding:10px 15px; border-bottom:1px solid #eee; font-weight:700; color:#004225;">Role: <?= htmlspecialchars(ucfirst($profile_check['role'] ?? 'applicant')) ?></div>
                    <a href="applicant_profile.php">View Profile</a>
                    <a href="my_tenant.php">House</a>
                    <a href="?logout=1">Logout</a>
                </div>
            </div>
    </div>

    <div class="header">My Housing / Tenant Details</div>

    <?php if (!$row): ?>
        <div class="card">
            <p>No tenant record found.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="meta-row">
                <?php if (!empty($row['photo'])): ?>
                    <img src="../<?= htmlspecialchars($row['photo']) ?>" class="avatar" alt="Profile photo">
                <?php else: ?>
                    <div class="avatar" aria-hidden="true"></div>
                <?php endif; ?>
                <div>
                    <div style="font-size:18px;font-weight:700;color:#123b22"><?= htmlspecialchars($row['name']) ?></div>
                    <div style="margin-top:6px"><span class="kv">PF No:</span> <?= htmlspecialchars($row['pf_no']) ?></div>
                    <div style="margin-top:4px"><span class="kv">Email:</span> <?= htmlspecialchars($row['email']) ?></div>
                </div>
            </div>

            <hr>
            <div class="info-list">
                <div class="info-item"><span class="kv">House No:</span> <?= htmlspecialchars($row['house_no']) ?></div>
                <div class="info-item"><span class="kv">Status:</span> <span class="status-pill"><?= htmlspecialchars($row['tenant_status']) ?></span></div>
                <div class="info-item"><span class="kv">Move In:</span> <?= htmlspecialchars($row['move_in_date']) ?></div>
                <div class="info-item"><span class="kv">Move Out:</span> <?= htmlspecialchars($row['move_out_date']) ?></div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}

function toggleProfileMenu() {
    document.getElementById('profileMenu').style.display = document.getElementById('profileMenu').style.display === 'block' ? 'none' : 'block';
}

window.onclick = function(event) {
    if (!event.target.matches('.profile-icon')) {
        document.getElementById('profileMenu').style.display = 'none';
    }
};

document.getElementById('sidebarOverlay').onclick = function() {
    toggleSidebar();
};
</script>
</body>
</html>
