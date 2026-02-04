<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/pagination.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$period = $_GET['period'] ?? 'month';
$commissionRate = 15; // % ค่าคอมมิชชั่นที่หักจากช่าง

// Date range
switch ($period) {
    case 'today': $dateCondition = "DATE(completed_time) = CURDATE()"; break;
    case 'week': $dateCondition = "YEARWEEK(completed_time) = YEARWEEK(CURDATE())"; break;
    case 'year': $dateCondition = "YEAR(completed_time) = YEAR(CURDATE())"; break;
    default: $dateCondition = "MONTH(completed_time) = MONTH(CURDATE()) AND YEAR(completed_time) = YEAR(CURDATE())";
}

// Summary
$summary = $conn->query("SELECT 
    COUNT(*) as total_jobs,
    COALESCE(SUM(price), 0) as total_revenue,
    COALESCE(SUM(price * $commissionRate / 100), 0) as commission
    FROM service_requests 
    WHERE status = 'completed' AND $dateCondition")->fetch_assoc();

// Paginated list
$countResult = $conn->query("SELECT COUNT(*) as total FROM service_requests WHERE status = 'completed' AND $dateCondition");
$total = $countResult->fetch_assoc()['total'];
$pagination = getPagination($total, $perPage, $page);

$jobs = $conn->query("SELECT r.*, 
    u.fullname as customer_name, 
    t.fullname as technician_name,
    (r.price * $commissionRate / 100) as commission
    FROM service_requests r
    LEFT JOIN users u ON r.customer_id = u.user_id
    LEFT JOIN users t ON r.technician_id = t.user_id
    WHERE r.status = 'completed' AND $dateCondition
    ORDER BY r.completed_time DESC
    LIMIT {$pagination['offset']}, $perPage");

// Monthly summary for finance department
$monthlySum = $conn->query("SELECT 
    DATE_FORMAT(completed_time, '%Y-%m') as month,
    COUNT(*) as jobs,
    SUM(price) as revenue,
    SUM(price * $commissionRate / 100) as commission
    FROM service_requests 
    WHERE status = 'completed' AND completed_time >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(completed_time, '%Y-%m')
    ORDER BY month DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0"><i class="fas fa-wallet text-primary me-2"></i>บัญชีรายรับ</h3>
    <div class="btn-group">
        <a href="?period=today" class="btn btn-<?= $period=='today'?'primary':'outline-primary' ?>">วันนี้</a>
        <a href="?period=week" class="btn btn-<?= $period=='week'?'primary':'outline-primary' ?>">สัปดาห์</a>
        <a href="?period=month" class="btn btn-<?= $period=='month'?'primary':'outline-primary' ?>">เดือน</a>
        <a href="?period=year" class="btn btn-<?= $period=='year'?'primary':'outline-primary' ?>">ปี</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card green">
            <div class="small opacity-75">รายได้รวม</div>
            <div class="fs-3 fw-bold">฿<?= number_format($summary['total_revenue'], 0) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card orange">
            <div class="small opacity-75">ค่าคอมมิชชั่น (<?= $commissionRate ?>%)</div>
            <div class="fs-3 fw-bold">฿<?= number_format($summary['commission'], 0) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card blue">
            <div class="small opacity-75">จำนวนงาน</div>
            <div class="fs-3 fw-bold"><?= number_format($summary['total_jobs']) ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>รายการงานที่เสร็จสิ้น</span>
                <a href="print_financial.php?period=<?= $period ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-print me-1"></i>พิมพ์
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>ลูกค้า</th>
                            <th>ช่าง</th>
                            <th class="text-end">ราคา</th>
                            <th class="text-end">หัก <?= $commissionRate ?>%</th>
                            <th>วันที่</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($job = $jobs->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= str_pad($job['request_id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($job['customer_name']) ?></td>
                            <td><?= htmlspecialchars($job['technician_name']) ?></td>
                            <td class="text-end">฿<?= number_format($job['price'], 0) ?></td>
                            <td class="text-end text-success">฿<?= number_format($job['commission'], 0) ?></td>
                            <td><?= date('d/m/Y', strtotime($job['completed_time'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pagination['totalPages'] > 1): ?>
            <div class="card-footer"><?= renderPagination($pagination, "manage_financial.php?period=$period&") ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-calendar-alt me-2"></i>สรุปรายเดือน (ส่งแผนกการเงิน)</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>เดือน</th><th class="text-end">รายได้</th><th class="text-end">ค่าคอม</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $monthlySum->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('M Y', strtotime($row['month'] . '-01')) ?></td>
                            <td class="text-end">฿<?= number_format($row['revenue'], 0) ?></td>
                            <td class="text-end text-success">฿<?= number_format($row['commission'], 0) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="export_financial.php" class="btn btn-success btn-sm w-100">
                    <i class="fas fa-file-excel me-1"></i>ส่งออก Excel ส่งแผนกการเงิน
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
