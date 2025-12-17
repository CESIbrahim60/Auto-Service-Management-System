<?php
/**
 * 3WAY Car Service - Edit Order (Arabic Only)
 */
require_once 'includes/config.php';
requireAccess('edit_order.php');

$db = Database::getInstance()->getConnection();
$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) { header('Location: orders.php'); exit; }

$stmt = $db->prepare("SELECT j.*, c.name as customer_name, c.phone as customer_phone FROM job_orders j LEFT JOIN customers c ON j.customer_id = c.id WHERE j.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) { header('Location: orders.php'); exit; }

// Check access restrictions
if (isReception()) {
    // Reception can only edit their own orders
    if ($order['created_by'] != $_SESSION['user_id']) {
        header('Location: 404.php');
        exit;
    }
} else {
    // Manager can only edit orders from their branch
    $userBranch = getBranchFilter();
    if ($userBranch && $order['branch'] !== $userBranch) {
        header('Location: 404.php');
        exit;
    }
}

// Fetch existing media
$stmt = $db->prepare("SELECT * FROM order_photos WHERE order_id = ? ORDER BY photo_type, uploaded_at");
$stmt->execute([$orderId]);
$allMedia = $stmt->fetchAll(PDO::FETCH_ASSOC);
$beforeMedia = array_filter($allMedia, fn($m) => $m['photo_type'] === 'before');
$afterMedia = array_filter($allMedia, fn($m) => $m['photo_type'] === 'after');

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Update customer
        $stmt = $db->prepare("UPDATE customers SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([sanitize($_POST['customer_name']), sanitize($_POST['phone']), $order['customer_id']]);
        
        // Update order
        $stmt = $db->prepare("UPDATE job_orders SET
            branch = ?, visit_source = ?, car_type = ?, car_model = ?, car_color = ?, plate_number = ?,
            has_dents = ?, has_paint_erosion = ?, has_scratches = ?, has_previous_polish = ?, has_exterior_mods = ?,
            condition_details = ?,
            service_body_repair = ?, service_parts_install = ?, service_collision_repair = ?,
            service_single_paint = ?, service_multi_paint = ?, service_full_spray = ?,
            service_single_dent = ?, service_multi_dents = ?,
            service_exterior_polish = ?, service_interior_polish = ?, service_lights_polish = ?,
            service_scratch_treatment = ?, service_nano_ceramic = ?, service_ppf = ?,
            service_wash = ?, service_deep_cleaning = ?,
            estimated_cost = ?, expected_completion_time = ?, delivery_date = ?,
            body_technician = ?, paint_technician = ?, pdr_technician = ?, polish_technician = ?, branch_manager = ?,
            status = ?
            WHERE id = ?");
        
        $stmt->execute([
            sanitize($_POST['branch']),
            sanitize($_POST['visit_source'] ?? ''),
            sanitize($_POST['car_type']),
            sanitize($_POST['car_model'] ?? ''),
            sanitize($_POST['car_color'] ?? ''),
            sanitize($_POST['plate_number'] ?? ''),
            isset($_POST['has_dents']) ? 1 : 0,
            isset($_POST['has_paint_erosion']) ? 1 : 0,
            isset($_POST['has_scratches']) ? 1 : 0,
            isset($_POST['has_previous_polish']) ? 1 : 0,
            isset($_POST['has_exterior_mods']) ? 1 : 0,
            sanitize($_POST['condition_details'] ?? ''),
            isset($_POST['service_body_repair']) ? 1 : 0,
            isset($_POST['service_parts_install']) ? 1 : 0,
            isset($_POST['service_collision_repair']) ? 1 : 0,
            isset($_POST['service_single_paint']) ? 1 : 0,
            isset($_POST['service_multi_paint']) ? 1 : 0,
            isset($_POST['service_full_spray']) ? 1 : 0,
            isset($_POST['service_single_dent']) ? 1 : 0,
            isset($_POST['service_multi_dents']) ? 1 : 0,
            isset($_POST['service_exterior_polish']) ? 1 : 0,
            isset($_POST['service_interior_polish']) ? 1 : 0,
            isset($_POST['service_lights_polish']) ? 1 : 0,
            isset($_POST['service_scratch_treatment']) ? 1 : 0,
            isset($_POST['service_nano_ceramic']) ? 1 : 0,
            isset($_POST['service_ppf']) ? 1 : 0,
            isset($_POST['service_wash']) ? 1 : 0,
            isset($_POST['service_deep_cleaning']) ? 1 : 0,
            floatval($_POST['estimated_cost'] ?? 0),
            sanitize($_POST['expected_completion_time'] ?? ''),
            !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null,
            sanitize($_POST['body_technician'] ?? ''),
            sanitize($_POST['paint_technician'] ?? ''),
            sanitize($_POST['pdr_technician'] ?? ''),
            sanitize($_POST['polish_technician'] ?? ''),
            sanitize($_POST['branch_manager'] ?? ''),
            sanitize($_POST['status']),
            $orderId
        ]);
        
        // Handle new before photos
        if (!empty($_FILES['new_before_media']['name'][0])) {
            uploadMedia($db, $orderId, 'before', $_FILES['new_before_media']);
        }
        
        // Handle new after photos
        if (!empty($_FILES['new_after_media']['name'][0])) {
            uploadMedia($db, $orderId, 'after', $_FILES['new_after_media']);
        }
        
        $db->commit();
        $message = 'تم تحديث الطلب بنجاح';
        $messageType = 'success';
        
        // Refresh data
        $stmt = $db->prepare("SELECT j.*, c.name as customer_name, c.phone as customer_phone FROM job_orders j LEFT JOIN customers c ON j.customer_id = c.id WHERE j.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("SELECT * FROM order_photos WHERE order_id = ? ORDER BY photo_type, uploaded_at");
        $stmt->execute([$orderId]);
        $allMedia = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $beforeMedia = array_filter($allMedia, fn($m) => $m['photo_type'] === 'before');
        $afterMedia = array_filter($allMedia, fn($m) => $m['photo_type'] === 'after');
        
    } catch (Exception $e) {
        $db->rollBack();
        $message = 'حدث خطأ: ' . $e->getMessage();
        $messageType = 'error';
    }
}

function uploadMedia($db, $orderId, $type, $files) {
    $uploadDir = UPLOAD_PATH . 'orders/' . $orderId . '/' . $type . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $allowedExts = getAllowedExtensions();
    
    foreach ($files['tmp_name'] as $key => $tmpName) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($files['name'][$key], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) continue;
            if ($files['size'][$key] > MAX_FILE_SIZE) continue;
            
            $fileName = uniqid() . '_' . time() . '.' . $ext;
            $relativePath = 'uploads/orders/' . $orderId . '/' . $type . '/' . $fileName;
            
            if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                $mediaType = isVideo($fileName) ? 'video' : 'image';
                $stmt = $db->prepare("INSERT INTO order_photos (order_id, photo_type, media_type, file_name, file_path, file_size) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$orderId, $type, $mediaType, $fileName, $relativePath, $files['size'][$key]]);
            }
        }
    }
}

