<?php
session_start();
session_destroy();
header("Location: /AMS/public/login.php");
exit;