<?php
// common.php - include in pages for session and helper functions
if (session_status() === PHP_SESSION_NONE) session_start();
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}
function esc($v) {
    return htmlspecialchars($v, ENT_QUOTES);
}
?>