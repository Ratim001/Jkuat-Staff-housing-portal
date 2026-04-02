<?php
require_once '../includes/init.php';
require_once '../includes/db.php';
// session already started in includes/init.php

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
    header('Location: applicant_profile.php?redirect=my_bills.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: applicantlogin.php');
    exit;
}

// Prefer tenant_id in session; fall back to DB lookup so rights apply immediately
$tenant_id = $_SESSION['tenant_id'] ?? null;
if (!$tenant_id && !empty($_SESSION['applicant_id'])) {
    require_once __DIR__ . '/../includes/helpers.php';
    $t = get_tenant_for_applicant($conn, $_SESSION['applicant_id']);
    if ($t) { $_SESSION['tenant_id'] = $t['tenant_id']; $tenant_id = $t['tenant_id']; }
}

// Fetch bills for this tenant via service->service_requests
$bills = [];
if ($tenant_id) {
    $q = $conn->prepare("SELECT b.bill_id, b.service_id, b.type_of_bill, b.amount, b.date_billed, b.date_settled, b.status, COALESCE(b.statuses,'active') as statuses FROM bills b JOIN service_requests s ON b.service_id = s.service_id WHERE s.tenant_id = ? ORDER BY b.date_billed DESC");
    $q->bind_param('s', $tenant_id);
    $q->execute();
    $bills = $q->get_result();
}

// Active page detection
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Bills | JKUAT Housing</title>
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

        .filter-bar {
            display: flex;
            justify-content: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-bar input, .filter-bar select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #006400;
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .btn {
            padding: 6px 10px;
            border-radius: 5px;
            border: none;
            background: #006400;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
        }

        .dispute {
            background: #ffc107;
            color: #000;
        }

        .btn:hover {
            background: #005826;
        }

        .dispute:hover {
            background: #ffb304;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: bold;
            display: inline-block;
            font-size: 12px;
        }

        .status-active {
            background-color: #28a745;
            color: white;
        }

        .status-disputed {
            background-color: #ffc107;
            color: #000;
        }

        .warn {
            background: #fff3cd;
            padding: 12px;
            border: 1px solid #ffeeba;
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

    <div class="header">Bills</div>

    <?php if (!$tenant_id): ?>
        <div class="warn">You do not have a tenant record. Bills are linked to tenant accounts.</div>
    <?php else: ?>
        <div class="filter-bar">
            <input id="searchInput" placeholder="Search by bill id, service id or type...">
            <select id="statusFilter">
                <option value="">All statuses</option>
                <option value="not paid">Not paid</option>
                <option value="paid">Paid</option>
            </select>
        </div>

        <table id="billsTable">
            <thead>
            <tr>
                <th>Bill ID</th>
                <th>Service ID</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Date Billed</th>
                <th>Date Settled</th>
                <th>Payment Status</th>
                <th>Admin Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($bills && $bills->num_rows > 0): while ($r = $bills->fetch_assoc()):
                $adminStatus = strtolower($r['statuses'] ?? 'active');
                $badgeClass = $adminStatus === 'disputed' ? 'status-disputed' : 'status-active';
            ?>
                <tr data-bill-id="<?= htmlspecialchars($r['bill_id']) ?>">
                    <td><?= htmlspecialchars($r['bill_id']) ?></td>
                    <td><?= htmlspecialchars($r['service_id']) ?></td>
                    <td><?= htmlspecialchars($r['type_of_bill']) ?></td>
                    <td><?= number_format((float)$r['amount'],2) ?></td>
                    <td><?= htmlspecialchars($r['date_billed']) ?></td>
                    <td><?= htmlspecialchars($r['date_settled']) ?></td>
                    <td><?= htmlspecialchars($r['status']) ?></td>
                    <td><span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($adminStatus)) ?></span></td>
                    <td>
                        <?php if ($adminStatus !== 'disputed'): ?>
                            <button class="btn dispute" onclick="disputeBill('<?= htmlspecialchars($r['bill_id']) ?>')">Dispute</button>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="9" style="text-align: center; padding: 20px; color: #999;">No bills found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
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

// Simple client-side filtering
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const rows = () => Array.from(document.querySelectorAll('#billsTable tbody tr'));
function applyFilters(){
    const q = (searchInput?.value||'').toLowerCase();
    const status = (statusFilter?.value||'').toLowerCase();
    rows().forEach(r=>{
        const text = r.textContent.toLowerCase();
        const payStatus = r.children[6]?.textContent.toLowerCase()||'';
        const match = (q === '' || text.includes(q)) && (status === '' || payStatus === status);
        r.style.display = match ? '' : 'none';
    });
}
if (searchInput) searchInput.addEventListener('input', applyFilters);
if (statusFilter) statusFilter.addEventListener('change', applyFilters);

function disputeBill(billId){
    if (!confirm('Are you sure you want to dispute this bill? The admin will be notified.')) return;
    fetch('dispute_bill.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'bill_id='+encodeURIComponent(billId)})
        .then(r=>r.json()).then(j=>{ if (j.success) location.reload(); else alert('Failed: '+(j.error||'unknown')); }).catch(e=>{console.error(e);alert('Error sending dispute')});
}
</script>
</body>
</html>
