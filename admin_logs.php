<?php
require_once 'includes/header.php'; 
require_once 'includes/db_connect.php';
require_once 'includes/admin_log.php';
require_once 'includes/pagination.php';

$pagination = getPaginationData($conn, 'admin_logs', '', 30);

$sql = "SELECT * FROM admin_logs ORDER BY created_at DESC {$pagination['limit']}";
$result = $conn->query($sql);

$actionLabels = [
    'create' => ['สร้าง', 'success'],
    'update' => ['แก้ไข', 'primary'],
    'delete' => ['ลบ', 'danger']
];

$targetLabels = [
    'customer' => 'ลูกค้า',
    'technician' => 'ช่าง',
    'job' => 'งานซ่อม',
    'service' => 'บริการ'
];
?>

<div class="card-custom p-0 overflow-hidden mb-4">
    <div class="p-4" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
        <div class="d-flex align-items-center">
            <div class="me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #64748b 0%, #475569 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-history text-white fa-lg"></i>
            </div>
            <div>
                <h4 class="fw-bold m-0 text-white">Activity Logs</h4>
                <small class="text-white-50">ประวัติการใช้งาน Admin</small>
            </div>
        </div>
    </div>
</div>

<div class="card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-custom w-100 mb-0 align-middle">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">วันเวลา</th>
                    <th>Admin</th>
                    <th>การกระทำ</th>
                    <th>ประเภท</th>
                    <th>ID</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $action = $row['action'];
                        $actionLabel = $actionLabels[$action] ?? ['ไม่ระบุ', 'secondary'];
                        $targetLabel = $targetLabels[$row['target_type']] ?? $row['target_type'];
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold"><?php echo date('d/m/y', strtotime($row['created_at'])); ?></div>
                        <small class="text-muted"><?php echo date('H:i:s', strtotime($row['created_at'])); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($row['admin_name'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $actionLabel[1]; ?>">
                            <?php echo $actionLabel[0]; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($targetLabel); ?></td>
                    <td><code><?php echo htmlspecialchars($row['target_id'] ?? '-'); ?></code></td>
                    <td class="small text-muted"><?php echo htmlspecialchars($row['ip_address'] ?? '-'); ?></td>
                </tr>
                <?php 
                    }
                } else {
                ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <p class="m-0">ยังไม่มีประวัติการใช้งาน</p>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    
    <?php echo renderPagination($pagination); ?>
</div>

<?php include 'includes/footer.php'; ?>
