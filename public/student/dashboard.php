<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('Student');

include __DIR__ . '/../../includes/header.php';
?>

<h1>Student Dashboard</h1>
<p>Welcome, Student.</p>

<?php include __DIR__ . '/../../includes/footer.php'; ?>