<?php
require_once 'includes/db_connect.php';

$period = $_GET['period'] ?? 'month';
$commissionRate = 15;

switch ($period) {
    case 'today': $dateCondition = "DATE(completed_time) = CURDATE()"; $periodText = "วันนี้"; break;
    case 'week': $dateCondition = "YEARWEEK(completed_time) = YEARWEEK(CURDATE())"; $periodText = "สัปดาห์นี้"; break;
    case 'year': $dateCondition = "YEAR(completed_time) = YEAR(CURDATE())"; $periodText = "ปี " . date('Y'); break;
    default: $dateCondition = "MONTH(completed_time) = MONTH(CURDATE()) AND YEAR(completed_time) = YEAR(CURDATE())"; $periodText = date('F Y');
}

$summary = $conn->query("SELECT COUNT(*) as total_jobs, COALESCE(SUM(price), 0) as total_revenue, COALESCE(SUM(price * $commissionRate / 100), 0) as commission FROM service_requests WHERE status = 'completed' AND $dateCondition")->fetch_assoc();

$jobs = $conn->query("SELECT r.*, u.fullname as customer_name, t.fullname as technician_name, (r.price * $commissionRate / 100) as commission FROM service_requests r LEFT JOIN users u ON r.customer_id = u.user_id LEFT JOIN users t ON r.technician_id = t.user_id WHERE r.status = 'completed' AND $dateCondition ORDER BY r.completed_time DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานบัญชีรายรับ - <?= $periodText ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; padding: 20px; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .summary { background: #f0f8ff; padding: 15px; margin-top: 20px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .footer { margin-top: 40px; }
        .signature { display: flex; justify-content: space-between; margin-top: 60px; }
        .signature div { text-align: center; width: 200px; }
        .signature .line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #2196F3; color: white; border: none; cursor: pointer;">🖨️ พิมพ์</button>
        <button onclick="window.close()" style="padding: 8px 16px; margin-left: 10px;">✕ ปิด</button>
    </div>

    <div class="header">
        <h1>CarHelp - รายงานบัญชีรายรับ</h1>
        <p>ช่วงเวลา: <?= $periodText ?></p>
        <p>วันที่พิมพ์: <?= date('d/m/Y H:i') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>ลูกค้า</th>
                <th>ช่าง</th>
                <th>ประเภท</th>
                <th class="text-right">ราคา</th>
                <th class="text-right">ค่าคอม <?= $commissionRate ?>%</th>
                <th>วันที่</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($job = $jobs->fetch_assoc()): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($job['customer_name']) ?></td>
                <td><?= htmlspecialchars($job['technician_name']) ?></td>
                <td><?= htmlspecialchars($job['problem_type']) ?></td>
                <td class="text-right">฿<?= number_format($job['price'], 0) ?></td>
                <td class="text-right">฿<?= number_format($job['commission'], 0) ?></td>
                <td><?= date('d/m/Y', strtotime($job['completed_time'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row"><span>จำนวนงานทั้งหมด:</span><strong><?= number_format($summary['total_jobs']) ?> งาน</strong></div>
        <div class="summary-row"><span>รายได้รวม:</span><strong>฿<?= number_format($summary['total_revenue'], 0) ?></strong></div>
        <div class="summary-row"><span>ค่าคอมมิชชั่นรวม (<?= $commissionRate ?>%):</span><strong style="color: green;">฿<?= number_format($summary['commission'], 0) ?></strong></div>
    </div>

    <div class="signature">
        <div><div class="line">ผู้จัดทำ</div></div>
        <div><div class="line">ผู้ตรวจสอบ</div></div>
        <div><div class="line">ผู้อนุมัติ</div></div>
    </div>

    <div class="footer">
        <p style="text-align: center; color: #999; margin-top: 20px;">เอกสารนี้จัดทำโดยระบบ CarHelp Admin</p>
    </div>
</body>
</html>
