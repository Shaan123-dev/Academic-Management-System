<?php
declare(strict_types=1);

$pageTitle = 'Home - Academic Management Portal';
$pageDescription = 'Marks Mafias Academic Management Portal with gallery, announcements, events, and role-based login.';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

/*
|--------------------------------------------------------------------------
| Fetch only public-safe announcements for home page
|--------------------------------------------------------------------------
| Home page should only show announcements meant for all users.
|--------------------------------------------------------------------------
*/
$annStmt = $pdo->prepare("
    SELECT title, content, created_at
    FROM announcements
    WHERE status = 'published'
      AND target_role = 'all'
    ORDER BY created_at DESC
    LIMIT 3
");
$annStmt->execute();
$announcements = $annStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Fetch upcoming events
|--------------------------------------------------------------------------
*/
$eventStmt = $pdo->prepare("
    SELECT title, description, start_date, end_date
    FROM calendar_events
    ORDER BY start_date ASC
    LIMIT 3
");
$eventStmt->execute();
$events = $eventStmt->fetchAll();
?>

<section class="hero">
    <div class="container hero-grid">
        <div class="hero-left">
            <h2>Academic Management Portal</h2>
            <p>
                A secure and responsive web portal for Admin, Teacher, and Student users.
                Manage courses, attendance, assignments, results, announcements, and academic records efficiently.
            </p>

            <div class="btn-group">
                <a class="btn btn-primary" href="<?= BASE_URL ?>/public/login.php">Login</a>
                <a class="btn btn-outline" href="<?= BASE_URL ?>/public/register.php">Register</a>
            </div>
        </div>

        <div class="hero-card">
            <h3 style="color:#0f3d91; margin-bottom:12px;">Short Description</h3>
            <p style="color:#1b1f24; margin-bottom:16px;">
                This Academic Management Portal System is designed to streamline academic operations
                with secure role-based access, user management, attendance, assignments, results,
                announcements, and class scheduling.
            </p>

            <img src="<?= BASE_URL ?>/assets/images/logo.jpeg" alt="Marks Mafias Logo" style="max-width:180px; margin:0 auto;">
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Gallery</h2>
        <div class="grid-4">
            <div class="gallery-item">
                <img src="<?= BASE_URL ?>/assets/images/background.jpg" alt="Campus View">
                <div class="caption">Campus Environment</div>
            </div>
            <div class="gallery-item">
                <img src="<?= BASE_URL ?>/assets/images/background.jpg" alt="Students">
                <div class="caption">Student Activities</div>
            </div>
            <div class="gallery-item">
                <img src="<?= BASE_URL ?>/assets/images/background.jpg" alt="Academic Blocks">
                <div class="caption">Academic Facilities</div>
            </div>
            <div class="gallery-item">
                <img src="<?= BASE_URL ?>/assets/images/background.jpg" alt="Classroom">
                <div class="caption">Classroom Space</div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:#eef5ff;">
    <div class="container grid-2">
        <div class="panel">
            <h3>Upcoming Events</h3>
            <div class="events-list">
                <?php if ($events): ?>
                    <?php foreach ($events as $event): ?>
                        <div class="event-item">
                            <h4><?= e($event['title']) ?></h4>
                            <p><?= e($event['description'] ?? 'No description available.') ?></p>
                            <small>
                                <?= e($event['start_date']) ?>
                                <?php if (!empty($event['end_date'])): ?>
                                    - <?= e($event['end_date']) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="event-item">
                        <h4>No upcoming events yet</h4>
                        <p>Events added by admin will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <h3>Latest Announcements</h3>
            <div class="events-list">
                <?php if ($announcements): ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="event-item">
                            <h4><?= e($announcement['title']) ?></h4>
                            <p><?= e(mb_strimwidth(strip_tags($announcement['content']), 0, 180, '...')) ?></p>
                            <small><?= e($announcement['created_at']) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="event-item">
                        <h4>No public announcements yet</h4>
                        <p>Announcements published for all users will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>