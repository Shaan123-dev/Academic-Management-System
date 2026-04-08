<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('Teacher');

include __DIR__ . '/../../includes/header.php';
?>

<h1>Teacher Dashboard</h1>
<p>Welcome, Teacher.</p>

<?php include __DIR__ . '/../../includes/footer.php'; ?>