<?php
/**
 * 3WAY Car Service - Save/Update Customer API
 */
header('Content-Type: application/json');
require_once '../includes/config.php';

session_start();
if (!isset($_SESSION['user_id']) || !canPerformAction('save_customer')) {
    echo json_encode(['success' => false, 'message' => getLang() === 'ar' ? 'غير مصرح لك بهذا الإجراء' : 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $id = intval($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($name) || empty($phone)) {
        throw new Exception(getLang() === 'ar' ? 'الاسم ورقم الجوال مطلوبان' : 'Name and phone are required');
    }
    
    if ($id) {
        // Update existing customer
        $stmt = $db->prepare("UPDATE customers SET name = ?, phone = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $email, $id]);
        $message = getLang() === 'ar' ? 'تم تحديث بيانات العميل' : 'Customer updated successfully';
    } else {
        // Check if phone already exists
        $stmt = $db->prepare("SELECT id FROM customers WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            throw new Exception(getLang() === 'ar' ? 'رقم الجوال مسجل مسبقاً' : 'Phone number already exists');
        }
        
        // Insert new customer
        $stmt = $db->prepare("INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)");
        $stmt->execute([$name, $phone, $email]);
        $message = getLang() === 'ar' ? 'تم إضافة العميل بنجاح' : 'Customer added successfully';
    }
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
