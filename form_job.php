<?php
require_once 'includes/header.php'; 
require_once 'includes/db_connect.php';
require_once 'includes/admin_log.php';

if (!isset($_GET['id'])) { 
    header("Location: manage_jobs.php");
    exit(); 
}
$id = intval($_GET['id']);

$sql = "SELECT r.*, 
        c.fullname AS c_name, c.phone AS c_phone, c.address AS c_addr, 
        t.fullname AS t_name, t.phone AS t_phone
        FROM service_requests r
        LEFT JOIN users c ON r.customer_id = c.user_id
        LEFT JOIN users t ON r.technician_id = t.user_id
        WHERE r.request_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo "<div class='alert alert-danger'>ไม่พบข้อมูลงานซ่อมนี้</div>";
    include 'includes/footer.php';
    exit();
}

$techs = $conn->query("SELECT user_id, fullname FROM users WHERE role='technician' AND status='active'");
?>

<div class="container-fluid" style="max-width: 1000px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0 text-dark">งานซ่อม #<?php echo str_pad($id, 5, '0', STR_PAD_LEFT); ?></h3>
            <a href="manage_jobs.php" class="btn btn-outline-secondary bg-white btn-sm mt-2">
                <i class="fas fa-arrow-left me-2"></i> ย้อนกลับ
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card-custom p-4">
        <form action="actions/job_save.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <input type="hidden" name="req_id" value="<?php echo $id; ?>">
            
            <div class="row">
                <div class="col-md-7 border-end">
                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">ข้อมูลลูกค้า</h6>
                    <table class="table table-borderless">
                        <tr><td class="text-muted w-25">ชื่อลูกค้า:</td><td class="fw-bold"><?php echo htmlspecialchars($row['c_name']); ?></td></tr>
                        <tr><td class="text-muted">เบอร์โทร:</td><td><?php echo htmlspecialchars($row['c_phone']); ?></td></tr>
                        <tr><td class="text-muted">สถานที่:</td><td><?php echo htmlspecialchars($row['c_addr']); ?></td></tr>
                        <tr><td class="text-muted">ปัญหา:</td><td class="fw-bold text-danger"><?php echo htmlspecialchars($row['problem_type']); ?></td></tr>
                        <tr><td class="text-muted">รายละเอียด:</td><td><?php echo htmlspecialchars($row['problem_details'] ?? '-'); ?></td></tr>
                        <tr>
                            <td class="text-muted">พิกัด:</td>
                            <td>
                                <?php if($row['location_lat']) { ?>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $row['location_lat']; ?>,<?php echo $row['location_lng']; ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-map-marker-alt me-1"></i> เปิดแผนที่
                                    </a>
                                <?php } else { echo "-"; } ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-5 ps-4">
                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">จัดการงาน</h6>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">สถานะงาน</label>
                        <select name="status" class="form-select">
                            <option value="pending" <?php if($row['status']=='pending') echo 'selected'; ?>>รอรับงาน</option>
                            <option value="accepted" <?php if($row['status']=='accepted') echo 'selected'; ?>>รับงานแล้ว</option>
                            <option value="traveling" <?php if($row['status']=='traveling') echo 'selected'; ?>>กำลังเดินทาง</option>
                            <option value="working" <?php if($row['status']=='working') echo 'selected'; ?>>กำลังซ่อม</option>
                            <option value="completed" <?php if($row['status']=='completed') echo 'selected'; ?>>เสร็จสิ้น</option>
                            <option value="cancelled" <?php if($row['status']=='cancelled') echo 'selected'; ?>>ยกเลิก</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ช่างผู้รับงาน</label>
                        <select name="tech_id" class="form-select">
                            <option value="0">-- ยังไม่ระบุ --</option>
                            <?php while($t = $techs->fetch_assoc()) { ?>
                                <option value="<?php echo $t['user_id']; ?>" <?php if($row['technician_id']==$t['user_id']) echo 'selected'; ?>>
                                    <?php echo $t['fullname']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">ค่าบริการ (บาท)</label>
                        <div class="input-group">
                            <span class="input-group-text">฿</span>
                            <input type="number" name="price" class="form-control fw-bold" value="<?php echo $row['price']; ?>" min="0">
                        </div>
                    </div>

                    <button type="submit" name="update_job" class="btn btn-primary-custom w-100 py-2">
                        <i class="fas fa-save me-2"></i> บันทึกข้อมูล
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
