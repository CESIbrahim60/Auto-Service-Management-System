<?php
/**
 * 3WAY Car Service - Dashboard (Arabic Only)
 */
require_once 'includes/config.php';
requireAccess('index.php');

$db = Database::getInstance()->getConnection();

// Get user's branch filter - non-admin users see only their branch
$userBranch = getBranchFilter();

// Get filter from URL (only admin can use this)
$branchFilter = $_GET['branch'] ?? '';

// Apply user branch restriction (non-admin users)
if ($userBranch) {
    $branchFilter = $userBranch; // Override any URL filter
}

// Build WHERE clause for branch filter
$whereClause = '';
$params = [];
if ($branchFilter && in_array($branchFilter, ['thumama', 'rawdah'])) {
    $whereClause = " WHERE branch = ?";
    $params[] = $branchFilter;
}

// Get statistics from database
$stats = [];

// Total orders
$stmt = $db->prepare("SELECT COUNT(*) FROM job_orders" . $whereClause);
$stmt->execute($params);
$stats['total_orders'] = $stmt->fetchColumn();

// Today's orders
$todayWhere = $whereClause ? $whereClause . " AND DATE(created_at) = CURDATE()" : " WHERE DATE(created_at) = CURDATE()";
$stmt = $db->prepare("SELECT COUNT(*) FROM job_orders" . $todayWhere);
$stmt->execute($params);
$stats['today_orders'] = $stmt->fetchColumn();

// Pending orders
$pendingWhere = $whereClause ? $whereClause . " AND status = 'pending'" : " WHERE status = 'pending'";
$stmt = $db->prepare("SELECT COUNT(*) FROM job_orders" . $pendingWhere);
$stmt->execute($params);
$stats['pending_orders'] = $stmt->fetchColumn();

// In progress orders
$progressWhere = $whereClause ? $whereClause . " AND status = 'in_progress'" : " WHERE status = 'in_progress'";
$stmt = $db->prepare("SELECT COUNT(*) FROM job_orders" . $progressWhere);
$stmt->execute($params);
$stats['in_progress_orders'] = $stmt->fetchColumn();

// Completed orders
$completedWhere = $whereClause ? $whereClause . " AND status IN ('completed', 'delivered')" : " WHERE status IN ('completed', 'delivered')";
$stmt = $db->prepare("SELECT COUNT(*) FROM job_orders" . $completedWhere);
$stmt->execute($params);
$stats['completed_orders'] = $stmt->fetchColumn();

// Total revenue
$stmt = $db->prepare("SELECT COALESCE(SUM(estimated_cost), 0) FROM job_orders" . $whereClause);
$stmt->execute($params);
$stats['total_revenue'] = $stmt->fetchColumn();

// Recent orders
$recentQuery = "SELECT j.*, c.name as customer_name, c.phone as customer_phone, u.full_name as created_by_name 
                FROM job_orders j 
                LEFT JOIN customers c ON j.customer_id = c.id 
                LEFT JOIN users u ON j.created_by = u.id" . 
                ($branchFilter ? " WHERE j.branch = ?" : "") . 
                " ORDER BY j.created_at DESC LIMIT 10";
$stmt = $db->prepare($recentQuery);
$stmt->execute($branchFilter ? [$branchFilter] : []);
$recentOrders = $stmt->fetchAll();

// Orders by status for chart
$statusQuery = "SELECT status, COUNT(*) as count FROM job_orders" . $whereClause . " GROUP BY status";
$stmt = $db->prepare($statusQuery);
$stmt->execute($params);
$statusData = $stmt->fetchAll();

// Top employees by orders created
$employeeQuery = "SELECT u.id, u.full_name, u.branch, 
                  COUNT(j.id) as orders_created,
                  SUM(CASE WHEN j.status IN ('completed', 'delivered') THEN 1 ELSE 0 END) as orders_completed
                  FROM users u 
                  LEFT JOIN job_orders j ON u.id = j.created_by
                  WHERE u.role IN ('reception', 'manager')
                  GROUP BY u.id 
                  ORDER BY orders_created DESC LIMIT 5";
$stmt = $db->query($employeeQuery);
$topEmployees = $stmt->fetchAll();

