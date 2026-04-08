<?php
session_start();

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /AMS/public/login.php");
        exit;
    }
}