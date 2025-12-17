<?php
/**
 * 3WAY Car Service - Logout
 */
session_start();
require_once 'includes/config.php';

// Log the logout activity
if (isset($_SESSION['user_id'])) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, details, ip_address) VALUES (?, 'logout', 'user', 'User logged out', ?)");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        // Silent fail
    }
}

// Destroy session
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
