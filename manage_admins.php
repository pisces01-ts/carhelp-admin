<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/admin_log.php';

// Only super admin can access
if (($_SESSION['admin_role'] ?? 'admin') !== 'super_admin') {
    $_SESSION['msg'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    $_SESSION['msg_type'] = 'danger';
    header('Location: dashboard.php');
    exit();
}

if (isset($_POST['add_admin'])) {
    if (!validateActionCsrf()) {
        $_SESSION['msg'] = 'CSRF token ไม่ถูกต้อง';
        $_SESSION['msg_type'] = 'danger';
    } else {
        $fullname = trim($_POST['fullname']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];

        $stmt = $conn->prepare("INSERT INTO users (fullname, phone, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssss", $fullname, $phone, $email, $password, $role);
        $stmt->execute();

        logAdminAction('create', 'admin', $conn->insert_id, ['fullname' => $fullname]);
        $_SESSION['msg'] = 'เพิ่มผู้ดูแลระบบเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: manage_admins.php");
    exit();
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    if ($id != $_SESSION['admin_id']) {
        $conn->query("UPDATE users SET status = IF(status='active','inactive','active') WHERE user_id = $id");
        logAdminAction('toggle', 'admin', $id);
    }
    header("Location: manage_admins.php");
    exit();
}

$admins = $conn->query("SELECT * FROM users WHERE role IN ('admin', 'super_admin') ORDER BY created_at DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0"><i class="fas fa-user-shield text-primary me-2"></i>จัดการผู้ดูแลระบบ</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fas fa-plus me-2"></i>เพิ่มผู้ดูแล
    </button>
</div>

<?php if (isset($_SESSION['msg'])): ?>
<div class="alert alert-<?= $_SESSION['msg_type'] ?> alert-dismissible fade show">
    <?= $_SESSION['msg'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['msg'], $_SESSION['msg_type']); endif; ?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>ชื่อ</th>
                    <th>เบอร์โทร</th>
                    <th>อีเมล</th>
                    <th>ระดับ</th>
                    <th>สถานะ</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($a = $admins->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($a['fullname']) ?></strong>
                        <?php if ($a['user_id'] == $_SESSION['admin_id']): ?>
                            <span class="badge bg-info ms-1">คุณ</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $a['phone'] ?></td>
                    <td><?= htmlspecialchars($a['email'] ?? '-') ?></td>
                    <td>
                        <span class="badge bg-<?= $a['role']=='super_admin'?'danger':'primary' ?>">
                            <?= $a['role']=='super_admin'?'Super Admin':'Admin' ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $a['status']=='active'?'success':'secondary' ?>">
                            <?= $a['status']=='active'?'ใช้งาน':'ระงับ' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($a['user_id'] != $_SESSION['admin_id']): ?>
                        <a href="?toggle=<?= $a['user_id'] ?>" class="btn btn-sm btn-outline-<?= $a['status']=='active'?'warning':'success' ?>">
                            <i class="fas fa-<?= $a['status']=='active'?'ban':'check' ?>"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มผู้ดูแลระบบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เบอร์โทร</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อีเมล</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ระดับ</label>
                        <select name="role" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="add_admin" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
