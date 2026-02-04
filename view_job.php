<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: manage_jobs.php'); exit(); }

$job = $conn->query("SELECT r.*, 
    c.fullname as customer_name, c.phone as customer_phone, c.email as customer_email,
    t.fullname as technician_name, t.phone as technician_phone,
    tp.vehicle_plate, tp.vehicle_model, tp.expertise
    FROM service_requests r
    LEFT JOIN users c ON r.customer_id = c.user_id
    LEFT JOIN users t ON r.technician_id = t.user_id
    LEFT JOIN technician_profiles tp ON t.user_id = tp.user_id
    WHERE r.request_id = $id")->fetch_assoc();

if (!$job) { header('Location: manage_jobs.php'); exit(); }

$review = $conn->query("SELECT * FROM reviews WHERE request_id = $id")->fetch_assoc();

$statusColors = ['pending' => 'warning', 'accepted' => 'info', 'traveling' => 'info', 'working' => 'primary', 'completed' => 'success', 'cancelled' => 'danger'];
$statusTexts = ['pending' => 'รอรับงาน', 'accepted' => 'รับงานแล้ว', 'traveling' => 'กำลังเดินทาง', 'working' => 'กำลังซ่อม', 'completed' => 'เสร็จสิ้น', 'cancelled' => 'ยกเลิก'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">
        <i class="fas fa-clipboard-list text-primary me-2"></i>
        งาน #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?>
    </h3>
    <div>
        <a href="print_receipt.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-primary">
            <i class="fas fa-print me-1"></i>พิมพ์ใบเสร็จ
        </a>
        <a href="manage_jobs.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>กลับ
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span><i class="fas fa-info-circle me-2"></i>ข้อมูลงาน</span>
                <span class="badge bg-<?= $statusColors[$job['status']] ?? 'secondary' ?> fs-6">
                    <?= $statusTexts[$job['status']] ?? $job['status'] ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">ประเภทปัญหา</label>
                        <p class="mb-0 fw-bold"><?= htmlspecialchars($job['problem_type']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">ราคา</label>
                        <p class="mb-0 fw-bold text-success fs-4">฿<?= number_format($job['price'] ?? 0, 0) ?></p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">รายละเอียด</label>
                    <p class="mb-0"><?= htmlspecialchars($job['problem_details'] ?? '-') ?></p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">สถานที่</label>
                    <p class="mb-0"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($job['location_address'] ?? '-') ?></p>
                    <?php if ($job['location_lat'] && $job['location_lng']): ?>
                    <a href="https://www.google.com/maps?q=<?= $job['location_lat'] ?>,<?= $job['location_lng'] ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="fas fa-map me-1"></i>ดูบน Google Maps
                    </a>
                    <?php endif; ?>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-4">
                        <label class="text-muted small">เวลาแจ้ง</label>
                        <p class="mb-0"><?= date('d/m/Y H:i', strtotime($job['request_time'])) ?></p>
                    </div>
                    <?php if ($job['accepted_time']): ?>
                    <div class="col-md-4">
                        <label class="text-muted small">เวลารับงาน</label>
                        <p class="mb-0"><?= date('d/m/Y H:i', strtotime($job['accepted_time'])) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($job['completed_time']): ?>
                    <div class="col-md-4">
                        <label class="text-muted small">เวลาเสร็จ</label>
                        <p class="mb-0"><?= date('d/m/Y H:i', strtotime($job['completed_time'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($review): ?>
        <div class="card">
            <div class="card-header"><i class="fas fa-star text-warning me-2"></i>รีวิวจากลูกค้า</div>
            <div class="card-body">
                <div class="text-warning mb-2">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o text-muted' ?> fa-lg"></i>
                    <?php endfor; ?>
                    <span class="ms-2 text-dark">(<?= $review['rating'] ?>/5)</span>
                </div>
                <p class="mb-0"><?= htmlspecialchars($review['comment'] ?? '-') ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user me-2"></i>ลูกค้า</div>
            <div class="card-body">
                <h5 class="fw-bold"><?= htmlspecialchars($job['customer_name']) ?></h5>
                <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i><?= $job['customer_phone'] ?></p>
                <?php if ($job['customer_email']): ?>
                <p class="mb-0"><i class="fas fa-envelope me-2 text-muted"></i><?= $job['customer_email'] ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($job['technician_name']): ?>
        <div class="card">
            <div class="card-header"><i class="fas fa-user-cog me-2"></i>ช่าง</div>
            <div class="card-body">
                <h5 class="fw-bold"><?= htmlspecialchars($job['technician_name']) ?></h5>
                <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i><?= $job['technician_phone'] ?></p>
                <p class="mb-1"><i class="fas fa-car me-2 text-muted"></i><?= htmlspecialchars($job['vehicle_model'] ?? '-') ?> (<?= $job['vehicle_plate'] ?>)</p>
                <p class="mb-0"><i class="fas fa-tools me-2 text-muted"></i><?= htmlspecialchars($job['expertise'] ?? '-') ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
