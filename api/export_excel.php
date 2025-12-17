<?php
/**
 * 3WAY Car Service - Export Orders to Excel (CSV)
 */
require_once '../includes/config.php';

session_start();
if (!isset($_SESSION['user_id']) || !canPerformAction('export_excel')) {
    header('HTTP/1.1 403 Forbidden');
    exit(getLang() === 'ar' ? 'غير مصرح لك بهذا الإجراء' : 'Access denied');
}

$currentLang = getLang();
$db = Database::getInstance()->getConnection();

// Get filter parameters
$status = sanitize($_GET['status'] ?? '');
$fromDate = sanitize($_GET['from_date'] ?? '');
$toDate = sanitize($_GET['to_date'] ?? '');
$search = sanitize($_GET['search'] ?? '');

// Build query
$where = "WHERE 1=1";
$params = [];

if ($status) {
    $where .= " AND j.status = ?";
    $params[] = $status;
}

if ($fromDate) {
    $where .= " AND DATE(j.created_at) >= ?";
    $params[] = $fromDate;
}

if ($toDate) {
    $where .= " AND DATE(j.created_at) <= ?";
    $params[] = $toDate;
}

if ($search) {
    $where .= " AND (j.order_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Fetch orders
$sql = "SELECT 
    j.order_number,
    c.name as customer_name,
    c.phone as customer_phone,
    j.visit_source,
    j.car_type,
    j.car_model,
    j.car_color,
    j.plate_number,
    j.estimated_cost,
    j.status,
    j.expected_completion_time,
    j.delivery_date,
    j.body_technician,
    j.paint_technician,
    j.pdr_technician,
    j.polish_technician,
    j.created_at,
    j.service_body_repair,
    j.service_parts_install,
    j.service_collision_repair,
    j.service_single_paint,
    j.service_multi_paint,
    j.service_full_spray,
    j.service_single_dent,
    j.service_multi_dents,
    j.service_exterior_polish,
    j.service_interior_polish,
    j.service_nano_ceramic,
    j.service_ppf,
    j.service_wash,
    j.service_deep_cleaning
FROM job_orders j
LEFT JOIN customers c ON j.customer_id = c.id
$where
ORDER BY j.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status labels
$statusLabels = [
    'pending' => $currentLang === 'ar' ? 'قيد الانتظار' : 'Pending',
    'in_progress' => $currentLang === 'ar' ? 'قيد التنفيذ' : 'In Progress',
    'completed' => $currentLang === 'ar' ? 'مكتمل' : 'Completed',
    'delivered' => $currentLang === 'ar' ? 'تم التسليم' : 'Delivered',
    'cancelled' => $currentLang === 'ar' ? 'ملغي' : 'Cancelled'
];

$sourceLabels = [
    'tiktok' => 'TikTok',
    'instagram' => 'Instagram',
    'snapchat' => 'Snapchat',
    'google_search' => $currentLang === 'ar' ? 'بحث جوجل' : 'Google Search',
    'friend_referral' => $currentLang === 'ar' ? 'توصية صديق' : 'Friend Referral',
    'google_maps' => $currentLang === 'ar' ? 'خرائط جوجل' : 'Google Maps',
    'youtube' => 'YouTube',
    'direct_visit' => $currentLang === 'ar' ? 'زيارة مباشرة' : 'Direct Visit',
    'twitter' => 'Twitter'
];

// Generate filename
$filename = '3way_orders_' . date('Y-m-d_H-i-s') . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = $currentLang === 'ar' ? [
    'رقم الطلب',
    'اسم العميل',
    'رقم الجوال',
    'مصدر الزيارة',
    'نوع السيارة',
    'الموديل',
    'اللون',
    'رقم اللوحة',
    'التكلفة',
    'الحالة',
    'الوقت المتوقع',
    'موعد التسليم',
    'سمكري',
    'دهان',
    'فني PDR',
    'فني تلميع',
    'الخدمات',
    'تاريخ الإنشاء'
] : [
    'Order Number',
    'Customer Name',
    'Phone',
    'Visit Source',
    'Car Type',
    'Model',
    'Color',
    'Plate Number',
    'Cost',
    'Status',
    'Expected Time',
    'Delivery Date',
    'Body Tech',
    'Paint Tech',
    'PDR Tech',
    'Polish Tech',
    'Services',
    'Created At'
];

fputcsv($output, $headers);

// Write data rows
foreach ($orders as $order) {
    // Collect services
    $services = [];
    if ($order['service_body_repair']) $services[] = $currentLang === 'ar' ? 'سمكرة' : 'Body';
    if ($order['service_parts_install']) $services[] = $currentLang === 'ar' ? 'فتح وتركيب' : 'Parts';
    if ($order['service_collision_repair']) $services[] = $currentLang === 'ar' ? 'صدمات' : 'Collision';
    if ($order['service_single_paint']) $services[] = $currentLang === 'ar' ? 'دهان قطعة' : 'Single Paint';
    if ($order['service_multi_paint']) $services[] = $currentLang === 'ar' ? 'دهان متعدد' : 'Multi Paint';
    if ($order['service_full_spray']) $services[] = $currentLang === 'ar' ? 'رش كامل' : 'Full Spray';
    if ($order['service_single_dent']) $services[] = 'PDR';
    if ($order['service_multi_dents']) $services[] = 'PDR Multi';
    if ($order['service_exterior_polish']) $services[] = $currentLang === 'ar' ? 'تلميع خارجي' : 'Exterior Polish';
    if ($order['service_interior_polish']) $services[] = $currentLang === 'ar' ? 'تلميع داخلي' : 'Interior Polish';
    if ($order['service_nano_ceramic']) $services[] = $currentLang === 'ar' ? 'نانو' : 'Nano';
    if ($order['service_ppf']) $services[] = 'PPF';
    if ($order['service_wash']) $services[] = $currentLang === 'ar' ? 'غسيل' : 'Wash';
    if ($order['service_deep_cleaning']) $services[] = $currentLang === 'ar' ? 'تنظيف عميق' : 'Deep Clean';
    
    $row = [
        $order['order_number'],
        $order['customer_name'],
        $order['customer_phone'],
        $sourceLabels[$order['visit_source']] ?? $order['visit_source'] ?? '',
        $order['car_type'],
        $order['car_model'] ?? '',
        $order['car_color'] ?? '',
        $order['plate_number'] ?? '',
        $order['estimated_cost'],
        $statusLabels[$order['status']] ?? $order['status'],
        $order['expected_completion_time'] ?? '',
        $order['delivery_date'] ? date('d/m/Y', strtotime($order['delivery_date'])) : '',
        $order['body_technician'] ?? '',
        $order['paint_technician'] ?? '',
        $order['pdr_technician'] ?? '',
        $order['polish_technician'] ?? '',
        implode(', ', $services),
        date('d/m/Y H:i', strtotime($order['created_at']))
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit;
