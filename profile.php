<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$adminId = $_SESSION['admin_id'];

if (isset($_POST['update_profile'])) {
    if (!validateActionCsrf()) {
        $_SESSION['msg'] = 'CSRF token ไม่ถูกต้อง';
        $_SESSION['msg_type'] = 'danger';
    } else {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $fullname, $email, $phone, $adminId);
        $stmt->execute();

        $_SESSION['admin_name'] = $fullname;
        $_SESSION['msg'] = 'อัปเดตข้อมูลเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: profile.php");
    exit();
}

if (isset($_POST['change_password'])) {
    if (!validateActionCsrf()) {
        $_SESSION['msg'] = 'CSRF token ไม่ถูกต้อง';
        $_SESSION['msg_type'] = 'danger';
    } else {
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!password_verify($currentPass, $result['password'])) {
            $_SESSION['msg'] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            $_SESSION['msg_type'] = 'danger';
        } elseif ($newPass !== $confirmPass) {
            $_SESSION['msg'] = 'รหัสผ่านใหม่ไม่ตรงกัน';
            $_SESSION['msg_type'] = 'danger';
        } elseif (strlen($newPass) < 6) {
            $_SESSION['msg'] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
            $_SESSION['msg_type'] = 'danger';
        } else {
            $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashedPass, $adminId);
            $stmt->execute();

            $_SESSION['msg'] = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
            $_SESSION['msg_type'] = 'success';
        }
    }
    header("Location: profile.php");
    exit();
}

$admin = $conn->query("SELECT * FROM users WHERE user_id = $adminId")->fetch_assoc();
?>

<h3 class="fw-bold text-dark mb-4"><i class="fas fa-user-shield text-primary me-2"></i>ตั้งค่าบัญชี</h3>

<?php if (isset($_SESSION['msg'])): ?>
<div class="alert alert-<?= $_SESSION['msg_type'] ?> alert-dismissible fade show">
    <?= $_SESSION['msg'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['msg'], $_SESSION['msg_type']); endif; ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-user me-2"></i>ข้อมูลส่วนตัว</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($admin['fullname']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อีเมล</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เบอร์โทร</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($admin['phone']) ?>">
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">บันทึก</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-key me-2"></i>เปลี่ยนรหัสผ่าน</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่านปัจจุบัน</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning">เปลี่ยนรหัสผ่าน</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
