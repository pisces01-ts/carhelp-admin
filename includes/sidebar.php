<?php 
$p = basename($_SERVER['PHP_SELF']); 
?>

<nav class="sidebar d-flex flex-column" id="sidebar">
    
    <div class="brand">
        <i class="fa-solid fa-car-burst text-primary"></i> <span class="brand-text">CarHelp <span class="text-primary">Admin</span></span>
    </div>

    <a href="dashboard.php" class="nav-link <?php echo ($p == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-pie"></i> 
        <span class="nav-text">ภาพรวม</span>
    </a>

    <a href="manage_technicians.php" class="nav-link <?php echo ($p == 'manage_technicians.php' || $p == 'form_technician.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-gear"></i> 
        <span class="nav-text">จัดการช่าง</span>
    </a>

    <a href="manage_customers.php" class="nav-link <?php echo ($p == 'manage_customers.php' || $p == 'form_customer.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-users"></i> 
        <span class="nav-text">จัดการลูกค้า</span>
    </a>

    <a href="manage_services.php" class="nav-link <?php echo ($p == 'manage_services.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-list-check"></i> 
        <span class="nav-text">ประเภทบริการ</span>
    </a>

    <a href="manage_jobs.php" class="nav-link <?php echo ($p == 'manage_jobs.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-clipboard-list"></i> 
        <span class="nav-text">รายการซ่อม</span>
    </a>

    <a href="manage_financial.php" class="nav-link <?php echo ($p == 'manage_financial.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-wallet"></i> 
        <span class="nav-text">รายรับ</span>
    </a>

    <a href="reviews.php" class="nav-link <?php echo ($p == 'reviews.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-star"></i> 
        <span class="nav-text">รีวิวจากลูกค้า</span>
    </a>

    <a href="admin_logs.php" class="nav-link <?php echo ($p == 'admin_logs.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-history"></i> 
        <span class="nav-text">Activity Logs</span>
    </a>

    <div class="mt-auto mb-3">
        <a href="profile.php" class="nav-link <?php echo ($p == 'profile.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-shield"></i> 
            <span class="nav-text">ตั้งค่าบัญชี</span>
        </a>
        
        <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 20px;">

        <a href="logout.php" class="nav-link text-danger btn-logout">
            <i class="fa-solid fa-right-from-bracket"></i> 
            <span class="nav-text">ออกจากระบบ</span>
        </a>
    </div>
</nav>