global $statusLabels;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - 3WAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; right: 0; width: 100px; height: 100px; border-radius: 50%; opacity: 0.1; transform: translate(30%, -30%); }
        .stat-card.primary::before { background: #1e3a5f; }
        .stat-card.success::before { background: #10b981; }
        .stat-card.warning::before { background: #f59e0b; }
        .stat-card.info::before { background: #3b82f6; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .stat-card.primary .stat-icon { background: rgba(30,58,95,0.1); color: #1e3a5f; }
        .stat-card.success .stat-icon { background: rgba(16,185,129,0.1); color: #10b981; }
        .stat-card.warning .stat-icon { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .stat-card.info .stat-icon { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #1e3a5f; margin-bottom: 4px; }
        .stat-label { color: #6b7280; font-size: 0.9rem; }
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 1.1rem; font-weight: 700; color: #1e3a5f; display: flex; align-items: center; gap: 10px; }
        .card-title i { color: #f59e0b; }
        .card-body { padding: 20px; }
        .order-item { display: flex; align-items: center; justify-content: space-between; padding: 16px; border-radius: 12px; background: #f9fafb; margin-bottom: 12px; transition: all 0.2s; }
        .order-item:hover { background: #f3f4f6; }
        .order-info h4 { font-weight: 600; color: #1e3a5f; margin-bottom: 4px; }
        .order-info p { color: #6b7280; font-size: 0.85rem; }
        .order-meta { text-align: left; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-badge.pending { background: rgba(245,158,11,0.1); color: #d97706; }
        .status-badge.in_progress { background: rgba(59,130,246,0.1); color: #2563eb; }
        .status-badge.completed { background: rgba(16,185,129,0.1); color: #059669; }
        .status-badge.delivered { background: rgba(139,92,246,0.1); color: #7c3aed; }
        .employee-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 10px; margin-bottom: 8px; }
        .employee-item:hover { background: #f9fafb; }
        .employee-avatar { width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #1e3a5f, #2c5282); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; }
        .employee-info { flex: 1; }
        .employee-info h4 { font-weight: 600; color: #1e3a5f; font-size: 0.95rem; }
        .employee-info p { color: #6b7280; font-size: 0.8rem; }
        .employee-stats { text-align: left; }
        .employee-stats span { display: block; font-size: 0.85rem; }
        .branch-filter { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-btn { padding: 10px 20px; border: 2px solid #e5e7eb; background: #fff; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; color: #4b5563; }
        .filter-btn:hover, .filter-btn.active { border-color: #1e3a5f; background: #1e3a5f; color: #fff; }
        @media (max-width: 1024px) { .dashboard-grid { grid-template-columns: 1fr; } }
        @media (max-width: 640px) { 
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .stat-card { padding: 16px; }
            .stat-value { font-size: 1.5rem; }
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
                    <i class="fas fa-chart-pie"></i>
                    <h2>لوحة التحكم</h2>
                </div>
                <div class="header-actions">
                    <a href="order_new.php" class="btn btn-primary"><i class="fas fa-plus"></i> طلب جديد</a>
                </div>
            </header>
            
            <!-- Branch Filter -->
            <?php if (canViewAllBranches()): ?>
            <div class="branch-filter">
                <a href="index.php" class="filter-btn <?= !$branchFilter ? 'active' : '' ?>">جميع الفروع</a>
                <a href="index.php?branch=thumama" class="filter-btn <?= $branchFilter === 'thumama' ? 'active' : '' ?>">فرع الثمامة</a>
                <a href="index.php?branch=rawdah" class="filter-btn <?= $branchFilter === 'rawdah' ? 'active' : '' ?>">فرع الروضة</a>
            </div>
            <?php else: ?>
            <div class="branch-filter">
                <span class="filter-btn active"><i class="fas fa-building"></i> <?= getBranchName($branchFilter) ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-clipboard-list fa-lg"></i></div>
                    <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                    <div class="stat-label">إجمالي الطلبات</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-calendar-day fa-lg"></i></div>
                    <div class="stat-value"><?= number_format($stats['today_orders']) ?></div>
                    <div class="stat-label">طلبات اليوم</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-hourglass-half fa-lg"></i></div>
                    <div class="stat-value"><?= number_format($stats['pending_orders'] + $stats['in_progress_orders']) ?></div>
                    <div class="stat-label">قيد التنفيذ</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check-circle fa-lg"></i></div>
                    <div class="stat-value"><?= number_format($stats['completed_orders']) ?></div>
                    <div class="stat-label">مكتملة</div>
                </div>
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="fas fa-coins fa-lg"></i></div>
                    <div class="stat-value"><?= number_format($stats['total_revenue']) ?></div>
                    <div class="stat-label">إجمالي الإيرادات (ريال)</div>
                </div>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-clock"></i> آخر الطلبات</div>
                        <a href="orders.php" class="btn btn-outline btn-sm">عرض الكل</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentOrders)): ?>
                        <p style="text-align: center; color: #9ca3af; padding: 40px;">لا توجد طلبات</p>
                        <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <h4><?= htmlspecialchars($order['order_number']) ?></h4>
                                <p><?= htmlspecialchars($order['customer_name']) ?> - <?= htmlspecialchars($order['car_type']) ?></p>
                                <p style="font-size: 0.75rem; color: #9ca3af;">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($order['created_by_name'] ?? 'غير معروف') ?>
                                    | <i class="fas fa-building"></i> <?= getBranchName($order['branch']) ?>
                                </p>
                            </div>
                            <div class="order-meta">
                                <span class="status-badge <?= $order['status'] ?>"><?= $statusLabels[$order['status']] ?? $order['status'] ?></span>
                                <p style="margin-top: 8px; font-size: 0.8rem; color: #6b7280;"><?= date('d/m/Y', strtotime($order['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Top Employees -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-users"></i> أداء الموظفين</div>
                        <?php if (isAdmin()): ?>
                        <a href="employees.php" class="btn btn-outline btn-sm">التفاصيل</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topEmployees)): ?>
                        <p style="text-align: center; color: #9ca3af; padding: 20px;">لا توجد بيانات</p>
                        <?php else: ?>
                        <?php foreach ($topEmployees as $emp): ?>
                        <div class="employee-item">
                            <div class="employee-avatar"><?= mb_substr($emp['full_name'], 0, 1) ?></div>
                            <div class="employee-info">
                                <h4><?= htmlspecialchars($emp['full_name']) ?></h4>
                                <p><?= getBranchName($emp['branch']) ?></p>
                            </div>
                            <div class="employee-stats">
                                <span style="color: #1e3a5f; font-weight: 600;"><?= $emp['orders_created'] ?> طلب</span>
                                <span style="color: #10b981;"><?= $emp['orders_completed'] ?> مكتمل</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
