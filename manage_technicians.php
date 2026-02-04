<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/admin_log.php';
require_once 'includes/pagination.php';

$sql = "SELECT u.*, p.vehicle_model, p.vehicle_plate, p.expertise, p.avg_rating 
        FROM users u 
        LEFT JOIN technician_profiles p ON u.user_id = p.user_id 
        WHERE u.role = 'technician' 
        ORDER BY u.created_at DESC";
$result = $conn->query($sql);
?>

<div class="card-custom p-0 overflow-hidden mb-4">
    <div class="p-4" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-wrench text-white fa-lg"></i>
                </div>
                <div>
                    <h4 class="fw-bold m-0 text-white">จัดการช่าง</h4>
                    <small class="text-white-50">รายชื่อช่างทั้งหมดในระบบ</small>
                </div>
            </div>
            <a href="form_technician.php" class="btn btn-light">
                <i class="fas fa-plus me-2"></i> เพิ่มช่างใหม่
            </a>
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
        <h5 class="fw-bold m-0 text-dark"><i class="fas fa-list me-2 text-primary"></i>รายการช่าง</h5>
        <div class="position-relative">
            <i class="fas fa-search position-absolute text-muted" style="left: 15px; top: 12px;"></i>
            <input type="text" id="searchInput" onkeyup="searchTable()" class="form-control ps-5" placeholder="ค้นหา..." style="width: 250px; border-radius: 20px;">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-custom w-100 mb-0 align-middle">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>ชื่อ-สกุล</th>
                    <th>เบอร์โทร</th>
                    <th>ความชำนาญ</th>
                    <th>รถ</th>
                    <th>คะแนน</th>
                    <th>สถานะ</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="ps-4"><strong>#<?php echo str_pad($row['user_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><small><?php echo htmlspecialchars($row['expertise'] ?? '-'); ?></small></td>
                    <td><small><?php echo htmlspecialchars($row['vehicle_plate'] ?? '-'); ?></small></td>
                    <td>
                        <?php if($row['avg_rating'] > 0): ?>
                            <i class="fas fa-star text-warning"></i> <?php echo number_format($row['avg_rating'], 1); ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : ($row['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                            <?php echo $row['status'] == 'active' ? 'ใช้งาน' : ($row['status'] == 'pending' ? 'รออนุมัติ' : 'ปิดใช้งาน'); ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="form_technician.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="<?php echo getDeleteUrl('actions/tech_save.php', $row['user_id'], 'user_id'); ?>" class="btn btn-sm btn-outline-danger btn-delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
