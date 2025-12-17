<?php
/**
 * 3WAY Car Service - Print Order (Arabic Only)
 */
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$db = Database::getInstance()->getConnection();
$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) exit('طلب غير صالح');

$stmt = $db->prepare("SELECT j.*, c.name as customer_name, c.phone as customer_phone FROM job_orders j LEFT JOIN customers c ON j.customer_id = c.id WHERE j.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) exit('الطلب غير موجود');

// Check access restrictions
if (isReception()) {
    // Reception can only print their own orders
    if ($order['created_by'] != $_SESSION['user_id']) {
        header('Location: 404.php');
        exit;
    }
} else {
    // Manager can only print orders from their branch
    $userBranch = getBranchFilter();
    if ($userBranch && $order['branch'] !== $userBranch) {
        header('Location: 404.php');
        exit;
    }
}

global $statusLabels;

$services = [];
if ($order['service_body_repair']) $services[] = 'سمكرة';
if ($order['service_parts_install']) $services[] = 'فتح وتركيب';
if ($order['service_collision_repair']) $services[] = 'إصلاح صدمات';
if ($order['service_single_paint']) $services[] = 'دهان قطعة';
if ($order['service_multi_paint']) $services[] = 'دهان متعدد';
if ($order['service_full_spray']) $services[] = 'رش كامل';
if ($order['service_single_dent']) $services[] = 'PDR طعجة';
if ($order['service_multi_dents']) $services[] = 'PDR متعدد';
if ($order['service_exterior_polish']) $services[] = 'تلميع خارجي';
if ($order['service_interior_polish']) $services[] = 'تلميع داخلي';
if ($order['service_nano_ceramic']) $services[] = 'نانو سيراميك';
if ($order['service_ppf']) $services[] = 'PPF';
if ($order['service_wash']) $services[] = 'غسيل';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة - <?= $order['order_number'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Tajawal',sans-serif;padding:20px;max-width:800px;margin:0 auto;font-size:14px}
        .header{text-align:center;margin-bottom:30px;padding-bottom:20px;border-bottom:3px solid #1e3a5f}
        .logo{font-size:2rem;font-weight:700;color:#1e3a5f}
        .logo span{color:#f59e0b}
        .order-info{display:flex;justify-content:space-between;margin-bottom:20px}
        .order-number{font-size:1.3rem;font-weight:700}
        .section{margin-bottom:20px;padding:15px;border:1px solid #ddd;border-radius:8px}
        .section-title{font-weight:700;color:#1e3a5f;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #eee}
        .row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6}
        .row:last-child{border-bottom:none}
        .label{color:#666}
        .value{font-weight:600}
        .services{display:flex;flex-wrap:wrap;gap:8px}
        .service-tag{background:#1e3a5f;color:#fff;padding:4px 10px;border-radius:15px;font-size:12px}
        .cost{font-size:1.5rem;font-weight:700;color:#10b981;text-align:center;padding:15px}
        .signatures{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:40px}
        .sig-box{text-align:center;padding-top:50px;border-top:1px solid #333}
        .print-btn{position:fixed;top:20px;left:20px;padding:10px 20px;background:#1e3a5f;color:#fff;border:none;border-radius:8px;cursor:pointer}
        @media print{.print-btn{display:none}}
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> طباعة</button>
    
    <div class="header">
        <div class="logo">3<span>WAY</span></div>
        <p>نظام إدارة خدمات السيارات</p>
    </div>
    
    <div class="order-info">
        <div>
            <div class="order-number"><?= $order['order_number'] ?></div>
            <div><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
        </div>
        <div>
            <strong><?= getBranchName($order['branch']) ?></strong><br>
            <span style="background:<?= $order['status'] === 'completed' ? '#10b981' : '#f59e0b' ?>;color:#fff;padding:4px 12px;border-radius:15px"><?= $statusLabels[$order['status']] ?></span>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">بيانات العميل</div>
        <div class="row"><span class="label">الاسم</span><span class="value"><?= htmlspecialchars($order['customer_name']) ?></span></div>
        <div class="row"><span class="label">الجوال</span><span class="value"><?= $order['customer_phone'] ?></span></div>
    </div>
    
    <div class="section">
        <div class="section-title">بيانات السيارة</div>
        <div class="row"><span class="label">النوع</span><span class="value"><?= htmlspecialchars($order['car_type']) ?></span></div>
        <div class="row"><span class="label">الموديل</span><span class="value"><?= $order['car_model'] ?: '-' ?></span></div>
        <div class="row"><span class="label">اللون</span><span class="value"><?= $order['car_color'] ?: '-' ?></span></div>
        <div class="row"><span class="label">اللوحة</span><span class="value"><?= $order['plate_number'] ?: '-' ?></span></div>
    </div>
    
    <div class="section">
        <div class="section-title">الخدمات المطلوبة</div>
        <div class="services">
            <?php foreach ($services as $s): ?><span class="service-tag"><?= $s ?></span><?php endforeach; ?>
            <?php if (empty($services)): ?><span style="color:#999">لا توجد خدمات</span><?php endif; ?>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">التكلفة</div>
        <div class="cost"><?= number_format($order['estimated_cost']) ?> ريال</div>
        <div class="row"><span class="label">الوقت المتوقع</span><span class="value"><?= $order['expected_completion_time'] ?: '-' ?></span></div>
        <div class="row"><span class="label">موعد التسليم</span><span class="value"><?= $order['delivery_date'] ? date('d/m/Y', strtotime($order['delivery_date'])) : '-' ?></span></div>
    </div>
    
    <div class="signatures">
        <div class="sig-box">توقيع العميل</div>
        <div class="sig-box">توقيع الاستقبال</div>
        <div class="sig-box">توقيع المدير</div>
    </div>
</body>
</html>
