<?php
include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/functions.php';

// ယနေ့ပြုလုပ်ခဲ့သော Audit Logs များကို ဆွဲထုတ်ခြင်း
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT u.username, l.action, l.timestamp FROM audit_logs l JOIN users u ON l.user_id = u.user_id WHERE DATE(l.timestamp) = ? ORDER BY l.timestamp ASC");
$stmt->execute([$today]);
$logs = $stmt->fetchAll();

$admin_id = "1162211533";

if ($logs) {
    $report = "📝 <b>Daily Audit Digest (" . date('d-M-Y') . ")</b>\n--------------------------------\n";
    foreach ($logs as $l) {
        $report .= "• [" . date('h:i A', strtotime($l['timestamp'])) . "] " . $user['username'] . ": " . $l['action'] . "\n";
    }
} else {
    $report = "📝 <b>Daily Digest:</b> ယနေ့အတွက် Audit Logs မရှိပါ။";
}

sendTelegramNotification($report, $admin_id);