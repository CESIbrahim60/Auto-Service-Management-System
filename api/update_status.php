<?php
/**
 * 3WAY Car Service - Update Order Status API
 */

header('Content-Type: application/json');
require_once '../includes/config.php';

// Check if user is logged in and has permission (all users can update status)
session_start();
if (!isset($_SESSION['user_id']) || !canPerformAction('update_status')) {
    echo json_encode(['success' => false, 'message' => getLang() === 'ar' ? 'يرجى تسجيل الدخول' : 'Please login first']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = intval($input['id'] ?? 0);
    $newStatus = sanitize($input['status'] ?? '');
    
    $validStatuses = ['pending', 'in_progress', 'completed', 'delivered', 'cancelled'];
    
    if (!$orderId || !in_array($newStatus, $validStatuses)) {
        throw new Exception(getLang() === 'ar' ? 'بيانات غير صحيحة' : 'Invalid data');
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("UPDATE job_orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    
    // Log activity
    $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, 'update_status', 'job_order', ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'] ?? null, $orderId, "Status changed to: $newStatus", $_SERVER['REMOTE_ADDR'] ?? '']);
    
    echo json_encode([
        'success' => true,
        'message' => getLang() === 'ar' ? 'تم تحديث الحالة بنجاح' : 'Status updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
