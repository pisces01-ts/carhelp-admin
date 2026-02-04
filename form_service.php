<?php
require_once 'includes/header.php'; 
require_once 'includes/db_connect.php';
require_once 'includes/admin_log.php';

$id = "";
$name = "";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM repair_types WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $name = $row['name'];
    } else {
        header("Location: manage_services.php");
        exit();
    }
    $stmt->close();
}
?>

<div class="container-fluid" style="max-width: 600px;">
    
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
                    <div class="me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-tools text-white fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold m-0 text-white"><?php echo $id ? 'แก้ไขบริการ #'.str_pad($id, 3, '0', STR_PAD_LEFT) : 'เพิ่มบริการใหม่'; ?></h5>
                        <small class="text-white-50">จัดการประเภทบริการ</small>
                    </div>
                </div>
                <a href="manage_services.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> ย้อนกลับ
                </a>
            </div>
        </div>
        <div class="p-4">
            <form action="actions/service_save.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <div class="mb-4">
                    <label class="form-label fw-bold">ชื่อบริการ <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required autofocus>
                </div>

                <button type="submit" name="save" class="btn btn-primary-custom w-100 py-2">
                    <i class="fas fa-save me-2"></i> บันทึกข้อมูล
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
