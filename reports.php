<?php
/**
 * 3WAY Car Service - Reports (Arabic Only)
 */
require_once 'includes/config.php';
requireAccess('reports.php');

$db = Database::getInstance()->getConnection();

$branch = $_GET['branch'] ?? '';
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

$where = "WHERE DATE(created_at) BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];

if ($branch && in_array($branch, ['thumama', 'rawdah'])) {
    $where .= " AND branch = ?";
    $params[] = $branch;
}

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM job_orders $where");
$stmt->execute($params);
$totalOrders = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM job_orders $where AND status IN ('completed', 'delivered')");
$stmt->execute($params);
$completedOrders = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(estimated_cost), 0) FROM job_orders $where");
$stmt->execute($params);
$totalRevenue = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COALESCE(SUM(estimated_cost), 0) FROM job_orders $where AND status IN ('completed', 'delivered')");
$stmt->execute($params);
$collectedRevenue = $stmt->fetchColumn();

// Orders by status
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM job_orders $where GROUP BY status");
$stmt->execute($params);
$statusData = $stmt->fetchAll();

// Orders by branch
$stmt = $db->prepare("SELECT branch, COUNT(*) as count, SUM(estimated_cost) as revenue FROM job_orders $where GROUP BY branch");
$stmt->execute($params);
$branchData = $stmt->fetchAll();

// Daily orders for chart
$stmt = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM job_orders $where GROUP BY DATE(created_at) ORDER BY date");
$stmt->execute($params);
$dailyData = $stmt->fetchAll();

// Top services
$services = [
    'service_body_repair' => 'سمكرة',
    'service_single_paint' => 'دهان قطعة',
    'service_multi_paint' => 'دهان متعدد',
    'service_full_spray' => 'رش كامل',
    'service_single_dent' => 'PDR طعجة',
    'service_exterior_polish' => 'تلميع خارجي',
    'service_nano_ceramic' => 'نانو سيراميك',
    'service_wash' => 'غسيل'
];

$serviceStats = [];
foreach ($services as $col => $name) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM job_orders $where AND $col = 1");
    $stmt->execute($params);
    $serviceStats[$name] = $stmt->fetchColumn();
}
arsort($serviceStats);

// Visit source stats
$stmt = $db->prepare("SELECT visit_source, COUNT(*) as count, SUM(estimated_cost) as revenue FROM job_orders $where GROUP BY visit_source ORDER BY count DESC");
$stmt->execute($params);
$sourceData = $stmt->fetchAll();

