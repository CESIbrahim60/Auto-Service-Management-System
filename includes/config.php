<?php
/**
 * 3WAY Car Service - Configuration File
 * Arabic Only - Fully Responsive System
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '3way_car_service');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', '3WAY');
define('APP_VERSION', '2.0.0');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB for videos

// Branches
define('BRANCHES', [
    'thumama' => 'فرع الثمامة',
    'rawdah' => 'فرع الروضة'
]);

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Riyadh');

// Database Connection Class
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Helper Functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateOrderNumber() {
    return '3W-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function getBranchName($key) {
    return BRANCHES[$key] ?? $key;
}

// Arabic Labels
$labels = [
    'app_title' => '3WAY - نظام إدارة خدمات السيارات',
    'new_order' => 'طلب جديد',
    'orders' => 'الطلبات',
    'dashboard' => 'لوحة التحكم',
    'customer_info' => 'بيانات العميل',
    'customer_name' => 'اسم العميل',
    'phone' => 'رقم الجوال',
    'visit_source' => 'مصدر الزيارة',
    'branch' => 'الفرع',
    'car_info' => 'بيانات السيارة',
    'car_type' => 'نوع السيارة',
    'model' => 'الموديل',
    'color' => 'اللون',
    'plate_number' => 'رقم اللوحة',
    'car_condition' => 'حالة السيارة قبل الاستلام',
    'dents' => 'طعجات',
    'paint_erosion' => 'تآكل دهان',
    'scratches' => 'خدوش',
    'previous_polish' => 'تلميع سابق',
    'exterior_mods' => 'تعديلات خارجية',
    'condition_details' => 'وصف الحالة بالتفصيل',
    'required_services' => 'الخدمة المطلوبة',
    'body_work' => 'أعمال الهيكل والسمكرة',
    'body_repair' => 'سمكرة',
    'parts_install' => 'فتح وتركيب أجزاء',
    'collision_repair' => 'إصلاح صدمات',
    'paint_work' => 'أعمال الدهان',
    'single_paint' => 'دهان قطعة واحدة',
    'multi_paint' => 'دهان أكثر من قطعة',
    'full_spray' => 'رش كامل',
    'pdr_service' => 'PDR - شفط طعجات بدون بوية',
    'single_dent' => 'طعجة واحدة',
    'multi_dents' => 'عدة طعجات',
    'polish_protection' => 'التلميع والحماية',
    'exterior_polish' => 'تلميع خارجي',
    'interior_polish' => 'تلميع داخلي',
    'lights_polish' => 'تلميع الأنوار',
    'scratch_treatment' => 'معالجة الخدوش',
    'nano_ceramic' => 'حماية نانو سيراميك',
    'ppf_protection' => 'PPF حماية واجهات / أجزاء',
    'wash' => 'غسيل',
    'deep_cleaning' => 'تنظيف داخلي عميق',
    'other' => 'أخرى',
    'cost_estimate' => 'التكلفة والوقت',
    'service_cost' => 'تكلفة الخدمة',
    'sar' => 'ريال',
    'expected_time' => 'الوقت المتوقع للإنجاز',
    'delivery_date' => 'موعد التسليم',
    'technician' => 'الفني المسؤول',
    'body_tech' => 'سمكري',
    'paint_tech' => 'دهان',
    'pdr_tech' => 'فني PDR',
    'polish_tech' => 'فني تلميع',
    'branch_manager' => 'مدير الفرع',
    'save' => 'حفظ',
    'cancel' => 'إلغاء',
    'search' => 'بحث',
    'filter' => 'تصفية',
    'all' => 'الكل',
    'pending' => 'قيد الانتظار',
    'in_progress' => 'قيد التنفيذ',
    'completed' => 'مكتمل',
    'delivered' => 'تم التسليم',
    'cancelled' => 'ملغي',
    'total_orders' => 'إجمالي الطلبات',
    'today_orders' => 'طلبات اليوم',
    'revenue' => 'الإيرادات',
    'order_number' => 'رقم الطلب',
    'date' => 'التاريخ',
    'status' => 'الحالة',
    'actions' => 'الإجراءات',
    'view' => 'عرض',
    'edit' => 'تعديل',
    'delete' => 'حذف',
    'print' => 'طباعة',
    'upload_media' => 'رفع الصور والفيديو',
    'before_photos' => 'صور قبل',
    'after_photos' => 'صور بعد',
    'logout' => 'تسجيل الخروج',
    'login' => 'تسجيل الدخول',
    'username' => 'اسم المستخدم',
    'password' => 'كلمة المرور',
    'success_saved' => 'تم الحفظ بنجاح',
    'error_save' => 'حدث خطأ أثناء الحفظ',
    'confirm_delete' => 'هل أنت متأكد من الحذف؟',
    'reports' => 'التقارير',
    'export' => 'تصدير',
    'employees' => 'الموظفين',
    'customers' => 'العملاء',
    'settings' => 'الإعدادات',
    'created_by' => 'أنشئ بواسطة',
    'employee_stats' => 'إحصائيات الموظفين',
    'orders_created' => 'طلبات تم إنشاؤها',
    'orders_completed' => 'طلبات تم إكمالها',
];

function t($key) {
    global $labels;
    return $labels[$key] ?? $key;
}

// Status Labels
$statusLabels = [
    'pending' => 'قيد الانتظار',
    'in_progress' => 'قيد التنفيذ',
    'completed' => 'مكتمل',
    'delivered' => 'تم التسليم',
    'cancelled' => 'ملغي'
];

// Visit Source Labels
$sourceLabels = [
    'tiktok' => 'تيك توك',
    'instagram' => 'إنستجرام',
    'snapchat' => 'سناب شات',
    'google_search' => 'بحث جوجل',
    'google_maps' => 'خرائط جوجل',
    'youtube' => 'يوتيوب',
    'twitter' => 'تويتر',
    'friend_referral' => 'توصية صديق',
    'direct_visit' => 'زيارة مباشرة',
    'returning_customer' => 'عميل قديم',
    'other' => 'أخرى'
];

// Role-based Access Control
function getUserRole() {
    return $_SESSION['role'] ?? 'reception';
}

function isAdmin() {
    return getUserRole() === 'admin';
}

function isManager() {
    return in_array(getUserRole(), ['admin', 'manager']);
}

function isReception() {
    return getUserRole() === 'reception';
}

// Check if user can access a page
function canAccess($page) {
    $role = getUserRole();
    
    // Pages accessible by all authenticated users (reception included)
    $allAccess = ['order_new.php', 'order_view.php', 'edit_order.php', 'print_order.php', 'orders.php', 'logout.php'];
    
    // Pages accessible by managers and admins only
    $managerAccess = ['index.php', 'customers.php', 'settings.php'];
    
    // Admin only pages
    $adminAccess = ['employees.php', 'reports.php'];
    
    if (in_array($page, $allAccess)) {
        return true;
    }
    
    if (in_array($page, $managerAccess) && isManager()) {
        return true;
    }
    
    if (in_array($page, $adminAccess) && isAdmin()) {
        return true;
    }
    
    return false;
}

// Check if user can perform an API action
function canPerformAction($action) {
    $role = getUserRole();
    
    // Actions allowed for all users
    $allActions = ['save_order', 'update_status'];
    
    // Actions for managers and admins
    $managerActions = ['delete_order', 'save_customer', 'delete_customer', 'export_excel'];
    
    // Admin only actions
    $adminActions = ['save_user', 'delete_user'];
    
    if (in_array($action, $allActions)) {
        return true;
    }
    
    if (in_array($action, $managerActions) && isManager()) {
        return true;
    }
    
    if (in_array($action, $adminActions) && isAdmin()) {
        return true;
    }
    
    return false;
}

// Redirect if no access - goes to 404 page
function requireAccess($page) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    if (!canAccess($page)) {
        header('Location: 404.php');
        exit;
    }
}

// Get home page based on role
function getHomePage() {
    if (isManager()) {
        return 'index.php';
    }
    return 'orders.php';
}

// Get allowed file types for upload
function getAllowedMediaTypes() {
    return [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'
    ];
}

function getAllowedExtensions() {
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg', 'mov', 'avi'];
}

function isVideo($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi']);
}

// Get user's assigned branch
function getUserBranch() {
    return $_SESSION['branch'] ?? null;
}

// Check if user can view all branches (admin only)
function canViewAllBranches() {
    return isAdmin();
}

// Get branch filter for queries - returns user's branch or null for admin
function getBranchFilter() {
    if (isAdmin()) {
        return null; // Admin can see all
    }
    return getUserBranch(); // Others see only their branch
}
?>
