<?php
require_once '../includes/init.php';
require_once '../includes/db.php';

// Only allow logged-in applicants
// `includes/init.php` already starts the session; avoid calling `session_start()` again.
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
    header('Location: applicant_profile.php?redirect=my_service_requests.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: applicantlogin.php');
    exit;
}

// Resolve tenant id from session or DB so tenant features are immediately available
$tenant_id = $_SESSION['tenant_id'] ?? null; // may be set if applicant has tenant record
if (!$tenant_id && !empty($_SESSION['applicant_id'])) {
    require_once __DIR__ . '/../includes/helpers.php';
    $t = get_tenant_for_applicant($conn, $_SESSION['applicant_id']);
    if ($t) { $_SESSION['tenant_id'] = $t['tenant_id']; $tenant_id = $t['tenant_id']; }
}

$error = '';
$success = '';

// If user is not a tenant, they cannot submit service requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_service'])) {
    if (!$tenant_id) {
        $error = 'Only tenants can submit service requests. Contact admin if you are a tenant but cannot see this.';
    } else {
        $type = trim($_POST['type_of_service'] ?? '');
        $details = trim($_POST['details'] ?? '');
        if ($type === '' || $details === '') {
            $error = 'Please provide both service type and details.';
        } else {
            // Generate service id
            $q = $conn->query("SELECT service_id FROM service_requests ORDER BY service_id DESC LIMIT 1");
            $newNum = 1;
            if ($q && $row = $q->fetch_assoc()) {
                $last = preg_replace('/[^0-9]/', '', $row['service_id']);
                $newNum = ((int)$last) + 1;
            }
            $service_id = 'S' . str_pad($newNum, 3, '0', STR_PAD_LEFT);
            $date = date('Y-m-d H:i:s');
            $status = 'pending';
            $bill_amount = 0.00;

            $ins = $conn->prepare("INSERT INTO service_requests (service_id, tenant_id, type_of_service, details, bill_amount, date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($ins === false) {
                logs_write('error', 'my_service_requests: prepare failed: ' . $conn->error);
                $error = 'Server error. Please try again later.';
            } else {
                // types: service_id(s), tenant_id(s), type(s), details(s), bill_amount(d), date(s), status(s)
                $ins->bind_param('ssssdss', $service_id, $tenant_id, $type, $details, $bill_amount, $date, $status);
                if ($ins->execute()) {
                    $success = 'Service request created. Service ID: ' . htmlspecialchars($service_id);
                } else {
                    logs_write('error', 'my_service_requests: execute failed: ' . $ins->error);
                    $error = 'Failed to create service request: ' . $conn->error;
                }
            }
        }
    }
}

// Fetch the applicant's service requests by tenant_id if present
$requests = [];
if ($tenant_id) {
    $stmt = $conn->prepare("SELECT service_id, tenant_id, type_of_service, details, bill_amount, date, status FROM service_requests WHERE tenant_id = ? ORDER BY date DESC");
    $stmt->bind_param('s', $tenant_id);
    $stmt->execute();
    $requests = $stmt->get_result();
}

// Active page detection
$current = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Service Requests | JKUAT Housing</title>
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
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        th, td {
            padding: 12px 14px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #006400;
            color: #fff;
            font-weight: 600;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .btn {
            padding: 10px 14px;
            border-radius: 6px;
            border: none;
            background: #006400;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
        }

        .btn.secondary {
            background: #fff;
            color: #006400;
            border: 1px solid #006400;
        }

        .btn:hover {
            background: #005826;
        }

        .btn.secondary:hover {
            background: #f1f1f1;
        }

        .error {
            background: #fff3f3;
            padding: 12px;
            border-left: 4px solid #bbdefb;
            color: #1a237e;
            margin-bottom: 12px;
            border-radius: 4px;
        }

        .success {
            background: #f3fff5;
            padding: 12px;
            border-left: 4px solid #b6e6c8;
            color: #155724;
            margin-bottom: 12px;
            border-radius: 4px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            max-width: 900px;
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #254b2b;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #d6d6d6;
            font-size: 14px;
            background: #fff;
            font-family: inherit;
        }

        textarea {
            min-height: 44px;
            resize: none;
            overflow: hidden;
        }

        .muted-note {
            color: #6b6b6b;
            font-size: 13px;
            margin-top: 8px;
        }

        .info-text {
            color: #6b6b6b;
            font-size: 14px;
            background: #f7f7f7;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
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
            .form-grid {
                grid-template-columns: 1fr;
            }
            table {
                font-size: 12px;
            }
            th, td {
                padding: 6px;
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

    <div class="header">Service Requests</div>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if (!$tenant_id): ?>
        <div class="info-text">You currently do not have a tenant record. Service requests can only be submitted by tenants. Contact administration to link your account.</div>
    <?php else: ?>
        <div class="card">
        <form method="POST">
            <input type="hidden" name="create_service" value="1">
            <div class="form-grid">
                <div>
                    <label for="type_of_service">Type of Service</label>
                    <textarea id="type_of_service" name="type_of_service" placeholder="e.g. Plumbing, Electrical, Door repair" required rows="1" oninput="autoResize(this)"></textarea>
                </div>
                <div>
                    <label for="details">Details</label>
                    <textarea id="details" name="details" placeholder="Describe the issue with as much detail as possible" required rows="2" oninput="autoResize(this)"></textarea>
                </div>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
                <button class="btn" type="submit">Create Service Request</button>
                <button type="button" class="btn secondary" onclick="clearForm()">Clear</button>
                <div class="muted-note">You can expand the fields by typing — they auto-resize.</div>
            </div>
        </form>
        </div>

        <h2 style="margin-top:30px;color:#006400;">Your Requests</h2>
        <div style="margin-top:12px"></div>
        <table>
            <thead>
                <tr><th>Service ID</th><th>Type</th><th>Details</th><th>Bill (KES)</th><th>Date</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php if ($requests && $requests->num_rows > 0): while ($r = $requests->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['service_id']) ?></td>
                    <td><?= htmlspecialchars($r['type_of_service']) ?></td>
                    <td style="max-width:380px;white-space:pre-wrap;"><?= htmlspecialchars($r['details']) ?></td>
                    <td><?= number_format((float)$r['bill_amount'],2) ?></td>
                    <td><?= htmlspecialchars($r['date']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($r['status'])) ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="6" style="text-align:center;padding:20px;">No service requests found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>
</body>
</html>
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

// Auto-resize textareas based on content
function autoResize(el){
    if(!el) return;
    el.style.height = 'auto';
    el.style.height = (el.scrollHeight) + 'px';
}
// Initialize any existing textareas on load
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('textarea').forEach(t => autoResize(t));
});
function clearForm(){
    document.getElementById('type_of_service').value='';
    document.getElementById('details').value='';
    autoResize(document.getElementById('type_of_service'));
    autoResize(document.getElementById('details'));
}
</script>