global $statusLabels, $sourceLabels;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الطلب - <?= $order['order_number'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        .edit-form{background:#fff;border-radius:16px;box-shadow:var(--shadow);padding:24px;margin-bottom:24px}
        .form-section{margin-bottom:32px}
        .form-section-title{display:flex;align-items:center;gap:10px;font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid var(--gray-200)}
        .form-section-title i{color:var(--accent)}
        .form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:16px}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-weight:600;margin-bottom:8px;color:var(--gray-700)}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:12px;border:2px solid var(--gray-200);border-radius:10px;font-family:inherit;font-size:1rem}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--primary)}
        .checkbox-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
        .checkbox-item{display:flex;align-items:center;gap:10px;padding:10px;background:var(--gray-50);border-radius:8px;cursor:pointer;border:2px solid transparent}
        .checkbox-item.checked{border-color:var(--primary);background:rgba(30,58,95,.05)}
        .checkbox-item input{width:16px;height:16px}
        .media-section{margin-top:20px}
        .media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:10px;margin:12px 0}
        .media-thumb{position:relative;border-radius:8px;overflow:hidden;background:var(--gray-100)}
        .media-thumb-preview{aspect-ratio:1;position:relative}
        .media-thumb img,.media-thumb video{width:100%;height:100%;object-fit:cover}
        .media-thumb .delete-btn{position:absolute;top:4px;right:4px;width:24px;height:24px;background:rgba(239,68,68,.9);border:none;border-radius:50%;color:#fff;cursor:pointer;font-size:.7rem;z-index:2}
        .media-thumb .download-btn{display:flex;align-items:center;justify-content:center;padding:6px;background:var(--primary);color:#fff;text-decoration:none;font-size:.75rem}
        .media-thumb .download-btn:hover{background:var(--primary-light)}
        .upload-box{border:2px dashed var(--gray-300);border-radius:10px;padding:20px;text-align:center;cursor:pointer;background:var(--gray-50)}
        .upload-box:hover{border-color:var(--primary)}
        .upload-box input{display:none}
        .form-actions{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;padding-top:20px;border-top:2px solid var(--gray-200)}
        .status-select{padding:12px 20px;border:2px solid var(--gray-200);border-radius:10px;font-weight:600;font-size:1rem;min-width:180px}
        @media(max-width:640px){.form-actions{flex-direction:column}.form-actions>*{width:100%}}
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="sidebar-overlay"></div>
        <main class="main-content">
            <header class="top-header">
                <div class="page-title">
                    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                    <a href="order_view.php?id=<?= $orderId ?>" style="color:var(--gray-500);margin-left:10px"><i class="fas fa-arrow-right"></i></a>
                    <h2>تعديل الطلب - <?= $order['order_number'] ?></h2>
                </div>
            </header>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $message ?></div>
            <?php endif; ?>
            
            <form method="POST" class="edit-form" enctype="multipart/form-data">
                <!-- Customer & Branch -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-user"></i> بيانات العميل والفرع</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>اسم العميل *</label>
                            <input type="text" name="customer_name" value="<?= htmlspecialchars($order['customer_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>رقم الجوال *</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($order['customer_phone']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>الفرع</label>
                            <?php if (canViewAllBranches()): ?>
                            <select name="branch">
                                <option value="thumama" <?= $order['branch'] === 'thumama' ? 'selected' : '' ?>>فرع الثمامة</option>
                                <option value="rawdah" <?= $order['branch'] === 'rawdah' ? 'selected' : '' ?>>فرع الروضة</option>
                            </select>
                            <?php else: ?>
                            <input type="hidden" name="branch" value="<?= $order['branch'] ?>">
                            <input type="text" value="<?= getBranchName($order['branch']) ?>" readonly style="background:var(--gray-100);cursor:not-allowed">
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>مصدر الزيارة</label>
                            <select name="visit_source">
                                <option value="">-- اختر --</option>
                                <?php foreach ($sourceLabels as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $order['visit_source'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Car Info -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-car"></i> بيانات السيارة</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>نوع السيارة *</label>
                            <input type="text" name="car_type" value="<?= htmlspecialchars($order['car_type']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>الموديل</label>
                            <input type="text" name="car_model" value="<?= htmlspecialchars($order['car_model'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>اللون</label>
                            <input type="text" name="car_color" value="<?= htmlspecialchars($order['car_color'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>رقم اللوحة</label>
                            <input type="text" name="plate_number" value="<?= htmlspecialchars($order['plate_number'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <label style="font-weight:600;margin-bottom:10px;display:block">حالة السيارة</label>
                    <div class="checkbox-grid">
                        <label class="checkbox-item <?= $order['has_dents'] ? 'checked' : '' ?>"><input type="checkbox" name="has_dents" <?= $order['has_dents'] ? 'checked' : '' ?>><span>طعجات</span></label>
                        <label class="checkbox-item <?= $order['has_scratches'] ? 'checked' : '' ?>"><input type="checkbox" name="has_scratches" <?= $order['has_scratches'] ? 'checked' : '' ?>><span>خدوش</span></label>
                        <label class="checkbox-item <?= $order['has_paint_erosion'] ? 'checked' : '' ?>"><input type="checkbox" name="has_paint_erosion" <?= $order['has_paint_erosion'] ? 'checked' : '' ?>><span>تآكل دهان</span></label>
                        <label class="checkbox-item <?= $order['has_previous_polish'] ? 'checked' : '' ?>"><input type="checkbox" name="has_previous_polish" <?= $order['has_previous_polish'] ? 'checked' : '' ?>><span>تلميع سابق</span></label>
                        <label class="checkbox-item <?= $order['has_exterior_mods'] ? 'checked' : '' ?>"><input type="checkbox" name="has_exterior_mods" <?= $order['has_exterior_mods'] ? 'checked' : '' ?>><span>تعديلات خارجية</span></label>
                    </div>
                    <div class="form-group" style="margin-top:16px">
                        <label>ملاحظات</label>
                        <textarea name="condition_details" rows="2"><?= htmlspecialchars($order['condition_details'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <!-- Services -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-tools"></i> الخدمات</div>
                    <div class="checkbox-grid">
                        <label class="checkbox-item <?= $order['service_body_repair'] ? 'checked' : '' ?>"><input type="checkbox" name="service_body_repair" <?= $order['service_body_repair'] ? 'checked' : '' ?>><span>سمكرة</span></label>
                        <label class="checkbox-item <?= $order['service_parts_install'] ? 'checked' : '' ?>"><input type="checkbox" name="service_parts_install" <?= $order['service_parts_install'] ? 'checked' : '' ?>><span>فتح وتركيب</span></label>
                        <label class="checkbox-item <?= $order['service_collision_repair'] ? 'checked' : '' ?>"><input type="checkbox" name="service_collision_repair" <?= $order['service_collision_repair'] ? 'checked' : '' ?>><span>إصلاح صدمات</span></label>
                        <label class="checkbox-item <?= $order['service_single_paint'] ? 'checked' : '' ?>"><input type="checkbox" name="service_single_paint" <?= $order['service_single_paint'] ? 'checked' : '' ?>><span>دهان قطعة</span></label>
                        <label class="checkbox-item <?= $order['service_multi_paint'] ? 'checked' : '' ?>"><input type="checkbox" name="service_multi_paint" <?= $order['service_multi_paint'] ? 'checked' : '' ?>><span>دهان متعدد</span></label>
                        <label class="checkbox-item <?= $order['service_full_spray'] ? 'checked' : '' ?>"><input type="checkbox" name="service_full_spray" <?= $order['service_full_spray'] ? 'checked' : '' ?>><span>رش كامل</span></label>
                        <label class="checkbox-item <?= $order['service_single_dent'] ? 'checked' : '' ?>"><input type="checkbox" name="service_single_dent" <?= $order['service_single_dent'] ? 'checked' : '' ?>><span>PDR طعجة</span></label>
                        <label class="checkbox-item <?= $order['service_multi_dents'] ? 'checked' : '' ?>"><input type="checkbox" name="service_multi_dents" <?= $order['service_multi_dents'] ? 'checked' : '' ?>><span>PDR متعدد</span></label>
                        <label class="checkbox-item <?= $order['service_exterior_polish'] ? 'checked' : '' ?>"><input type="checkbox" name="service_exterior_polish" <?= $order['service_exterior_polish'] ? 'checked' : '' ?>><span>تلميع خارجي</span></label>
                        <label class="checkbox-item <?= $order['service_interior_polish'] ? 'checked' : '' ?>"><input type="checkbox" name="service_interior_polish" <?= $order['service_interior_polish'] ? 'checked' : '' ?>><span>تلميع داخلي</span></label>
                        <label class="checkbox-item <?= $order['service_lights_polish'] ? 'checked' : '' ?>"><input type="checkbox" name="service_lights_polish" <?= $order['service_lights_polish'] ? 'checked' : '' ?>><span>تلميع أنوار</span></label>
                        <label class="checkbox-item <?= $order['service_scratch_treatment'] ? 'checked' : '' ?>"><input type="checkbox" name="service_scratch_treatment" <?= $order['service_scratch_treatment'] ? 'checked' : '' ?>><span>معالجة خدوش</span></label>
                        <label class="checkbox-item <?= $order['service_nano_ceramic'] ? 'checked' : '' ?>"><input type="checkbox" name="service_nano_ceramic" <?= $order['service_nano_ceramic'] ? 'checked' : '' ?>><span>نانو سيراميك</span></label>
                        <label class="checkbox-item <?= $order['service_ppf'] ? 'checked' : '' ?>"><input type="checkbox" name="service_ppf" <?= $order['service_ppf'] ? 'checked' : '' ?>><span>PPF</span></label>
                        <label class="checkbox-item <?= $order['service_wash'] ? 'checked' : '' ?>"><input type="checkbox" name="service_wash" <?= $order['service_wash'] ? 'checked' : '' ?>><span>غسيل</span></label>
                        <label class="checkbox-item <?= $order['service_deep_cleaning'] ? 'checked' : '' ?>"><input type="checkbox" name="service_deep_cleaning" <?= $order['service_deep_cleaning'] ? 'checked' : '' ?>><span>تنظيف عميق</span></label>
                    </div>
                </div>
                
                <!-- Cost & Time -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-calculator"></i> التكلفة والوقت</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>التكلفة (ريال)</label>
                            <input type="number" name="estimated_cost" value="<?= $order['estimated_cost'] ?>" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>الوقت المتوقع</label>
                            <input type="text" name="expected_completion_time" value="<?= htmlspecialchars($order['expected_completion_time'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>موعد التسليم</label>
                            <input type="datetime-local" name="delivery_date" value="<?= $order['delivery_date'] ? date('Y-m-d\TH:i', strtotime($order['delivery_date'])) : '' ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Technicians -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-users-cog"></i> الفنيين</div>
                    <div class="form-row">
                        <div class="form-group"><label>سمكري</label><input type="text" name="body_technician" value="<?= htmlspecialchars($order['body_technician'] ?? '') ?>"></div>
                        <div class="form-group"><label>دهان</label><input type="text" name="paint_technician" value="<?= htmlspecialchars($order['paint_technician'] ?? '') ?>"></div>
                        <div class="form-group"><label>PDR</label><input type="text" name="pdr_technician" value="<?= htmlspecialchars($order['pdr_technician'] ?? '') ?>"></div>
                        <div class="form-group"><label>تلميع</label><input type="text" name="polish_technician" value="<?= htmlspecialchars($order['polish_technician'] ?? '') ?>"></div>
                        <div class="form-group"><label>مدير الفرع</label><input type="text" name="branch_manager" value="<?= htmlspecialchars($order['branch_manager'] ?? '') ?>"></div>
                    </div>
                </div>
                
                <!-- Media -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-camera"></i> الصور والفيديو</div>
                    
                    <h4 style="margin-bottom:10px">صور قبل العمل</h4>
                    <?php if (!empty($beforeMedia)): ?>
                    <div class="media-grid">
                        <?php foreach ($beforeMedia as $m): ?>
                        <div class="media-thumb" data-id="<?= $m['id'] ?>">
                            <div class="media-thumb-preview">
                                <?php if ($m['media_type'] === 'video'): ?>
                                <video src="<?= $m['file_path'] ?>"></video>
                                <?php else: ?>
                                <img src="<?= $m['file_path'] ?>">
                                <?php endif; ?>
                                <button type="button" class="delete-btn" onclick="deleteMedia(<?= $m['id'] ?>, this)"><i class="fas fa-times"></i></button>
                            </div>
                            <a href="api/download_media.php?id=<?= $m['id'] ?>" class="download-btn"><i class="fas fa-download"></i></a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <label class="upload-box">
                        <i class="fas fa-plus"></i> إضافة صور/فيديو قبل
                        <input type="file" name="new_before_media[]" multiple accept="image/*,video/*">
                    </label>
                    
                    <h4 style="margin:20px 0 10px">صور بعد العمل</h4>
                    <?php if (!empty($afterMedia)): ?>
                    <div class="media-grid">
                        <?php foreach ($afterMedia as $m): ?>
                        <div class="media-thumb" data-id="<?= $m['id'] ?>">
                            <div class="media-thumb-preview">
                                <?php if ($m['media_type'] === 'video'): ?>
                                <video src="<?= $m['file_path'] ?>"></video>
                                <?php else: ?>
                                <img src="<?= $m['file_path'] ?>">
                                <?php endif; ?>
                                <button type="button" class="delete-btn" onclick="deleteMedia(<?= $m['id'] ?>, this)"><i class="fas fa-times"></i></button>
                            </div>
                            <a href="api/download_media.php?id=<?= $m['id'] ?>" class="download-btn"><i class="fas fa-download"></i></a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <label class="upload-box">
                        <i class="fas fa-plus"></i> إضافة صور/فيديو بعد
                        <input type="file" name="new_after_media[]" multiple accept="image/*,video/*">
                    </label>
                </div>
                
                <div class="form-actions">
                    <div>
                        <label style="font-weight:600;margin-left:10px">الحالة:</label>
                        <select name="status" class="status-select">
                            <?php foreach ($statusLabels as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $order['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex;gap:12px">
                        <a href="order_view.php?id=<?= $orderId ?>" class="btn btn-outline">إلغاء</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التغييرات</button>
                    </div>
                </div>
            </form>
        </main>
    </div>
    
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
    <script>
        document.querySelectorAll('.checkbox-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const cb = this.querySelector('input');
                    cb.checked = !cb.checked;
                }
                this.classList.toggle('checked', this.querySelector('input').checked);
            });
        });
        
        function deleteMedia(id, btn) {
            if (!confirm('هل أنت متأكد من حذف هذا الملف؟')) return;
            fetch('api/delete_photo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) btn.closest('.media-thumb').remove();
                else alert(data.message);
            });
        }
    </script>
</body>
</html>
