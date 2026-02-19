<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
authorize(['ADMIN', 'MANAGER']);

if (isset($_POST['confirm_receive'])) {
    $request_id = $_POST['request_id'];
    $actual_qty = $_POST['actual_qty']; 

    try {
        $pdo->beginTransaction();

        // ၁။ Status ကို 'RECEIVED' သို့ ပြောင်းလဲခြင်း
        // ဤနေရာတွင် Update လုပ်လိုက်သည်နှင့် Database Trigger စတင်အလုပ်လုပ်မည်ဖြစ်သည်
        $updateReq = $pdo->prepare("
            UPDATE stock_requests 
            SET status = 'RECEIVED', 
                received_at = NOW(), 
                remark = ? 
            WHERE request_id = ? AND status = 'SHIPPED'
        ");
        
        $remark = "Received $actual_qty units";
        $updateReq->execute([$remark, $request_id]);

        // အကယ်၍ Status ပြောင်းလဲမှု အောင်မြင်ရင် (Row တစ်ခု Update ဖြစ်သွားရင်)
        if ($updateReq->rowCount() > 0) {
            $pdo->commit(); 
            header("Location: shipment_tracking.php?msg=success");
            exit();
        } else {
            throw new Exception("Order could not be updated. It might already be received.");
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: shipment_tracking.php");
    exit();
}