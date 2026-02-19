<?php
session_start();
include '../config/db.php';
require '../vendor/autoload.php'; // Dompdf ကို ခေါ်ယူခြင်း

use Dompdf\Dompdf;
use Dompdf\Options;

// ၁။ PDF Options သတ်မှတ်ခြင်း
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// ၂။ Inventory Data ဆွဲယူခြင်း
$query = "SELECT p.*, b.branch_name FROM products p JOIN branches b ON p.branch_id = b.branch_id ORDER BY b.branch_name ASC";
$products = $pdo->query($query)->fetchAll();

// ၃။ PDF အတွက် HTML ဒီဇိုင်း ရေးသားခြင်း
$html = '
<style>
    body { font-family: sans-serif; color: #333; }
    .header { text-align: center; margin-bottom: 30px; }
    .header h1 { margin: 0; color: #1C4D8D; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th { background-color: #f8f9fa; color: #666; font-size: 12px; text-transform: uppercase; padding: 12px; border-bottom: 2px solid #ddd; }
    td { padding: 12px; border-bottom: 1px solid #eee; font-size: 11px; }
    .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 10px; color: #aaa; }
    .low-stock { color: #d9534f; font-weight: bold; }
</style>

<div class="header">
    <h1>SMART LOGISTICS ERP</h1>
    <p>Inventory Assets Master Report</p>
    <small>Generated on: ' . date('d M Y, h:i A') . ' | Operator: ' . $_SESSION['username'] . '</small>
</div>

<table>
    <thead>
        <tr>
            <th>Product Name</th>
            <th>Branch</th>
            <th>Price (MMK)</th>
            <th>Stock Qty</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>';

foreach ($products as $p) {
    $meta = json_decode($p['metadata'], true);
    $is_low = ($p['quantity'] < ($meta['threshold'] ?? 5));
    $status = $is_low ? '<span class="low-stock">LOW STOCK</span>' : 'HEALTHY';
    
    $html .= '
        <tr>
            <td>' . htmlspecialchars($p['product_name']) . '</td>
            <td>' . htmlspecialchars($p['branch_name']) . '</td>
            <td>' . number_format($p['price']) . '</td>
            <td align="center">' . $p['quantity'] . '</td>
            <td align="right">' . $status . '</td>
        </tr>';
}

$html .= '
    </tbody>
</table>

<div class="footer">
    Smart Logistics System - Confidential Report
</div>';

// ၄။ PDF ထုတ်ယူခြင်း
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// ၅။ Download ချပေးခြင်း
$dompdf->stream("Inventory_Report_" . date('Y-m-d') . ".pdf", ["Attachment" => true]);
exit();