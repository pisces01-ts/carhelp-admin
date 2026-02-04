<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
require_once 'includes/admin_log.php';

$result = $conn->query("SELECT * FROM repair_types ORDER BY id DESC");
?>

<div class="card-custom p-0 overflow-hidden mb-4">
    <div class="p-4" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-tools text-white fa-lg"></i>
                </div>
                <div>
                    <h4 class="fw-bold m-0 text-white">จัดการบริการ</h4>
                    <small class="text-white-50">ประเภทบริการ/อาการเสีย</small>
                </div>
            </div>
            <a href="form_service.php" class="btn btn-light">
                <i class="fas fa-plus me-2"></i> เพิ่มบริการใหม่
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
        <h5 class="fw-bold m-0 text-dark"><i class="fas fa-list me-2 text-primary"></i>รายการบริการ</h5>
    </div>

    <div class="table-responsive">
        <table class="table table-custom w-100 mb-0 align-middle">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>ชื่อบริการ</th>
                    <th>สถานะ</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="ps-4"><strong>#<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo ($row['is_active'] ?? 1) ? 'success' : 'danger'; ?>">
                            <?php echo ($row['is_active'] ?? 1) ? 'ใช้งาน' : 'ปิด'; ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="form_service.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="<?php echo getDeleteUrl('actions/service_save.php', $row['id']); ?>" class="btn btn-sm btn-outline-danger btn-delete">
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
