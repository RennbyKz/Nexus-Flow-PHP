<?php
// includes/generate_report.php

// လက်ရှိ folder ရဲ့ အပေါ်က folder (Root) လမ်းကြောင်းကို ယူခြင်း
$base_path = dirname(__DIR__); 

// ၁။ လိုအပ်သော ဖိုင်များကို Absolute Path ဖြင့် ခေါ်ယူခြင်း
require_once $base_path . '/vendor/autoload.php'; 
include_once $base_path . '/config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ၂။ ဒေတာများ ဆွဲထုတ်ခြင်း
$stmt = $pdo->query("SELECT p.product_name, p.quantity, p.price, (p.quantity * p.price) as val 
                     FROM products p 
                     WHERE p.is_deleted = FALSE");
$rows = $stmt->fetchAll();

// ၃။ HTML Template
$html = '
<h2 style="text-align: center;">OMNI FLOW - AUDIT REPORT</h2>
<p style="text-align: center;">Generated Date: ' . date('d-M-Y h:i A') . '</p>
<table border="1" cellspacing="0" cellpadding="8" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background-color: #f2f2f2;">
            <th>Product Name</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total Value</th>
        </tr>
    </thead>
    <tbody>';

foreach ($rows as $row) {
    $html .= '<tr>
        <td>' . htmlspecialchars($row['product_name']) . '</td>
        <td style="text-align: center;">' . number_format($row['quantity']) . '</td>
        <td style="text-align: right;">' . number_format($row['price']) . '</td>
        <td style="text-align: right;">' . number_format($row['val']) . '</td>
    </tr>';
}
$html .= '</tbody></table>';

// ၄။ Dompdf တည်ဆောက်ခြင်း
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// ၅။ ဖိုင်သိမ်းဆည်းခြင်း
$file_name = 'audit_report_' . time() . '.pdf';
$file_path = $base_path . '/reports/' . $file_name;

if (!file_exists($base_path . '/reports')) {
    mkdir($base_path . '/reports', 0777, true);
}

file_put_contents($file_path, $dompdf->output());

// ၆။ Bot အတွက် Path ထုတ်ပေးခြင်း
echo realpath($file_path);