<?php
/**
 * 3WAY Car Service - Orders List (Arabic Only)
 */
require_once 'includes/config.php';
requireAccess('orders.php');

$db = Database::getInstance()->getConnection();

// Get filters
$status = $_GET['status'] ?? '';
$branch = $_GET['branch'] ?? '';
$search = $_GET['search'] ?? '';

// Get user's branch filter - non-admin users can only see their branch
$userBranch = getBranchFilter();

// Build query
$where = [];
$params = [];

// Reception can only see their own orders
if (isReception()) {
    $where[] = "j.created_by = ?";
    $params[] = $_SESSION['user_id'];
} else {
    // Apply user branch restriction (manager users)
    if ($userBranch) {
        $where[] = "j.branch = ?";
        $params[] = $userBranch;
        $branch = $userBranch;
    } elseif ($branch && in_array($branch, ['thumama', 'rawdah'])) {
        // Admin can filter by branch
        $where[] = "j.branch = ?";
        $params[] = $branch;
    }
}

if ($status && in_array($status, ['pending', 'in_progress', 'completed', 'delivered', 'cancelled'])) {
    $where[] = "j.status = ?";
    $params[] = $status;
}

if ($search) {
    $where[] = "(j.order_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR j.car_type LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

$whereClause = $where ? " WHERE " . implode(" AND ", $where) : "";

// Get orders
$query = "SELECT j.*, c.name as customer_name, c.phone as customer_phone, u.full_name as created_by_name 
          FROM job_orders j 
          LEFT JOIN customers c ON j.customer_id = c.id 
          LEFT JOIN users u ON j.created_by = u.id
          $whereClause
          ORDER BY j.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get counts by status
if (isReception()) {
    $countWhere = " WHERE created_by = ?";
    $countParams = [$_SESSION['user_id']];
} elseif ($userBranch) {
    $countWhere = " WHERE branch = ?";
    $countParams = [$userBranch];
} else {
    $countWhere = "";
    $countParams = [];
}

$countQuery = "SELECT status, COUNT(*) as count FROM job_orders $countWhere GROUP BY status";
$stmt = $db->prepare($countQuery);
$stmt->execute($countParams);
$statusCounts = [];
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
$totalOrders = array_sum($statusCounts);

global $statusLabels;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الطلبات - 3WAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        .filters-bar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; align-items: center; }
        .search-box { flex: 1; min-width: 250px; position: relative; }
        .search-box input { width: 100%; padding: 12px 16px; padding-right: 45px; border: 2px solid #e5e7eb; border-radius: 10px; font-family: inherit; font-size: 1rem; }
        .search-box input:focus { outline: none; border-color: #1e3a5f; }
        .search-box i { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .filter-select { padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-family: inherit; min-width: 150px; }
        .status-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .status-tab { padding: 10px 18px; border-radius: 25px; font-weight: 600; text-decoration: none; color: #4b5563; background: #f3f4f6; transition: all 0.2s; display: flex; align-items: center; gap: 8px; }
        .status-tab:hover { background: #e5e7eb; }
        .status-tab.active { background: #1e3a5f; color: #fff; }
        .status-tab .count { background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
        .status-tab.active .count { background: rgba(255,255,255,0.3); }
        .orders-table { width: 100%; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .orders-table th, .orders-table td { padding: 16px; text-align: right; border-bottom: 1px solid #e5e7eb; }
        .orders-table th { background: #f9fafb; font-weight: 700; color: #1e3a5f; white-space: nowrap; }
        .orders-table tr:hover { background: #f9fafb; }
        .order-number { font-weight: 700; color: #1e3a5f; }
        .customer-info { display: flex; align-items: center; gap: 10px; }
        .customer-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #1e3a5f, #2c5282); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.9rem; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-badge.pending { background: rgba(245,158,11,0.1); color: #d97706; }
        .status-badge.in_progress { background: rgba(59,130,246,0.1); color: #2563eb; }
        .status-badge.completed { background: rgba(16,185,129,0.1); color: #059669; }
        .status-badge.delivered { background: rgba(139,92,246,0.1); color: #7c3aed; }
        .status-badge.cancelled { background: rgba(239,68,68,0.1); color: #dc2626; }
        .branch-badge { font-size: 0.75rem; padding: 4px 8px; background: #f3f4f6; border-radius: 6px; color: #4b5563; }
        .action-btns { display: flex; gap: 6px; }
        .btn-icon { width: 34px; height: 34px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none; }
        .btn-icon.view { background: #dbeafe; color: #2563eb; }
        .btn-icon.edit { background: #fef3c7; color: #d97706; }
        .btn-icon.print { background: #d1fae5; color: #059669; }
        .btn-icon.delete { background: #fee2e2; color: #dc2626; }
        .btn-icon:hover { transform: scale(1.1); }
        .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
        .empty-state i { font-size: 4rem; margin-bottom: 16px; }
        .created-by { font-size: 0.75rem; color: #6b7280; margin-top: 4px; }
        @media (max-width: 1024px) {
            .orders-table { display: block; overflow-x: auto; }
        }
        @media (max-width: 640px) {
            .filters-bar { flex-direction: column; }
            .search-box { width: 100%; }
            .filter-select { width: 100%; }
        }
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
                    <i class="fas fa-clipboard-list"></i>
                    <h2>الطلبات</h2>
                </div>
                <div class="header-actions">
                    <a href="order_new.php" class="btn btn-primary"><i class="fas fa-plus"></i> طلب جديد</a>
                </div>
            </header>
            
            <!-- Filters -->
            <form class="filters-bar" method="GET">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="بحث برقم الطلب، اسم العميل، الجوال..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <?php if (canViewAllBranches()): ?>
                <select name="branch" class="filter-select" onchange="this.form.submit()">
                    <option value="">جميع الفروع</option>
                    <option value="thumama" <?= $branch === 'thumama' ? 'selected' : '' ?>>فرع الثمامة</option>
                    <option value="rawdah" <?= $branch === 'rawdah' ? 'selected' : '' ?>>فرع الروضة</option>
                </select>
                <?php else: ?>
                <span style="padding:12px 16px;background:var(--gray-100);border-radius:10px;font-weight:600">
                    <i class="fas fa-building"></i> <?= getBranchName($userBranch) ?>
                </span>
                <?php endif; ?>
                <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i> بحث</button>
            </form>
            
            <!-- Status Tabs -->
            <div class="status-tabs">
                <a href="orders.php<?= $branch ? "?branch=$branch" : '' ?>" class="status-tab <?= !$status ? 'active' : '' ?>">
                    الكل <span class="count"><?= $totalOrders ?></span>
                </a>
                <a href="orders.php?status=pending<?= $branch ? "&branch=$branch" : '' ?>" class="status-tab <?= $status === 'pending' ? 'active' : '' ?>">
                    قيد الانتظار <span class="count"><?= $statusCounts['pending'] ?? 0 ?></span>
                </a>
                <a href="orders.php?status=in_progress<?= $branch ? "&branch=$branch" : '' ?>" class="status-tab <?= $status === 'in_progress' ? 'active' : '' ?>">
                    قيد التنفيذ <span class="count"><?= $statusCounts['in_progress'] ?? 0 ?></span>
                </a>
                <a href="orders.php?status=completed<?= $branch ? "&branch=$branch" : '' ?>" class="status-tab <?= $status === 'completed' ? 'active' : '' ?>">
                    مكتمل <span class="count"><?= $statusCounts['completed'] ?? 0 ?></span>
                </a>
                <a href="orders.php?status=delivered<?= $branch ? "&branch=$branch" : '' ?>" class="status-tab <?= $status === 'delivered' ? 'active' : '' ?>">
                    تم التسليم <span class="count"><?= $statusCounts['delivered'] ?? 0 ?></span>
                </a>
            </div>
            
            <!-- Orders Table -->
            <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>لا توجد طلبات</h3>
                <p>لم يتم العثور على طلبات تطابق معايير البحث</p>
            </div>
            <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>السيارة</th>
                        <th>الفرع</th>
                        <th>التكلفة</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <span class="order-number"><?= htmlspecialchars($order['order_number']) ?></span>
                            <div class="created-by"><i class="fas fa-user"></i> <?= htmlspecialchars($order['created_by_name'] ?? 'غير معروف') ?></div>
                        </td>
                        <td>
                            <div class="customer-info">
                                <div class="customer-avatar"><?= mb_substr($order['customer_name'] ?? 'ع', 0, 1) ?></div>
                                <div>
                                    <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
                                    <small style="display:block;color:#6b7280;"><?= $order['customer_phone'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($order['car_type']) ?></strong>
                            <?php if ($order['plate_number']): ?>
                            <small style="display:block;color:#6b7280;"><?= $order['plate_number'] ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="branch-badge"><?= getBranchName($order['branch']) ?></span></td>
                        <td><strong><?= number_format($order['estimated_cost']) ?></strong> ريال</td>
                        <td><span class="status-badge <?= $order['status'] ?>"><?= $statusLabels[$order['status']] ?? $order['status'] ?></span></td>
                        <td><?= date('d/m/Y', strtotime($order['created_at'])) ?><br><small style="color:#6b7280;"><?= date('H:i', strtotime($order['created_at'])) ?></small></td>
                        <td>
                            <div class="action-btns">
                                <a href="order_view.php?id=<?= $order['id'] ?>" class="btn-icon view" title="عرض"><i class="fas fa-eye"></i></a>
                                <a href="edit_order.php?id=<?= $order['id'] ?>" class="btn-icon edit" title="تعديل"><i class="fas fa-edit"></i></a>
                                <a href="print_order.php?id=<?= $order['id'] ?>" target="_blank" class="btn-icon print" title="طباعة"><i class="fas fa-print"></i></a>
                                <?php if (isManager()): ?>
                                <button type="button" class="btn-icon delete" title="حذف" onclick="deleteOrder(<?= $order['id'] ?>)"><i class="fas fa-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
    <script>
        function deleteOrder(id) {
            if (!confirm('هل أنت متأكد من حذف هذا الطلب؟')) return;
            
            fetch('api/delete_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'حدث خطأ');
                }
            })
            .catch(() => alert('حدث خطأ في الاتصال'));
        }
    </script>
</body>
</html>
