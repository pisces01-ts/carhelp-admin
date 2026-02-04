<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/admin_log.php';
require_once 'includes/pagination.php';

$pagination = getPaginationData($conn, 'service_requests', '', 20);

$sql = "SELECT r.*, c.fullname AS customer_name, t.fullname AS technician_name 
        FROM service_requests r 
        LEFT JOIN users c ON r.customer_id = c.user_id 
        LEFT JOIN users t ON r.technician_id = t.user_id 
        ORDER BY r.request_time DESC {$pagination['limit']}";
$result = $conn->query($sql);

$statusColors = ['pending' => 'warning', 'accepted' => 'info', 'traveling' => 'info', 'arrived' => 'primary', 'working' => 'primary', 'completed' => 'success', 'cancelled' => 'danger'];
$statusTexts = ['pending' => 'รอรับงาน', 'accepted' => 'รับงานแล้ว', 'traveling' => 'กำลังเดินทาง', 'arrived' => 'ถึงแล้ว', 'working' => 'กำลังซ่อม', 'completed' => 'เสร็จสิ้น', 'cancelled' => 'ยกเลิก'];
?>

<div class="card-custom p-0 overflow-hidden mb-4">
    <div class="p-4" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-clipboard-list text-white fa-lg"></i>
                </div>
                <div>
                    <h4 class="fw-bold m-0 text-white">จัดการงานซ่อม</h4>
                    <small class="text-white-50">รายการงานซ่อมทั้งหมด</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card-custom p-0 overflow-hidden">
    <div class="d-flex justify-content-between align-items-center p-4 border-bottom bg-white">
        <h5 class="fw-bold m-0 text-dark"><i class="fas fa-list me-2 text-primary"></i>รายการงาน</h5>
        <div class="position-relative">
            <i class="fas fa-search position-absolute text-muted" style="left: 15px; top: 12px;"></i>
            <input type="text" id="searchInput" onkeyup="searchTable()" class="form-control ps-5" placeholder="ค้นหา..." style="width: 250px; border-radius: 20px;">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-custom w-100 mb-0 align-middle">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">รหัส</th>
                    <th>ลูกค้า</th>
                    <th>ปัญหา</th>
                    <th>ช่าง</th>
                    <th>สถานะ</th>
                    <th>ราคา</th>
                    <th>เวลา</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="ps-4"><strong>#<?php echo str_pad($row['request_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['customer_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['problem_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['technician_name'] ?? '-'); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $statusColors[$row['status']] ?? 'secondary'; ?>">
                            <?php echo $statusTexts[$row['status']] ?? $row['status']; ?>
                        </span>
                    </td>
                    <td class="fw-bold text-success">฿<?php echo number_format($row['price'], 0); ?></td>
                    <td class="text-muted small"><?php echo date('d/m/y H:i', strtotime($row['request_time'])); ?></td>
                    <td class="text-center">
                        <a href="form_job.php?id=<?php echo $row['request_id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo getDeleteUrl('actions/job_save.php', $row['request_id'], 'req_id'); ?>" class="btn btn-sm btn-outline-danger btn-delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <?php echo renderPagination($pagination); ?>
</div>

<?php include 'includes/footer.php'; ?>
