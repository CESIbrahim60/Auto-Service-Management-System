<?php
/**
 * 3WAY Car Service - Customers (Arabic Only)
 */
require_once 'includes/config.php';
requireAccess('customers.php');

$db = Database::getInstance()->getConnection();

$search = $_GET['search'] ?? '';
$message = '';
$messageType = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (isManager()) {
        try {
            $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->execute([intval($_POST['delete_id'])]);
            $message = 'تم حذف العميل بنجاح';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'لا يمكن حذف العميل - قد يكون لديه طلبات مرتبطة';
            $messageType = 'error';
        }
    }
}

// Get customers
$where = '';
$params = [];
if ($search) {
    $where = " WHERE name LIKE ? OR phone LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$stmt = $db->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM job_orders WHERE customer_id = c.id) as orders_count,
    (SELECT COALESCE(SUM(estimated_cost), 0) FROM job_orders WHERE customer_id = c.id) as total_spent
    FROM customers c $where ORDER BY c.created_at DESC");
$stmt->execute($params);
$customers = $stmt->fetchAll();

global $sourceLabels;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>العملاء - 3WAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        .search-bar{display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap}
        .search-box{flex:1;min-width:250px;position:relative}
        .search-box input{width:100%;padding:12px 16px;padding-right:45px;border:2px solid var(--gray-200);border-radius:10px;font-family:inherit;font-size:1rem}
        .search-box i{position:absolute;right:16px;top:50%;transform:translateY(-50%);color:var(--gray-400)}
        .customers-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px}
        .customer-card{background:#fff;border-radius:16px;box-shadow:var(--shadow);overflow:hidden;transition:transform .2s}
        .customer-card:hover{transform:translateY(-4px)}
        .customer-header{padding:20px;display:flex;align-items:center;gap:16px;border-bottom:1px solid var(--gray-100)}
        .customer-avatar{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;font-weight:700}
        .customer-info h4{margin:0 0 4px;color:var(--primary);font-size:1.1rem}
        .customer-info p{margin:0;color:var(--gray-500);font-size:.9rem}
        .customer-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;padding:16px 20px}
        .stat{text-align:center;padding:12px;background:var(--gray-50);border-radius:10px}
        .stat .value{font-size:1.3rem;font-weight:700;color:var(--primary)}
        .stat .label{font-size:.8rem;color:var(--gray-500);margin-top:4px}
        .customer-footer{padding:12px 20px;border-top:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center}
        .source-badge{padding:4px 10px;background:var(--gray-100);border-radius:15px;font-size:.8rem;color:var(--gray-600)}
        .customer-actions{display:flex;gap:8px}
        .btn-icon{width:32px;height:32px;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center}
        .btn-icon.view{background:rgba(59,130,246,.1);color:var(--info)}
        .btn-icon.delete{background:rgba(239,68,68,.1);color:var(--danger)}
        .empty-state{text-align:center;padding:60px 20px;color:var(--gray-400)}
        .empty-state i{font-size:4rem;margin-bottom:16px}
        @media(max-width:640px){.customers-grid{grid-template-columns:1fr}}
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
                    <i class="fas fa-users"></i>
                    <h2>العملاء</h2>
                </div>
                <div class="header-actions">
                    <span style="background:var(--gray-100);padding:8px 16px;border-radius:8px;font-weight:600">
                        <?= count($customers) ?> عميل
                    </span>
                </div>
            </header>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $message ?></div>
            <?php endif; ?>
            
            <form class="search-bar" method="GET">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="بحث باسم العميل أو رقم الجوال..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
                <?php if ($search): ?>
                <a href="customers.php" class="btn btn-outline">إلغاء البحث</a>
                <?php endif; ?>
            </form>
            
            <?php if (empty($customers)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>لا يوجد عملاء</h3>
                <p>لم يتم العثور على عملاء <?= $search ? 'تطابق معايير البحث' : '' ?></p>
            </div>
            <?php else: ?>
            <div class="customers-grid">
                <?php foreach ($customers as $customer): ?>
                <div class="customer-card">
                    <div class="customer-header">
                        <div class="customer-avatar"><?= mb_substr($customer['name'], 0, 1) ?></div>
                        <div class="customer-info">
                            <h4><?= htmlspecialchars($customer['name']) ?></h4>
                            <p><i class="fas fa-phone"></i> <?= $customer['phone'] ?></p>
                        </div>
                    </div>
                    <div class="customer-stats">
                        <div class="stat">
                            <div class="value"><?= $customer['orders_count'] ?></div>
                            <div class="label">الطلبات</div>
                        </div>
                        <div class="stat">
                            <div class="value"><?= number_format($customer['total_spent']) ?></div>
                            <div class="label">إجمالي الإنفاق</div>
                        </div>
                    </div>
                    <div class="customer-footer">
                        <span class="source-badge">
                            <i class="fas fa-bullhorn"></i> <?= $sourceLabels[$customer['visit_source']] ?? 'غير محدد' ?>
                        </span>
                        <div class="customer-actions">
                            <a href="orders.php?search=<?= urlencode($customer['phone']) ?>" class="btn-icon view" title="عرض الطلبات"><i class="fas fa-eye"></i></a>
                            <?php if (isManager()): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا العميل؟')">
                                <input type="hidden" name="delete_id" value="<?= $customer['id'] ?>">
                                <button type="submit" class="btn-icon delete" title="حذف"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
