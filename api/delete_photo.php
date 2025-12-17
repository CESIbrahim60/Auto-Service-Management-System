<?php
/**
 * 3WAY Car Service - Delete Photo API
 */
header('Content-Type: application/json');
require_once '../includes/config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $photoId = intval($input['id'] ?? 0);
    
    if (!$photoId) {
        throw new Exception(getLang() === 'ar' ? 'معرف الصورة غير صحيح' : 'Invalid photo ID');
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Get photo info
    $stmt = $db->prepare("SELECT * FROM order_photos WHERE id = ?");
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$photo) {
        throw new Exception(getLang() === 'ar' ? 'الصورة غير موجودة' : 'Photo not found');
    }
    
    // Delete file from server
    $fullPath = __DIR__ . '/../' . $photo['file_path'];
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
    
    // Delete from database
    $stmt = $db->prepare("DELETE FROM order_photos WHERE id = ?");
    $stmt->execute([$photoId]);
    
    echo json_encode([
        'success' => true,
        'message' => getLang() === 'ar' ? 'تم حذف الصورة بنجاح' : 'Photo deleted successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
