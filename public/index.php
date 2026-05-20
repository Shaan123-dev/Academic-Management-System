<?php
require_once dirname(__DIR__) . '/includes/auth.php';
$pageTitle = APP_NAME . ' | Home';
$announcements = $pdo->query('SELECT title, body, posted_at FROM announcements ORDER BY posted_at DESC LIMIT 3')->fetchAll();
include dirname(__DIR__) . '/includes/header.php';
include dirname(__DIR__) . '/includes/topnav.php';
?>
<div class="landing-shell home-refined-shell">
  <section class="landing-hero refined-hero">
    <div class="hero-center refined-hero-center">
      <h2>Academic Management<br>Portal</h2>
      <p>
        A clean academic portal for attendance, assignments, results, announcements,
        schedules, and daily academic management for admins, teachers, and students.
      </p>
      <div class="hero-actions">
        <a class="hero-cta primary" href="<?= BASE_URL ?>/login.php">Portal Access</a>
        <a class="hero-cta" href="#gallery">Explore Gallery</a>
      </div>
    </div>
  </section>

  <section class="section home-gallery-section" id="gallery">
    <div class="gallery-wide-header solo-header">
      <h2 class="section-title">Gallery</h2>
      <p class="gallery-subtitle">A quick visual look at our college.</p>
    </div>
    <div class="gallery gallery-wide-only gallery-wide-only-full">
      <div class="gallery-item gallery-shot gallery-shot-admin">
        <div class="caption"><strong>Student Lounge</strong></div>
      </div>
      <div class="gallery-item gallery-shot gallery-shot-teacher">
        <div class="caption"><strong>Classrooms</strong></div>
      </div>
      <div class="gallery-item gallery-shot gallery-shot-student">
        <div class="caption"><strong>Graduation</strong></div>
      </div>
    </div>
  </section>

  <section class="section refined-announcements">
    <div class="announce-title-row">
      <h2 class="section-title">Recent Announcements</h2>
      <span class="announce-chip">Latest updates</span>
    </div>
    <div class="announcement-grid premium-announcement-grid single-row-announcements">
      <?php foreach ($announcements as $item): ?>
        <article class="announcement-card premium-announce-card better-announcement-card">
          <div class="announce-icon">📢</div>
          <div>
            <h3><?= e($item['title']) ?></h3>
            <p><?= e($item['body']) ?></p>
            <div class="subtle announce-date">Posted on <?= e(date('d M Y', strtotime($item['posted_at']))) ?></div>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$announcements): ?>
        <div class="empty">No announcements available right now.</div>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>