<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);
$u = user();
$code = $u['role_code'] ?: digital_code('STD', (int)$u['id']);
$expiry = date('d M Y', strtotime('+1 year'));

$pageTitle = 'Digital ID | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <h1 class="page-title">Student Digital ID</h1>
        <div class="id-wrap">
            <div class="id-card" id="idCard">
                <div class="id-head">
                    <div style="display:flex;align-items:center;gap:14px;">
                        <img class="logo" src="<?= BASE_URL ?>/../assets/images/logo.png" alt="Logo">
                        <div>
                            <strong style="font-size:1.15rem;">Marks Mafias</strong>
                            <div>Academic Management System</div>
                        </div>
                    </div>
                    <div class="id-highlight"><?= e($code) ?></div>
                </div>
                <div class="id-body">
                    <img class="avatar" src="<?= photo_path($u['photo'] ?? null) ?>" alt="Student Photo">
                    <div class="id-details">
                        <div class="id-highlight">Official Student ID</div>
                        <h2><?= e($u['name']) ?></h2>
                        <p><strong>Role:</strong> Student</p>
                        <p><strong>Course:</strong> <?= e($u['department'] ?: 'Not Assigned') ?></p>
                        <p><strong>Email:</strong> <?= e($u['email']) ?></p>
                        <p><strong>Contact:</strong> <?= e($u['contact'] ?: 'N/A') ?></p>
                        <p><strong>Guardian:</strong> <?= e($u['guardian'] ?: 'N/A') ?></p>
                        <p><strong>Valid Till:</strong> <?= e($expiry) ?></p>
                    </div>
                </div>
                <div class="print-btn">
                    <button class="btn btn-secondary" onclick="window.print()">🖨️ Print ID</button>
                    <button class="btn btn-primary" onclick="downloadID()">⬇ Download as PNG</button>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    function downloadID() {
        const element = document.getElementById('idCard');

        // Show loading state on button
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ Capturing...';
        btn.disabled = true;

        html2canvas(element, {
            scale: 2.5,
            backgroundColor: null,
            useCORS: true,
            logging: false
        }).then(canvas => {
            const link = document.createElement('a');
            const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
            link.download = `Student_ID_${<?= json_encode($code) ?>}_${timestamp}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();

            btn.innerHTML = originalText;
            btn.disabled = false;
        }).catch(error => {
            console.error('Error:', error);
            btn.innerHTML = '❌ Failed';
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2000);
        });
    }
</script>

<style>
    .print-btn {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 20px;
        position: relative;
        z-index: 1;
    }

    @media print {

        .sidebar,
        .topbar,
        .dashboard-top,
        .print-btn,
        .dashboard-analytics,
        .quick-actions,
        .panel-card,
        .user-chip,
        .footer {
            display: none !important;
        }

        .dashboard-shell {
            display: block !important;
        }

        .main-panel {
            margin: 0 !important;
            padding: 0 !important;
        }

        .id-card {
            box-shadow: none !important;
            margin: 0 !important;
            page-break-inside: avoid;
        }
    }
</style>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>