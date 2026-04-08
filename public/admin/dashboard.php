<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('Admin');

include __DIR__ . '/../../includes/header.php';
?>

<h1>Admin Dashboard</h1>
<p>Welcome, Admin.</p>

<?php include __DIR__ . '/../../includes/footer.php'; ?>