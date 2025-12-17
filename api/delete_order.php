<?php
/**
 * 3WAY Car Service - Delete Order API
 */

header('Content-Type: application/json');
require_once '../includes/config.php';

// Check if user is logged in and has permission
session_start();
if (!isset($_SESSION['user_id']) || !canPerformAction('delete_order')) {
    echo json_encode(['success' => false, 'message' => getLang() === 'ar' ? 'غير مصرح لك بهذا الإجراء' : 'You are not authorized to perform this action']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = intval($input['id'] ?? 0);
    
    if (!$orderId) {
        throw new Exception(getLang() === 'ar' ? 'معرف الطلب غير صحيح' : 'Invalid order ID');
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Check if order exists
    $stmt = $db->prepare("SELECT id, order_number FROM job_orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception(getLang() === 'ar' ? 'الطلب غير موجود' : 'Order not found');
    }
    
    // Delete order (cascade will delete photos)
    $stmt = $db->prepare("DELETE FROM job_orders WHERE id = ?");
    $stmt->execute([$orderId]);
    
    // Log activity
    $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, 'delete', 'job_order', ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'] ?? null, $orderId, "Deleted order: " . $order['order_number'], $_SERVER['REMOTE_ADDR'] ?? '']);
    
    // Delete uploaded files
    $uploadDir = UPLOAD_PATH . 'orders/' . $orderId;
    if (is_dir($uploadDir)) {
        array_map('unlink', glob("$uploadDir/*/*"));
        array_map('rmdir', glob("$uploadDir/*"));
        rmdir($uploadDir);
    }
    
    echo json_encode([
        'success' => true,
        'message' => getLang() === 'ar' ? 'تم حذف الطلب بنجاح' : 'Order deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
