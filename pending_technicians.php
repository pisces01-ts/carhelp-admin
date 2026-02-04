<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/pagination.php';
require_once 'includes/admin_log.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;

// Handle approve/reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    if (!validateActionCsrf()) {
        $_SESSION['msg'] = 'CSRF token ไม่ถูกต้อง';
        $_SESSION['msg_type'] = 'danger';
        header("Location: pending_technicians.php");
        exit();
    }
    
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $conn->query("UPDATE users SET status = 'active' WHERE user_id = $id AND role = 'technician'");
        $conn->query("UPDATE technician_profiles SET status = 'approved' WHERE user_id = $id");
        logAdminAction('approve', 'technician', $id);
        $_SESSION['msg'] = 'อนุมัติช่างเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    } elseif ($action === 'reject') {
        $conn->query("UPDATE users SET status = 'inactive' WHERE user_id = $id AND role = 'technician'");
        $conn->query("UPDATE technician_profiles SET status = 'rejected' WHERE user_id = $id");
        logAdminAction('reject', 'technician', $id);
        $_SESSION['msg'] = 'ปฏิเสธช่างเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'warning';
    }
    
    header("Location: pending_technicians.php");
    exit();
}

// Count pending
$countResult = $conn->query("SELECT COUNT(*) as total FROM users u 
    JOIN technician_profiles p ON u.user_id = p.user_id 
    WHERE u.role = 'technician' AND u.status = 'pending'");
$total = $countResult->fetch_assoc()['total'];

$pagination = getPagination($total, $perPage, $page);

// Get pending technicians
$sql = "SELECT u.user_id, u.fullname, u.phone, u.id_card, u.created_at,
        p.vehicle_model, p.vehicle_plate, p.expertise
        FROM users u 
        JOIN technician_profiles p ON u.user_id = p.user_id 
        WHERE u.role = 'technician' AND u.status = 'pending'
        ORDER BY u.created_at DESC
        LIMIT {$pagination['offset']}, $perPage";
$result = $conn->query($sql);
?>

<h2 class="mb-4"><i class="fas fa-user-clock me-2"></i>อนุมัติช่างใหม่</h2>

<?php if (isset($_SESSION['msg'])): ?>
<div class="alert alert-<?= $_SESSION['msg_type'] ?> alert-dismissible fade show">
    <?= $_SESSION['msg'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['msg'], $_SESSION['msg_type']); endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>รอการอนุมัติ: <?= $total ?> คน</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>เบอร์โทร</th>
                        <th>บัตรประชาชน</th>
                        <th>รถ</th>
                        <th>ความเชี่ยวชาญ</th>
                        <th>สมัครเมื่อ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = $pagination['offset'] + 1; while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($row['fullname']) ?></strong></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['id_card'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($row['vehicle_model'] . ' ' . $row['vehicle_plate']) ?></td>
                            <td><?= htmlspecialchars($row['expertise'] ?: '-') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td class="text-center">
                                <a href="<?= getDeleteUrl('pending_technicians.php', ['action' => 'approve', 'id' => $row['user_id']]) ?>" 
                                   class="btn btn-success btn-sm" onclick="return confirm('ยืนยันอนุมัติช่างคนนี้?')">
                                    <i class="fas fa-check"></i> อนุมัติ
                                </a>
                                <a href="<?= getDeleteUrl('pending_technicians.php', ['action' => 'reject', 'id' => $row['user_id']]) ?>" 
                                   class="btn btn-danger btn-sm" onclick="return confirm('ยืนยันปฏิเสธช่างคนนี้?')">
                                    <i class="fas fa-times"></i> ปฏิเสธ
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">ไม่มีช่างรออนุมัติ</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($pagination['totalPages'] > 1): ?>
    <div class="card-footer">
        <?= renderPagination($pagination, 'pending_technicians.php') ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
