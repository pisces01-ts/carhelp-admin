<?php
require_once __DIR__ . '/db_connect.php';

function logAdminAction($action, $targetType, $targetId = null, $details = null) {
    global $conn;
    
    $adminId = $_SESSION['admin_id'] ?? 0;
    $adminName = $_SESSION['admin_name'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, admin_name, action, target_type, target_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $adminId, $adminName, $action, $targetType, $targetId, $detailsJson, $ip, $userAgent);
    $stmt->execute();
    $stmt->close();
}

function validateActionCsrf() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
        $_SESSION['msg'] = 'CSRF token ไม่ถูกต้อง กรุณาลองใหม่';
        $_SESSION['msg_type'] = 'danger';
        return false;
    }
    return true;
}

function getDeleteUrl($baseUrl, $id, $idParam = 'id') {
    $token = $_SESSION['csrf_token'] ?? '';
    return "{$baseUrl}?{$idParam}={$id}&action=delete&csrf_token={$token}";
}
