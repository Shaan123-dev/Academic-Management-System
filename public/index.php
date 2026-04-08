<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="main-content">
    <section class="hero">
        <h2>Welcome to the Academic Management Portal</h2>
        <p>
            A centralized system for managing academic activities, user roles, and institutional workflows.
        </p>
        <a href="/AMS/public/login.php" class="btn">Get Started</a>
    </section>

    <section class="cards">
        <div class="card">
            <h3>Admin</h3>
            <p>Manage users, courses, classes, and announcements.</p>
        </div>

        <div class="card">
            <h3>Teacher</h3>
            <p>Handle classes, attendance, assignments, and student records.</p>
        </div>

        <div class="card">
            <h3>Student</h3>
            <p>Access schedules, announcements, assignments, and academic information.</p>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>