<?php
/**
 * 3WAY Car Service - Delete Customer API
 */
header('Content-Type: application/json');
require_once '../includes/config.php';

session_start();
if (!isset($_SESSION['user_id']) || !canPerformAction('delete_customer')) {
    echo json_encode(['success' => false, 'message' => getLang() === 'ar' ? 'غير مصرح لك بهذا الإجراء' : 'Unauthorized']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    
    if (!$id) {
        throw new Exception(getLang() === 'ar' ? 'معرف العميل غير صحيح' : 'Invalid customer ID');
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Check if customer has orders
    $stmt = $db->prepare("SELECT COUNT(*) FROM job_orders WHERE customer_id = ?");
    $stmt->execute([$id]);
    $orderCount = $stmt->fetchColumn();
    
    if ($orderCount > 0) {
        throw new Exception(getLang() === 'ar' ? 'لا يمكن حذف العميل لأن لديه طلبات مرتبطة' : 'Cannot delete customer with existing orders');
    }
    
    $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true, 
        'message' => getLang() === 'ar' ? 'تم حذف العميل بنجاح' : 'Customer deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
