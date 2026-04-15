<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_login();
redirect_to(dashboard_path(user()['role']));
