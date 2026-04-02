<?php
session_start();
include '../includes/db.php';

// Check if applicant is logged in - applicants-only page
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
    header('Location: applicant_profile.php?redirect=notifications.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: applicantlogin.php');
    exit;
}

// Mark all as read
$conn->query("UPDATE notifications SET status='read' WHERE recipient_type='applicant' AND recipient_id='$applicant_id'");

// Fetch notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE recipient_type = 'applicant' AND recipient_id = ? ORDER BY date_sent DESC");
$stmt->bind_param("s", $applicant_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Active page detection
$current = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications | JKUAT Housing</title>
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

        .notification-card {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid #006400;
        }
        
        .notification-title {
            font-weight: bold;
            font-size: 18px;
            color: #004225;
            margin-bottom: 8px;
        }
        
        .notification-body {
            color: #333;
            line-height: 1.6;
        }
        
        .notification-date {
            font-size: 12px;
            color: gray;
            margin-top: 12px;
            text-align: right;
        }

        .notification-card button {
            background: #ff9800;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 12px;
        }

        .notification-card button:hover {
            background: #c82333;
        }

        .empty-message {
            background: #f7f7f7;
            padding: 20px;
            border-radius: 8px;
            color: #666;
            text-align: center;
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

    <div class="header">Notifications</div>

    <?php if ($notifications->num_rows > 0): ?>
        <?php while ($note = $notifications->fetch_assoc()): ?>
            <div class="notification-card" id="<?= htmlspecialchars($note['notification_id']) ?>">
                <div>
                    <div class="notification-title">
                        <?= htmlspecialchars($note['title'] ?? 'No Title') ?>
                    </div>
                    <div class="notification-body">
                        <?= nl2br(htmlspecialchars($note['message'])) ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <button onclick="deleteNotification('<?= htmlspecialchars($note['notification_id']) ?>')">Delete</button>
                        <div class="notification-date">
                            <?= date('F j, Y, g:i a', strtotime($note['date_sent'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-message">No notifications at this time.</div>
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

function deleteNotification(id) {
    if (!confirm('Delete this notification?')) return;
    fetch('delete_notification.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'notification_id=' + encodeURIComponent(id)
    }).then(r => r.json()).then(j => {
        if (j.success) {
            const el = document.getElementById(id);
            if (el) el.remove();
        } else {
            alert('Failed: ' + (j.error||'unknown'));
        }
    }).catch(e => {
        console.error(e);
        alert('Error deleting notification');
    });
}
</script>

</body>
</html>