<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/pagination.php';
require_once 'includes/admin_log.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$status = $_GET['status'] ?? '';

// Handle actions
if (isset($_POST['update_complaint'])) {
    if (!validateActionCsrf()) {
        $_SESSION['msg'] = 'CSRF token ไม่ถูกต้อง';
        $_SESSION['msg_type'] = 'danger';
    } else {
        $id = intval($_POST['complaint_id']);
        $newStatus = $_POST['status'];
        $response = trim($_POST['admin_response'] ?? '');
        
        $sql = "UPDATE complaints SET status = ?, admin_response = ?";
        if ($newStatus === 'resolved') {
            $sql .= ", resolved_at = NOW()";
        }
        $sql .= " WHERE complaint_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $newStatus, $response, $id);
        $stmt->execute();
        
        logAdminAction('update', 'complaint', $id, ['status' => $newStatus]);
        
        $_SESSION['msg'] = 'อัปเดตสถานะเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: manage_complaints.php");
    exit();
}

// Count
$where = "1=1";
if ($status) $where .= " AND c.status = '$status'";

$countResult = $conn->query("SELECT COUNT(*) as total FROM complaints c WHERE $where");
$total = $countResult->fetch_assoc()['total'];
$pagination = getPagination($total, $perPage, $page);

// Get complaints
$sql = "SELECT c.*, 
        u.fullname as customer_name, u.phone as customer_phone,
        t.fullname as technician_name
        FROM complaints c
        LEFT JOIN users u ON c.customer_id = u.user_id
        LEFT JOIN users t ON c.technician_id = t.user_id
        WHERE $where
        ORDER BY c.created_at DESC
        LIMIT {$pagination['offset']}, $perPage";
$result = $conn->query($sql);
?>

<h2 class="mb-4"><i class="fas fa-exclamation-triangle me-2"></i>จัดการเรื่องร้องเรียน</h2>

<?php if (isset($_SESSION['msg'])): ?>
<div class="alert alert-<?= $_SESSION['msg_type'] ?> alert-dismissible fade show">
    <?= $_SESSION['msg'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['msg'], $_SESSION['msg_type']); endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>ทั้งหมด: <?= $total ?> รายการ</span>
        <div>
            <a href="?status=" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-outline-primary' ?>">ทั้งหมด</a>
            <a href="?status=pending" class="btn btn-sm <?= $status=='pending' ? 'btn-warning' : 'btn-outline-warning' ?>">รอดำเนินการ</a>
            <a href="?status=in_progress" class="btn btn-sm <?= $status=='in_progress' ? 'btn-info' : 'btn-outline-info' ?>">กำลังดำเนินการ</a>
            <a href="?status=resolved" class="btn btn-sm <?= $status=='resolved' ? 'btn-success' : 'btn-outline-success' ?>">แก้ไขแล้ว</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>หัวข้อ</th>
                        <th>ลูกค้า</th>
                        <th>ช่าง</th>
                        <th>สถานะ</th>
                        <th>วันที่</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = $pagination['offset'] + 1; while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['subject']) ?></strong>
                                <br><small class="text-muted"><?= mb_substr(htmlspecialchars($row['message']), 0, 50) ?>...</small>
                            </td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= htmlspecialchars($row['technician_name'] ?: '-') ?></td>
                            <td>
                                <?php
                                $statusBadge = match($row['status']) {
                                    'pending' => 'bg-warning',
                                    'in_progress' => 'bg-info',
                                    'resolved' => 'bg-success',
                                    'rejected' => 'bg-secondary',
                                    default => 'bg-secondary'
                                };
                                $statusText = match($row['status']) {
                                    'pending' => 'รอดำเนินการ',
                                    'in_progress' => 'กำลังดำเนินการ',
                                    'resolved' => 'แก้ไขแล้ว',
                                    'rejected' => 'ปฏิเสธ',
                                    default => $row['status']
                                };
                                ?>
                                <span class="badge <?= $statusBadge ?>"><?= $statusText ?></span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td class="text-center">
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal<?= $row['complaint_id'] ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Modal -->
                        <div class="modal fade" id="modal<?= $row['complaint_id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="complaint_id" value="<?= $row['complaint_id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?= htmlspecialchars($row['subject']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>ลูกค้า:</strong> <?= htmlspecialchars($row['customer_name']) ?> (<?= $row['customer_phone'] ?>)</p>
                                            <p><strong>ช่าง:</strong> <?= htmlspecialchars($row['technician_name'] ?: '-') ?></p>
                                            <p><strong>รายละเอียด:</strong></p>
                                            <div class="bg-light p-3 rounded mb-3"><?= nl2br(htmlspecialchars($row['message'])) ?></div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">สถานะ</label>
                                                <select name="status" class="form-select">
                                                    <option value="pending" <?= $row['status']=='pending'?'selected':'' ?>>รอดำเนินการ</option>
                                                    <option value="in_progress" <?= $row['status']=='in_progress'?'selected':'' ?>>กำลังดำเนินการ</option>
                                                    <option value="resolved" <?= $row['status']=='resolved'?'selected':'' ?>>แก้ไขแล้ว</option>
                                                    <option value="rejected" <?= $row['status']=='rejected'?'selected':'' ?>>ปฏิเสธ</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ตอบกลับ</label>
                                                <textarea name="admin_response" class="form-control" rows="3"><?= htmlspecialchars($row['admin_response'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                            <button type="submit" name="update_complaint" class="btn btn-primary">บันทึก</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">ไม่มีเรื่องร้องเรียน</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($pagination['totalPages'] > 1): ?>
    <div class="card-footer">
        <?= renderPagination($pagination, 'manage_complaints.php' . ($status ? "?status=$status&" : '?')) ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
