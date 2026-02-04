<?php
require_once 'includes/header.php'; 
require_once 'includes/db_connect.php';
require_once 'includes/admin_log.php';

$id = "";
$name = ""; 
$phone = ""; 
$card = ""; 
$v_model = ""; 
$v_plate = ""; 
$expert = "";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT u.*, p.vehicle_model, p.vehicle_plate, p.expertise 
            FROM users u 
            LEFT JOIN technician_profiles p ON u.user_id = p.user_id 
            WHERE u.user_id = ? AND u.role = 'technician'";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $name = $row['fullname'];
        $phone = $row['phone'];
        $card = $row['id_card'];
        $v_model = $row['vehicle_model'];
        $v_plate = $row['vehicle_plate'];
        $expert = $row['expertise'];
    } else {
        header("Location: manage_technicians.php");
        exit();
    }
    $stmt->close();
}
?>

<div class="container-fluid" style="max-width: 800px;">
    
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card-custom p-0 overflow-hidden">
        <div class="p-4 border-bottom" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-wrench text-white fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold m-0 text-white"><?php echo $id ? 'แก้ไขช่าง #'.str_pad($id, 5, '0', STR_PAD_LEFT) : 'เพิ่มช่างใหม่'; ?></h5>
                        <small class="text-white-50">จัดการข้อมูลช่าง</small>
                    </div>
                </div>
                <a href="manage_technicians.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> ย้อนกลับ
                </a>
            </div>
        </div>
        <div class="p-4">
        <form action="actions/tech_save.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <input type="hidden" name="user_id" value="<?php echo $id; ?>">

            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-user-circle me-2"></i>ข้อมูลส่วนตัว</h6>
            
            <div class="mb-3">
                <label class="form-label fw-bold">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">เลขบัตรประชาชน</label>
                    <input type="text" name="id_card" class="form-control" value="<?php echo htmlspecialchars($card); ?>" maxlength="13">
                </div>
            </div>

            <hr class="my-4">

            <h6 class="text-primary fw-bold mb-3"><i class="fas fa-tools me-2"></i>ข้อมูลงานช่าง</h6>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">รุ่นรถ</label>
                    <input type="text" name="vehicle_model" class="form-control" value="<?php echo htmlspecialchars($v_model); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">ทะเบียนรถ</label>
                    <input type="text" name="vehicle_plate" class="form-control" value="<?php echo htmlspecialchars($v_plate); ?>">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label fw-bold">ความชำนาญ</label>
                <input type="text" name="expertise" class="form-control" value="<?php echo htmlspecialchars($expert); ?>">
            </div>

            <hr class="my-4">

            <div class="mb-4">
                <label class="form-label fw-bold">รหัสผ่านใหม่ <small class="text-muted fw-normal">(เว้นว่างหากไม่ต้องการเปลี่ยน)</small></label>
                <input type="password" name="password" class="form-control">
                <?php if ($id == ""): ?>
                <div class="form-text text-muted">* สำหรับช่างใหม่ หากไม่กรอก จะใช้รหัสผ่านเริ่มต้น: 123456</div>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" name="save_tech" class="btn btn-primary-custom px-5 py-2">
                    <i class="fas fa-save me-2"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
