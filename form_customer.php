<?php
require_once 'includes/header.php'; 
require_once 'includes/db_connect.php';
require_once 'includes/admin_log.php';

$id = "";
$name = ""; 
$phone = ""; 
$email = ""; 
$addr = "";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'customer'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $name = $row['fullname'];
        $phone = $row['phone'];
        $email = $row['email'];
        $addr = $row['address'];
    } else {
        header("Location: manage_customers.php");
        exit();
    }
    $stmt->close();
}
?>

<div class="container-fluid" style="max-width: 800px;">
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card-custom p-0 overflow-hidden">
        <div class="p-4 border-bottom" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user text-white fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold m-0 text-white"><?php echo $id ? 'แก้ไขลูกค้า #'.str_pad($id, 5, '0', STR_PAD_LEFT) : 'เพิ่มลูกค้าใหม่'; ?></h5>
                        <small class="text-white-50">จัดการข้อมูลสมาชิก</small>
                    </div>
                </div>
                <a href="manage_customers.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> ย้อนกลับ
                </a>
            </div>
        </div>
        <div class="p-4">
        <form action="actions/cust_save.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <input type="hidden" name="user_id" value="<?php echo $id; ?>">

            <div class="mb-3">
                <label class="form-label fw-bold">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" maxlength="10" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">อีเมล</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">ที่อยู่</label>
                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($addr); ?></textarea>
            </div>

            <hr class="my-4">

            <div class="mb-4">
                <label class="form-label fw-bold">รหัสผ่านใหม่ <small class="text-muted fw-normal">(เว้นว่างหากไม่ต้องการเปลี่ยน)</small></label>
                <input type="password" name="password" class="form-control">
                <?php if ($id == ""): ?>
                <div class="form-text text-muted">* สำหรับลูกค้าใหม่ หากไม่กรอก จะใช้รหัสผ่านเริ่มต้น: 123456</div>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" name="save" class="btn btn-primary-custom px-5 py-2">
                    <i class="fas fa-save me-2"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