global $statusLabels, $sourceLabels;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير - 3WAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        .filters{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:24px;background:#fff;padding:20px;border-radius:12px;box-shadow:var(--shadow);align-items:flex-end}
        .filter-group{display:flex;flex-direction:column;gap:6px}
        .filter-group label{font-weight:600;font-size:.85rem;color:var(--gray-600)}
        .filter-group select,.filter-group input{padding:10px 14px;border:2px solid var(--gray-200);border-radius:8px;font-family:inherit}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
        .stat-card{background:#fff;border-radius:12px;padding:20px;box-shadow:var(--shadow);text-align:center}
        .stat-card .icon{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.3rem}
        .stat-card .icon.primary{background:rgba(30,58,95,.1);color:var(--primary)}
        .stat-card .icon.success{background:rgba(16,185,129,.1);color:var(--success)}
        .stat-card .icon.warning{background:rgba(245,158,11,.1);color:var(--warning)}
        .stat-card .icon.info{background:rgba(59,130,246,.1);color:var(--info)}
        .stat-card .value{font-size:1.8rem;font-weight:800;color:var(--primary)}
        .stat-card .label{color:var(--gray-500);font-size:.9rem;margin-top:4px}
        .report-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:24px}
        .report-card{background:#fff;border-radius:12px;box-shadow:var(--shadow);overflow:hidden}
        .report-card-header{padding:16px 20px;background:var(--gray-50);border-bottom:1px solid var(--gray-200);font-weight:700;color:var(--primary);display:flex;align-items:center;gap:10px}
        .report-card-header i{color:var(--accent)}
        .report-card-body{padding:20px}
        .progress-item{margin-bottom:16px}
        .progress-item .label{display:flex;justify-content:space-between;margin-bottom:6px;font-size:.9rem}
        .progress-item .bar{height:10px;background:var(--gray-200);border-radius:5px;overflow:hidden}
        .progress-item .fill{height:100%;border-radius:5px;transition:width .5s}
        .branch-item{display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--gray-50);border-radius:8px;margin-bottom:10px}
        .branch-item .name{font-weight:600}
        .branch-item .stats{text-align:left}
        .branch-item .stats span{display:block;font-size:.85rem}
        .status-list .item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--gray-100)}
        .status-list .item:last-child{border-bottom:none}
        .status-list .dot{width:12px;height:12px;border-radius:50%}
        .status-list .dot.pending{background:var(--warning)}
        .status-list .dot.in_progress{background:var(--info)}
        .status-list .dot.completed{background:var(--success)}
        .status-list .dot.delivered{background:#8b5cf6}
        .source-stats-grid{display:flex;flex-direction:column;gap:12px}
        .source-stat-item{display:flex;align-items:center;gap:16px;padding:12px;background:var(--gray-50);border-radius:10px}
        .source-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
        .source-info{flex:1;min-width:0}
        .source-info h4{font-size:.95rem;font-weight:600;color:var(--primary);margin:0 0 8px}
        .source-bar{height:8px;background:var(--gray-200);border-radius:4px;overflow:hidden}
        .source-fill{height:100%;border-radius:4px;transition:width .5s}
        .source-numbers{text-align:left;flex-shrink:0}
        .source-numbers .count{display:block;font-weight:700;color:var(--primary);font-size:.95rem}
        .source-numbers .revenue{display:block;font-size:.8rem;color:var(--success)}
        @media(max-width:640px){.filters{flex-direction:column}.filter-group{width:100%}.report-grid{grid-template-columns:1fr}.source-stat-item{flex-wrap:wrap}.source-numbers{width:100%;text-align:right;margin-top:8px;display:flex;justify-content:space-between}}
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
                    <i class="fas fa-chart-bar"></i>
                    <h2>التقارير</h2>
                </div>
            </header>
            
            <form class="filters" method="GET">
                <div class="filter-group">
                    <label>الفرع</label>
                    <select name="branch">
                        <option value="">جميع الفروع</option>
                        <option value="thumama" <?= $branch === 'thumama' ? 'selected' : '' ?>>فرع الثمامة</option>
                        <option value="rawdah" <?= $branch === 'rawdah' ? 'selected' : '' ?>>فرع الروضة</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>من تاريخ</label>
                    <input type="date" name="from" value="<?= $dateFrom ?>">
                </div>
                <div class="filter-group">
                    <label>إلى تاريخ</label>
                    <input type="date" name="to" value="<?= $dateTo ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> عرض</button>
            </form>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon primary"><i class="fas fa-clipboard-list"></i></div>
                    <div class="value"><?= number_format($totalOrders) ?></div>
                    <div class="label">إجمالي الطلبات</div>
                </div>
                <div class="stat-card">
                    <div class="icon success"><i class="fas fa-check-circle"></i></div>
                    <div class="value"><?= number_format($completedOrders) ?></div>
                    <div class="label">طلبات مكتملة</div>
                </div>
                <div class="stat-card">
                    <div class="icon warning"><i class="fas fa-coins"></i></div>
                    <div class="value"><?= number_format($totalRevenue) ?></div>
                    <div class="label">إجمالي الإيرادات</div>
                </div>
                <div class="stat-card">
                    <div class="icon info"><i class="fas fa-hand-holding-usd"></i></div>
                    <div class="value"><?= number_format($collectedRevenue) ?></div>
                    <div class="label">إيرادات محصلة</div>
                </div>
            </div>
            
            <div class="report-grid">
                <div class="report-card">
                    <div class="report-card-header"><i class="fas fa-chart-pie"></i> حالة الطلبات</div>
                    <div class="report-card-body">
                        <div class="status-list">
                            <?php foreach ($statusData as $s): ?>
                            <div class="item">
                                <span class="dot <?= $s['status'] ?>"></span>
                                <span style="flex:1"><?= $statusLabels[$s['status']] ?? $s['status'] ?></span>
                                <strong><?= $s['count'] ?></strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="report-card">
                    <div class="report-card-header"><i class="fas fa-building"></i> أداء الفروع</div>
                    <div class="report-card-body">
                        <?php foreach ($branchData as $b): ?>
                        <div class="branch-item">
                            <div class="name"><i class="fas fa-map-marker-alt" style="color:var(--accent)"></i> <?= getBranchName($b['branch']) ?></div>
                            <div class="stats">
                                <span><strong><?= $b['count'] ?></strong> طلب</span>
                                <span style="color:var(--success)"><?= number_format($b['revenue']) ?> ريال</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="report-card" style="grid-column:1/-1">
                    <div class="report-card-header"><i class="fas fa-tools"></i> الخدمات الأكثر طلباً</div>
                    <div class="report-card-body">
                        <?php $maxService = max($serviceStats) ?: 1; ?>
                        <?php foreach (array_slice($serviceStats, 0, 8, true) as $name => $count): ?>
                        <div class="progress-item">
                            <div class="label"><span><?= $name ?></span><span><?= $count ?></span></div>
                            <div class="bar"><div class="fill" style="width:<?= ($count / $maxService) * 100 ?>%;background:var(--primary)"></div></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="report-card" style="grid-column:1/-1">
                    <div class="report-card-header"><i class="fas fa-bullhorn"></i> مصادر الزيارة</div>
                    <div class="report-card-body">
                        <div class="source-stats-grid">
                            <?php 
                            $maxSource = !empty($sourceData) ? max(array_column($sourceData, 'count')) : 1;
                            $sourceIcons = [
                                'tiktok' => 'fab fa-tiktok',
                                'instagram' => 'fab fa-instagram',
                                'snapchat' => 'fab fa-snapchat',
                                'google_search' => 'fab fa-google',
                                'google_maps' => 'fas fa-map-marker-alt',
                                'youtube' => 'fab fa-youtube',
                                'twitter' => 'fab fa-twitter',
                                'friend_referral' => 'fas fa-user-friends',
                                'direct_visit' => 'fas fa-store',
                                'returning_customer' => 'fas fa-redo',
                                'other' => 'fas fa-ellipsis-h'
                            ];
                            $sourceColors = [
                                'tiktok' => '#000',
                                'instagram' => '#E4405F',
                                'snapchat' => '#FFFC00',
                                'google_search' => '#4285F4',
                                'google_maps' => '#34A853',
                                'youtube' => '#FF0000',
                                'twitter' => '#1DA1F2',
                                'friend_referral' => '#6366f1',
                                'direct_visit' => '#10b981',
                                'returning_customer' => '#f59e0b',
                                'other' => '#6b7280'
                            ];
                            ?>
                            <?php foreach ($sourceData as $src): ?>
                            <?php if (empty($src['visit_source'])) continue; ?>
                            <div class="source-stat-item">
                                <div class="source-icon" style="background:<?= $sourceColors[$src['visit_source']] ?? '#6b7280' ?>20;color:<?= $sourceColors[$src['visit_source']] ?? '#6b7280' ?>">
                                    <i class="<?= $sourceIcons[$src['visit_source']] ?? 'fas fa-question' ?>"></i>
                                </div>
                                <div class="source-info">
                                    <h4><?= $sourceLabels[$src['visit_source']] ?? $src['visit_source'] ?></h4>
                                    <div class="source-bar">
                                        <div class="source-fill" style="width:<?= ($src['count'] / $maxSource) * 100 ?>%;background:<?= $sourceColors[$src['visit_source']] ?? '#6b7280' ?>"></div>
                                    </div>
                                </div>
                                <div class="source-numbers">
                                    <span class="count"><?= $src['count'] ?> طلب</span>
                                    <span class="revenue"><?= number_format($src['revenue']) ?> ريال</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($sourceData)): ?>
                            <p style="text-align:center;color:var(--gray-400);padding:20px">لا توجد بيانات</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
