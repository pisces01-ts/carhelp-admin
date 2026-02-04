<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/admin_log.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!validateActionCsrf()) {
    header("Location: ../manage_services.php");
    exit();
}

// Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Check if service is in use
    $check = $conn->prepare("SELECT COUNT(*) as cnt FROM service_requests WHERE problem_type = (SELECT name FROM repair_types WHERE id = ?)");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    
    if ($result['cnt'] > 0) {
        $_SESSION['msg'] = 'ไม่สามารถลบได้ เนื่องจากมีงานซ่อมที่ใช้บริการนี้อยู่';
        $_SESSION['msg_type'] = 'danger';
    } else {
        $stmt = $conn->prepare("DELETE FROM repair_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        logAdminAction('delete', 'service', $id);
        
        $_SESSION['msg'] = 'ลบบริการเรียบร้อยแล้ว';
        $_SESSION['msg_type'] = 'success';
    }
    
    header("Location: ../manage_services.php");
    exit();
}

// Save
if (isset($_POST['save'])) {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name']);
    
    // Check duplicate
    $checkSql = "SELECT id FROM repair_types WHERE name = ? AND id != ?";
    $check = $conn->prepare($checkSql);
    $check->bind_param("si", $name, $id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['msg'] = 'ชื่อบริการนี้มีอยู่แล้ว';
        $_SESSION['msg_type'] = 'danger';
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE repair_types SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            
            logAdminAction('update', 'service', $id, ['name' => $name]);
            
            $_SESSION['msg'] = 'อัปเดตบริการเรียบร้อยแล้ว';
        } else {
            $stmt = $conn->prepare("INSERT INTO repair_types (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $newId = $conn->insert_id;
            
            logAdminAction('create', 'service', $newId, ['name' => $name]);
            
            $_SESSION['msg'] = 'เพิ่มบริการใหม่เรียบร้อยแล้ว';
        }
        $_SESSION['msg_type'] = 'success';
    }
    
    header("Location: ../manage_services.php");
    exit();
}

header("Location: ../manage_services.php");
