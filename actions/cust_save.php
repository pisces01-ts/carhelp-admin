<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/admin_log.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!validateActionCsrf()) {
    header("Location: ../manage_customers.php");
    exit();
}

// Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['user_id'])) {
    $id = intval($_GET['user_id']);
    
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM reviews WHERE customer_id = $id");
        $conn->query("DELETE FROM service_requests WHERE customer_id = $id");
        
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'customer'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        logAdminAction('delete', 'customer', $id);
        
        $conn->commit();
        $_SESSION['msg'] = 'ลบข้อมูลลูกค้าเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['msg'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $_SESSION['msg_type'] = 'danger';
    }
    
    header("Location: ../manage_customers.php");
    exit();
}

// Save
if (isset($_POST['save'])) {
    $id = intval($_POST['user_id'] ?? 0);
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($id > 0) {
        // Update
        $sql = "UPDATE users SET fullname = ?, phone = ?, email = ?, address = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $fullname, $phone, $email, $address, $id);
        $stmt->execute();
        
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt2->bind_param("si", $hash, $id);
            $stmt2->execute();
        }
        
        logAdminAction('update', 'customer', $id, ['fullname' => $fullname]);
        
        $_SESSION['msg'] = 'อัปเดตข้อมูลเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    } else {
        // Create
        $hash = password_hash($password ?: '123456', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (fullname, phone, email, address, password, role, status) VALUES (?, ?, ?, ?, ?, 'customer', 'active')");
        $stmt->bind_param("sssss", $fullname, $phone, $email, $address, $hash);
        $stmt->execute();
        $newId = $conn->insert_id;
        
        logAdminAction('create', 'customer', $newId, ['fullname' => $fullname]);
        
        $_SESSION['msg'] = 'เพิ่มลูกค้าใหม่เรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    }
    
    header("Location: ../manage_customers.php");
    exit();
}

header("Location: ../manage_customers.php");
