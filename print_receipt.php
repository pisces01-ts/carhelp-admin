<?php
require_once 'includes/db_connect.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { die('ไม่พบรายการ'); }

$job = $conn->query("SELECT r.*, 
    c.fullname as customer_name, c.phone as customer_phone,
    t.fullname as technician_name, t.phone as technician_phone,
    tp.vehicle_plate
    FROM service_requests r
    LEFT JOIN users c ON r.customer_id = c.user_id
    LEFT JOIN users t ON r.technician_id = t.user_id
    LEFT JOIN technician_profiles tp ON t.user_id = tp.user_id
    WHERE r.request_id = $id")->fetch_assoc();

if (!$job) { die('ไม่พบข้อมูลงาน'); }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จ #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; padding: 20px; max-width: 800px; margin: auto; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { color: #666; }
        .receipt-no { background: #f5f5f5; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        .section h3 { font-size: 14px; color: #666; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .row { display: flex; margin-bottom: 8px; }
        .label { width: 120px; color: #666; }
        .value { flex: 1; font-weight: 500; }
        .price-box { background: #f0f8ff; padding: 15px; border-radius: 5px; text-align: right; }
        .price-box .total { font-size: 24px; font-weight: bold; color: #2196F3; }
        .footer { margin-top: 40px; text-align: center; color: #999; font-size: 12px; }
        .signature { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature div { width: 200px; text-align: center; }
        .signature .line { border-top: 1px solid #333; margin-top: 50px; padding-top: 5px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer;">
            🖨️ พิมพ์ใบเสร็จ
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; margin-left: 10px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer;">
            ✕ ปิด
        </button>
    </div>

    <div class="header">
        <h1>🚗 CarHelp - ใบเสร็จรับเงิน</h1>
        <p>บริการช่วยเหลือรถยนต์ฉุกเฉิน 24 ชั่วโมง</p>
    </div>

    <div class="receipt-no">
        <strong>เลขที่ใบเสร็จ:</strong> #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>วันที่:</strong> <?= date('d/m/Y H:i', strtotime($job['completed_time'] ?? $job['request_time'])) ?>
    </div>

    <div class="section">
        <h3>👤 ข้อมูลลูกค้า</h3>
        <div class="row"><span class="label">ชื่อ:</span><span class="value"><?= htmlspecialchars($job['customer_name']) ?></span></div>
        <div class="row"><span class="label">เบอร์โทร:</span><span class="value"><?= $job['customer_phone'] ?></span></div>
        <div class="row"><span class="label">สถานที่:</span><span class="value"><?= htmlspecialchars($job['location_address'] ?? '-') ?></span></div>
    </div>

    <div class="section">
        <h3>🔧 ข้อมูลงานซ่อม</h3>
        <div class="row"><span class="label">ประเภท:</span><span class="value"><?= htmlspecialchars($job['problem_type']) ?></span></div>
        <div class="row"><span class="label">รายละเอียด:</span><span class="value"><?= htmlspecialchars($job['problem_details'] ?? '-') ?></span></div>
    </div>

    <div class="section">
        <h3>👨‍🔧 ข้อมูลช่าง</h3>
        <div class="row"><span class="label">ชื่อ:</span><span class="value"><?= htmlspecialchars($job['technician_name'] ?? '-') ?></span></div>
        <div class="row"><span class="label">เบอร์โทร:</span><span class="value"><?= $job['technician_phone'] ?? '-' ?></span></div>
        <div class="row"><span class="label">ทะเบียนรถ:</span><span class="value"><?= $job['vehicle_plate'] ?? '-' ?></span></div>
    </div>

    <div class="price-box">
        <div style="margin-bottom: 5px;">ค่าบริการทั้งหมด</div>
        <div class="total">฿<?= number_format($job['price'] ?? 0, 2) ?></div>
    </div>

    <div class="signature">
        <div>
            <div class="line">ลูกค้า</div>
        </div>
        <div>
            <div class="line">ช่างผู้ให้บริการ</div>
        </div>
    </div>

    <div class="footer">
        <p>ขอบคุณที่ใช้บริการ CarHelp</p>
        <p>หากมีข้อสงสัย กรุณาติดต่อ 02-XXX-XXXX</p>
    </div>
</body>
</html>
