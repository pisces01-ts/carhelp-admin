<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/admin_log.php';

// Handle actions
if (isset($_POST['add_promotion'])) {
    if (!validateActionCsrf()) {
        $_SESSION['msg'] = 'CSRF token ไม่ถูกต้อง';
        $_SESSION['msg_type'] = 'danger';
    } else {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $discountType = $_POST['discount_type'];
        $discountValue = floatval($_POST['discount_value']);
        $minAmount = floatval($_POST['min_amount'] ?? 0);
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $code = strtoupper(trim($_POST['code']));
        $maxUses = intval($_POST['max_uses'] ?? 0);

        $stmt = $conn->prepare("INSERT INTO promotions (title, description, code, discount_type, discount_value, min_amount, start_date, end_date, max_uses, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssssddssi", $title, $description, $code, $discountType, $discountValue, $minAmount, $startDate, $endDate, $maxUses);
        $stmt->execute();
        
        logAdminAction('create', 'promotion', $conn->insert_id, ['title' => $title]);
        $_SESSION['msg'] = 'เพิ่มโปรโมชั่นเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: promotions.php");
    exit();
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE promotions SET status = IF(status='active','inactive','active') WHERE promo_id = $id");
    logAdminAction('toggle', 'promotion', $id);
    header("Location: promotions.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM promotions WHERE promo_id = $id");
    logAdminAction('delete', 'promotion', $id);
    $_SESSION['msg'] = 'ลบโปรโมชั่นเรียบร้อยแล้ว';
    header("Location: promotions.php");
    exit();
}

$promotions = $conn->query("SELECT * FROM promotions ORDER BY created_at DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0"><i class="fas fa-tags text-primary me-2"></i>โปรโมชั่นและส่วนลด</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fas fa-plus me-2"></i>เพิ่มโปรโมชั่น
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
                    <th>โค้ด</th>
                    <th>ชื่อโปรโมชั่น</th>
                    <th>ส่วนลด</th>
                    <th>ระยะเวลา</th>
                    <th class="text-center">ใช้แล้ว</th>
                    <th>สถานะ</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($promotions && $promotions->num_rows > 0): ?>
                    <?php while ($p = $promotions->fetch_assoc()): ?>
                    <tr>
                        <td><code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars($p['code']) ?></code></td>
                        <td>
                            <strong><?= htmlspecialchars($p['title']) ?></strong>
                            <br><small class="text-muted"><?= htmlspecialchars(mb_substr($p['description'], 0, 50)) ?></small>
                        </td>
                        <td>
                            <?php if ($p['discount_type'] == 'percent'): ?>
                                <span class="badge bg-success"><?= $p['discount_value'] ?>%</span>
                            <?php else: ?>
                                <span class="badge bg-primary">฿<?= number_format($p['discount_value'], 0) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($p['start_date'])) ?> - <?= date('d/m/Y', strtotime($p['end_date'])) ?>
                        </td>
                        <td class="text-center"><?= $p['used_count'] ?><?= $p['max_uses'] > 0 ? "/{$p['max_uses']}" : '' ?></td>
                        <td>
                            <?php 
                            $now = date('Y-m-d');
                            $isExpired = $p['end_date'] < $now;
                            $isActive = $p['status'] == 'active' && !$isExpired;
                            ?>
                            <span class="badge bg-<?= $isActive ? 'success' : ($isExpired ? 'secondary' : 'warning') ?>">
                                <?= $isActive ? 'ใช้งานได้' : ($isExpired ? 'หมดอายุ' : 'ปิดใช้งาน') ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="?toggle=<?= $p['promo_id'] ?>" class="btn btn-sm btn-outline-<?= $p['status']=='active'?'warning':'success' ?>">
                                <i class="fas fa-<?= $p['status']=='active'?'pause':'play' ?>"></i>
                            </a>
                            <a href="?delete=<?= $p['promo_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันลบ?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">ยังไม่มีโปรโมชั่น</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มโปรโมชั่นใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">ชื่อโปรโมชั่น</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">รหัสโค้ด</label>
                            <input type="text" name="code" class="form-control text-uppercase" required placeholder="เช่น SAVE20">
                        </div>
                        <div class="col-12">
                            <label class="form-label">รายละเอียด</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ประเภทส่วนลด</label>
                            <select name="discount_type" class="form-select">
                                <option value="percent">เปอร์เซ็นต์ (%)</option>
                                <option value="fixed">ลดเงิน (บาท)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">มูลค่าส่วนลด</label>
                            <input type="number" name="discount_value" class="form-control" required step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ขั้นต่ำ (บาท)</label>
                            <input type="number" name="min_amount" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">วันเริ่มต้น</label>
                            <input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">วันสิ้นสุด</label>
                            <input type="date" name="end_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">จำกัดการใช้ (0=ไม่จำกัด)</label>
                            <input type="number" name="max_uses" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="add_promotion" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
