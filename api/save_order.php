<?php
/**
 * 3WAY Car Service - Save Order API
 */
header('Content-Type: application/json');
require_once '../includes/config.php';

session_start();
if (!isset($_SESSION['user_id']) || !canPerformAction('save_order')) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('طريقة طلب غير صالحة');
    }
    
    $customerName = sanitize($_POST['customer_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $branch = sanitize($_POST['branch'] ?? 'thumama');
    
    if (empty($customerName) || empty($phone)) {
        throw new Exception('يرجى ملء جميع الحقول المطلوبة');
    }
    
    $orderNumber = generateOrderNumber();
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    
    // Get or create customer
    $stmt = $db->prepare("SELECT id FROM customers WHERE phone = ?");
    $stmt->execute([$phone]);
    $customer = $stmt->fetch();
    
    if ($customer) {
        $customerId = $customer['id'];
        $stmt = $db->prepare("UPDATE customers SET name = ?, total_orders = total_orders + 1 WHERE id = ?");
        $stmt->execute([$customerName, $customerId]);
    } else {
        $stmt = $db->prepare("INSERT INTO customers (name, phone, visit_source, total_orders) VALUES (?, ?, ?, 1)");
        $stmt->execute([$customerName, $phone, sanitize($_POST['visit_source'] ?? '')]);
        $customerId = $db->lastInsertId();
    }
    
    // Prepare order data
    $orderData = [
        'order_number' => $orderNumber,
        'customer_id' => $customerId,
        'branch' => in_array($branch, ['thumama', 'rawdah']) ? $branch : 'thumama',
        'visit_source' => sanitize($_POST['visit_source'] ?? ''),
        'car_type' => sanitize($_POST['car_type'] ?? ''),
        'car_model' => sanitize($_POST['car_model'] ?? ''),
        'car_color' => sanitize($_POST['car_color'] ?? ''),
        'plate_number' => sanitize($_POST['plate_number'] ?? ''),
        'has_dents' => isset($_POST['has_dents']) ? 1 : 0,
        'has_paint_erosion' => isset($_POST['has_paint_erosion']) ? 1 : 0,
        'has_scratches' => isset($_POST['has_scratches']) ? 1 : 0,
        'has_previous_polish' => isset($_POST['has_previous_polish']) ? 1 : 0,
        'has_exterior_mods' => isset($_POST['has_exterior_mods']) ? 1 : 0,
        'condition_details' => sanitize($_POST['condition_details'] ?? ''),
        'service_body_repair' => isset($_POST['service_body_repair']) ? 1 : 0,
        'service_parts_install' => isset($_POST['service_parts_install']) ? 1 : 0,
        'service_collision_repair' => isset($_POST['service_collision_repair']) ? 1 : 0,
        'service_single_paint' => isset($_POST['service_single_paint']) ? 1 : 0,
        'service_multi_paint' => isset($_POST['service_multi_paint']) ? 1 : 0,
        'service_full_spray' => isset($_POST['service_full_spray']) ? 1 : 0,
        'service_single_dent' => isset($_POST['service_single_dent']) ? 1 : 0,
        'service_multi_dents' => isset($_POST['service_multi_dents']) ? 1 : 0,
        'service_exterior_polish' => isset($_POST['service_exterior_polish']) ? 1 : 0,
        'service_interior_polish' => isset($_POST['service_interior_polish']) ? 1 : 0,
        'service_lights_polish' => isset($_POST['service_lights_polish']) ? 1 : 0,
        'service_scratch_treatment' => isset($_POST['service_scratch_treatment']) ? 1 : 0,
        'service_nano_ceramic' => isset($_POST['service_nano_ceramic']) ? 1 : 0,
        'service_ppf' => isset($_POST['service_ppf']) ? 1 : 0,
        'service_wash' => isset($_POST['service_wash']) ? 1 : 0,
        'service_deep_cleaning' => isset($_POST['service_deep_cleaning']) ? 1 : 0,
        'estimated_cost' => floatval($_POST['estimated_cost'] ?? 0),
        'expected_completion_time' => sanitize($_POST['expected_completion_time'] ?? ''),
        'delivery_date' => !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null,
        'body_technician' => sanitize($_POST['body_technician'] ?? ''),
        'paint_technician' => sanitize($_POST['paint_technician'] ?? ''),
        'pdr_technician' => sanitize($_POST['pdr_technician'] ?? ''),
        'polish_technician' => sanitize($_POST['polish_technician'] ?? ''),
        'branch_manager' => sanitize($_POST['branch_manager'] ?? ''),
        'status' => 'pending',
        'created_by' => $_SESSION['user_id']
    ];
    
    $columns = implode(', ', array_keys($orderData));
    $placeholders = implode(', ', array_fill(0, count($orderData), '?'));
    
    $stmt = $db->prepare("INSERT INTO job_orders ($columns) VALUES ($placeholders)");
    $stmt->execute(array_values($orderData));
    $orderId = $db->lastInsertId();
    
    // Handle media uploads
    if (!empty($_FILES['media']['name'][0])) {
        $uploadDir = UPLOAD_PATH . 'orders/' . $orderId . '/before/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedExts = getAllowedExtensions();
        
        foreach ($_FILES['media']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {
                $originalName = $_FILES['media']['name'][$key];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                
                if (!in_array($extension, $allowedExts)) continue;
                if ($_FILES['media']['size'][$key] > MAX_FILE_SIZE) continue;
                
                $fileName = uniqid() . '_' . time() . '.' . $extension;
                $relativePath = 'uploads/orders/' . $orderId . '/before/' . $fileName;
                $fullPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $fullPath)) {
                    $mediaType = isVideo($fileName) ? 'video' : 'image';
                    $fileSize = $_FILES['media']['size'][$key];
                    
                    $stmt = $db->prepare("INSERT INTO order_photos (order_id, photo_type, media_type, file_name, file_path, file_size) VALUES (?, 'before', ?, ?, ?, ?)");
                    $stmt->execute([$orderId, $mediaType, $fileName, $relativePath, $fileSize]);
                }
            }
        }
    }
    
    // Log activity
    $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address) VALUES (?, 'create', 'job_order', ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $orderId, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إنشاء الطلب بنجاح',
        'order_id' => $orderId,
        'order_number' => $orderNumber
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
