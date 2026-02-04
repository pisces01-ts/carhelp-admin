<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/pagination.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$rating = $_GET['rating'] ?? '';

$where = "1=1";
if ($rating) $where .= " AND rv.rating = " . intval($rating);

$countResult = $conn->query("SELECT COUNT(*) as total FROM reviews rv WHERE $where");
$total = $countResult->fetch_assoc()['total'];
$pagination = getPagination($total, $perPage, $page);

$reviews = $conn->query("SELECT rv.*, 
    u.fullname as customer_name, u.phone as customer_phone,
    t.fullname as technician_name,
    r.problem_type, r.request_id
    FROM reviews rv
    LEFT JOIN users u ON rv.customer_id = u.user_id
    LEFT JOIN users t ON rv.technician_id = t.user_id
    LEFT JOIN service_requests r ON rv.request_id = r.request_id
    WHERE $where
    ORDER BY rv.created_at DESC
    LIMIT {$pagination['offset']}, $perPage");

// Stats
$stats = $conn->query("SELECT 
    COUNT(*) as total,
    AVG(rating) as avg_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as low_star
    FROM reviews")->fetch_assoc();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0"><i class="fas fa-star text-warning me-2"></i>รีวิวจากลูกค้า</h3>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card text-center p-3">
            <div class="display-4 text-warning"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></div>
            <div class="text-warning mb-2">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star<?= $i <= round($stats['avg_rating'] ?? 0) ? '' : '-o' ?>"></i>
                <?php endfor; ?>
            </div>
            <small class="text-muted">จาก <?= number_format($stats['total']) ?> รีวิว</small>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card p-3">
            <div class="row text-center">
                <div class="col">
                    <a href="?rating=5" class="text-decoration-none">
                        <div class="fs-4 fw-bold text-success"><?= $stats['five_star'] ?></div>
                        <small>5 ดาว</small>
                    </a>
                </div>
                <div class="col">
                    <a href="?rating=4" class="text-decoration-none">
                        <div class="fs-4 fw-bold text-primary"><?= $stats['four_star'] ?></div>
                        <small>4 ดาว</small>
                    </a>
                </div>
                <div class="col">
                    <a href="?rating=3" class="text-decoration-none">
                        <div class="fs-4 fw-bold text-warning"><?= $stats['three_star'] ?></div>
                        <small>3 ดาว</small>
                    </a>
                </div>
                <div class="col">
                    <a href="?rating=" class="text-decoration-none">
                        <div class="fs-4 fw-bold text-danger"><?= $stats['low_star'] ?></div>
                        <small>≤2 ดาว</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>รายการรีวิว</span>
        <?php if ($rating): ?>
            <a href="reviews.php" class="btn btn-sm btn-outline-secondary">ดูทั้งหมด</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>งาน</th>
                    <th>ลูกค้า</th>
                    <th>ช่าง</th>
                    <th class="text-center">คะแนน</th>
                    <th>ความคิดเห็น</th>
                    <th>วันที่</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reviews && $reviews->num_rows > 0): ?>
                    <?php while ($rv = $reviews->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <a href="view_job.php?id=<?= $rv['request_id'] ?>">#<?= str_pad($rv['request_id'], 5, '0', STR_PAD_LEFT) ?></a>
                            <br><small class="text-muted"><?= htmlspecialchars($rv['problem_type'] ?? '') ?></small>
                        </td>
                        <td><?= htmlspecialchars($rv['customer_name']) ?></td>
                        <td><?= htmlspecialchars($rv['technician_name']) ?></td>
                        <td class="text-center text-warning">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= $rv['rating'] ? '' : '-o text-muted' ?>"></i>
                            <?php endfor; ?>
                        </td>
                        <td><?= htmlspecialchars($rv['comment'] ?? '-') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($rv['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">ยังไม่มีรีวิว</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination['totalPages'] > 1): ?>
    <div class="card-footer"><?= renderPagination($pagination, "reviews.php?" . ($rating ? "rating=$rating&" : '')) ?></div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
