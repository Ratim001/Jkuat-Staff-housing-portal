<?php
/**
 * Create a fictional local admin with a generated password.
 * Usage: php migrations/seed_admin.php
 * Existing accounts are never overwritten.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this script from the command line.');
}

require_once __DIR__ . '/../includes/db.php';

$username = 'admin';
$check = $conn->prepare('SELECT user_id FROM users WHERE username = ? LIMIT 1');
$check->bind_param('s', $username);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo "Admin user already exists; no changes made.\n";
    exit(0);
}
$check->close();

$userId = 'DEMO-' . bin2hex(random_bytes(8));
$pfNo = $userId;
$name = 'Demo Administrator';
$email = 'admin@example.com';
$role = 'CS Admin';
$status = 'Active';
$password = bin2hex(random_bytes(16));
$hash = password_hash($password, PASSWORD_DEFAULT);

$insert = $conn->prepare(
    'INSERT INTO users (user_id, pf_no, username, name, email, role, password, date_created, status) '
    . 'VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)'
);
$insert->bind_param('ssssssss', $userId, $pfNo, $username, $name, $email, $role, $hash, $status);
if (!$insert->execute()) {
    fwrite(STDERR, "Could not create the administrator. Check the database privately.\n");
    exit(1);
}
echo "Created fictional local account: admin\n";
echo "Generated password (store privately): " . $password . "\n";
$insert->close();
$conn->close();
