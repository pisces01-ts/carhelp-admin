<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/admin_log.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!validateActionCsrf()) {
    header("Location: ../manage_technicians.php");
    exit();
}

// Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['user_id'])) {
    $id = intval($_GET['user_id']);
    
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM reviews WHERE technician_id = $id");
        $conn->query("DELETE FROM technician_profiles WHERE user_id = $id");
        $conn->query("UPDATE service_requests SET technician_id = NULL WHERE technician_id = $id");
        
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'technician'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        logAdminAction('delete', 'technician', $id);
        
        $conn->commit();
        $_SESSION['msg'] = 'ลบข้อมูลช่างเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['msg'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $_SESSION['msg_type'] = 'danger';
    }
    
    header("Location: ../manage_technicians.php");
    exit();
}

// Save
if (isset($_POST['save_tech'])) {
    $id = intval($_POST['user_id'] ?? 0);
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $id_card = trim($_POST['id_card'] ?? '');
    $vehicle_model = trim($_POST['vehicle_model'] ?? '');
    $vehicle_plate = trim($_POST['vehicle_plate'] ?? '');
    $expertise = trim($_POST['expertise'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($id > 0) {
        // Update user
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, id_card = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $fullname, $phone, $id_card, $id);
        $stmt->execute();
        
        // Update profile
        $stmt2 = $conn->prepare("UPDATE technician_profiles SET vehicle_model = ?, vehicle_plate = ?, expertise = ? WHERE user_id = ?");
        $stmt2->bind_param("sssi", $vehicle_model, $vehicle_plate, $expertise, $id);
        $stmt2->execute();
        
        if ($stmt2->affected_rows == 0) {
            $stmt3 = $conn->prepare("INSERT INTO technician_profiles (user_id, vehicle_model, vehicle_plate, expertise) VALUES (?, ?, ?, ?)");
            $stmt3->bind_param("isss", $id, $vehicle_model, $vehicle_plate, $expertise);
            $stmt3->execute();
        }
        
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt4 = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt4->bind_param("si", $hash, $id);
            $stmt4->execute();
        }
        
        logAdminAction('update', 'technician', $id, ['fullname' => $fullname]);
        
        $_SESSION['msg'] = 'อัปเดตข้อมูลช่างเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    } else {
        // Create
        $hash = password_hash($password ?: '123456', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (fullname, phone, id_card, password, role, status) VALUES (?, ?, ?, ?, 'technician', 'active')");
        $stmt->bind_param("ssss", $fullname, $phone, $id_card, $hash);
        $stmt->execute();
        $newId = $conn->insert_id;
        
        $stmt2 = $conn->prepare("INSERT INTO technician_profiles (user_id, vehicle_model, vehicle_plate, expertise) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("isss", $newId, $vehicle_model, $vehicle_plate, $expertise);
        $stmt2->execute();
        
        logAdminAction('create', 'technician', $newId, ['fullname' => $fullname]);
        
        $_SESSION['msg'] = 'เพิ่มช่างใหม่เรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    }
    
    header("Location: ../manage_technicians.php");
    exit();
}

header("Location: ../manage_technicians.php");
