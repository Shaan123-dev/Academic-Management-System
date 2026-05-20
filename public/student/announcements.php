<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);
$rows = $pdo->query('SELECT a.*, COALESCE(s.subject_name,"General") AS subject_name FROM announcements a LEFT JOIN subjects s ON s.id=a.subject_id WHERE visibility_role IN ("all","student") ORDER BY posted_at DESC')->fetchAll();
$pageTitle = 'Announcements | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Announcements</h1>
                <p>View announcements available to students.</p>
            </div>
        </div>
        <div class="table-card">
            <div class="search-row"><input type="text" placeholder="Search announcements" data-table-search="studentAnnTable"></div>
            <div class="table-wrap">
                <table id="studentAnnTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Audience</th>
                            <th>Subject</th>
                            <th>Posted</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($rows as $r): ?><tr>
                                <td><strong><?= e($r['title']) ?></strong>
                                    <div class="subtle"><?= e($r['body']) ?></div>
                                </td>
                                <td><?= e($r['target_audience'] ?: 'General') ?></td>
                                <td><?= e($r['subject_name']) ?></td>
                                <td><?= e(date('d M Y', strtotime($r['posted_at']))) ?></td>
                            </tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </main>
</div><?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>