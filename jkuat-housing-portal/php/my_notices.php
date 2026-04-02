<?php
require_once '../includes/init.php';
require_once '../includes/db.php';
// session is started in includes/init.php

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
    header('Location: applicant_profile.php?redirect=my_notices.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: applicantlogin.php');
    exit;
}

// Resolve tenant id from session or DB to give immediate tenant access
$tenant_id = $_SESSION['tenant_id'] ?? null;
if (!$tenant_id && !empty($_SESSION['applicant_id'])) {
    require_once __DIR__ . '/../includes/helpers.php';
    $t = get_tenant_for_applicant($conn, $_SESSION['applicant_id']);
    if ($t) { $_SESSION['tenant_id'] = $t['tenant_id']; $tenant_id = $t['tenant_id']; }
}

// Handle optional success message
$msg = $_GET['msg'] ?? '';

// Active page detection
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Notices | JKUAT Housing</title>
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

        h3 {
            margin: 0 0 12px;
            color: #006400;
        }

        .grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 24px;
            align-items: start;
        }

        .card {
            background: #fff;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 16px;
        }

        .field {
            margin-top: 12px;
        }

        label {
            display: block;
            margin: 0 0 6px;
            font-weight: 700;
            color: #333;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .note {
            background: #d4edda;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .warn {
            background: #fff3cd;
            padding: 12px;
            border: 1px solid #ffeeba;
            border-radius: 6px;
        }

        table {
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        thead {
            background-color: #006400;
            color: #fff;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ccc;
            vertical-align: top;
            word-wrap: break-word;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: #006400;
            color: #fff;
        }

        .btn-danger {
            background: #ff9800;
            color: #fff;
        }

        .btn-primary:hover {
            background: #005826;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .status-active {
            background-color: #28a745;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .status-revoked {
            background-color: #ff9800;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .status-fulfilled {
            background-color: #17a2b8;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
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
            .grid {
                grid-template-columns: 1fr;
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

    <div class="header">Notices</div>

    <?php if ($msg): ?>
        <div class="note"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h3>Place a Notice / Move-out Request</h3>

            <?php if (!$tenant_id): ?>
                <div class="warn">You do not have a tenant record. Only tenants can place notices.</div>
            <?php else: ?>
                <form method="post" action="submit_notice.php">
                    <input type="hidden" name="tenant_id" value="<?php echo htmlspecialchars($tenant_id); ?>">

                    <div class="field">
                        <label for="details">Details</label>
                        <textarea id="details" name="details" required></textarea>
                    </div>

                    <div class="field">
                        <label for="move_out_date">Move Out Date</label>
                        <input type="date" id="move_out_date" name="move_out_date" required>
                    </div>

                    <div class="field">
                        <button class="action-btn btn-primary" type="submit">Submit Notice</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div>
            <h3>Your Notices</h3>
            <?php
            // Fetch notices for tenant
            $notices = [];
            if ($tenant_id) {
                $p = $conn->prepare("SELECT notice_id, details, date_sent, notice_end_date, status FROM notices WHERE tenant_id = ? ORDER BY date_sent DESC");
                $p->bind_param('s', $tenant_id);
                $p->execute();
                $notices = $p->get_result();
            }
            ?>

            <table>
                <thead>
                    <tr>
                        <th style="width: 14%;">Notice ID</th>
                        <th style="width: 34%;">Details</th>
                        <th style="width: 16%;">Date Sent</th>
                        <th style="width: 16%;">Move Out Date</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 10%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($notices && $notices->num_rows > 0): while ($nr = $notices->fetch_assoc()): ?>
                    <?php
                    $status = strtolower($nr['status'] ?? 'active');
                    $statusClass = ($status === 'active') ? 'status-active' : (($status === 'revoked') ? 'status-revoked' : 'status-fulfilled');
                    $canRevoke = false;
                    try {
                        $ds = new DateTime($nr['date_sent']);
                        $now = new DateTime();
                        if ($now < $ds && $status === 'active') $canRevoke = true;
                    } catch (Exception $e) {}
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($nr['notice_id']) ?></td>
                        <td><?= htmlspecialchars($nr['details']) ?></td>
                        <td><?= htmlspecialchars($nr['date_sent']) ?></td>
                        <td><?= htmlspecialchars($nr['notice_end_date']) ?></td>
                        <td><span class="<?= $statusClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
                        <td>
                            <?php if ($canRevoke): ?>
                                <button type="button" class="action-btn btn-danger" onclick="revokeNotice('<?= htmlspecialchars($nr['notice_id']) ?>')">Revoke</button>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6">No notices found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

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

function revokeNotice(id) {
    if (!confirm('Revoke this notice?')) return;
    fetch('update_notice_status.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'notice_id=' + encodeURIComponent(id) + '&status=revoked'
    }).then(r=>r.json()).then(j=>{
        if (j.success) location.reload(); else alert('Failed: ' + (j.error||'unknown'));
    }).catch(e=>{ console.error(e); alert('Error'); });
}
</script>
</body>
</html>
