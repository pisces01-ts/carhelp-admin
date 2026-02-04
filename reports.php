<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$period = $_GET['period'] ?? 'month';
$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-t');

// Get date range based on period
switch ($period) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case 'week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
    case 'year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        break;
}

// Stats
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_jobs,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_jobs,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END), 0) as total_revenue
    FROM service_requests 
    WHERE DATE(request_time) BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// By problem type
$stmt = $conn->prepare("SELECT problem_type, COUNT(*) as count, SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END) as revenue
    FROM service_requests 
    WHERE DATE(request_time) BETWEEN ? AND ?
    GROUP BY problem_type
    ORDER BY count DESC");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$byType = $stmt->get_result();

// Top technicians
$stmt = $conn->prepare("SELECT u.fullname, COUNT(*) as jobs, SUM(CASE WHEN r.status = 'completed' THEN r.price ELSE 0 END) as revenue,
    (SELECT AVG(rating) FROM reviews WHERE technician_id = u.user_id) as avg_rating
    FROM service_requests r
    JOIN users u ON r.technician_id = u.user_id
    WHERE DATE(r.request_time) BETWEEN ? AND ?
    GROUP BY r.technician_id
    ORDER BY jobs DESC
    LIMIT 10");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$topTechs = $stmt->get_result();

// New customers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$newCustomers = $stmt->get_result()->fetch_assoc()['count'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0"><i class="fas fa-chart-bar text-primary me-2"></i>รายงานและสถิติ</h3>
    <div class="btn-group">
        <a href="?period=today" class="btn btn-<?= $period=='today'?'primary':'outline-primary' ?>">วันนี้</a>
        <a href="?period=week" class="btn btn-<?= $period=='week'?'primary':'outline-primary' ?>">สัปดาห์นี้</a>
        <a href="?period=month" class="btn btn-<?= $period=='month'?'primary':'outline-primary' ?>">เดือนนี้</a>
        <a href="?period=year" class="btn btn-<?= $period=='year'?'primary':'outline-primary' ?>">ปีนี้</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card blue">
            <div class="small opacity-75">งานทั้งหมด</div>
            <div class="fs-3 fw-bold"><?= number_format($stats['total_jobs']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card green">
            <div class="small opacity-75">งานเสร็จสิ้น</div>
            <div class="fs-3 fw-bold"><?= number_format($stats['completed_jobs']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card orange">
            <div class="small opacity-75">รายได้รวม</div>
            <div class="fs-3 fw-bold">฿<?= number_format($stats['total_revenue'], 0) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card purple" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
            <div class="small opacity-75">ลูกค้าใหม่</div>
            <div class="fs-3 fw-bold"><?= number_format($newCustomers) ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-tools me-2"></i>งานตามประเภท</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>ประเภท</th><th class="text-center">จำนวน</th><th class="text-end">รายได้</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $byType->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['problem_type']) ?></td>
                            <td class="text-center"><?= number_format($row['count']) ?></td>
                            <td class="text-end text-success">฿<?= number_format($row['revenue'], 0) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-trophy me-2"></i>ช่างยอดเยี่ยม</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>ช่าง</th><th class="text-center">งาน</th><th class="text-center">คะแนน</th><th class="text-end">รายได้</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $topTechs->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td class="text-center"><?= number_format($row['jobs']) ?></td>
                            <td class="text-center text-warning"><?= $row['avg_rating'] ? number_format($row['avg_rating'], 1) . ' ⭐' : '-' ?></td>
                            <td class="text-end text-success">฿<?= number_format($row['revenue'], 0) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print me-2"></i>พิมพ์รายงาน</button>
    <a href="export_report.php?period=<?= $period ?>&start=<?= $startDate ?>&end=<?= $endDate ?>" class="btn btn-success"><i class="fas fa-file-excel me-2"></i>ส่งออก Excel</a>
</div>

<?php include 'includes/footer.php'; ?>
