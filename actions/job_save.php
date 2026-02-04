<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/admin_log.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!validateActionCsrf()) {
    header("Location: ../manage_jobs.php");
    exit();
}

// Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['req_id'])) {
    $id = intval($_GET['req_id']);
    
    $stmt = $conn->prepare("DELETE FROM service_requests WHERE request_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    logAdminAction('delete', 'job', $id);
    
    $_SESSION['msg'] = 'ลบงานซ่อมเรียบร้อยแล้ว';
    $_SESSION['msg_type'] = 'success';
    
    header("Location: ../manage_jobs.php");
    exit();
}

// Update
if (isset($_POST['update_job'])) {
    $id = intval($_POST['req_id']);
    $status = $_POST['status'];
    $tech_id = intval($_POST['tech_id']);
    $price = floatval($_POST['price']);
    
    $stmt = $conn->prepare("UPDATE service_requests SET status = ?, technician_id = ?, price = ? WHERE request_id = ?");
    $techIdValue = $tech_id > 0 ? $tech_id : null;
    $stmt->bind_param("sidi", $status, $techIdValue, $price, $id);
    $stmt->execute();
    
    logAdminAction('update', 'job', $id, ['status' => $status, 'price' => $price]);
    
    $_SESSION['msg'] = 'อัปเดตงานซ่อมเรียบร้อยแล้ว';
    $_SESSION['msg_type'] = 'success';
    
    header("Location: ../form_job.php?id=" . $id);
    exit();
}

header("Location: ../manage_jobs.php");
