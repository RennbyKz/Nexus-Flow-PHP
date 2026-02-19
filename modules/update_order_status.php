<?php
session_start();
include '../config/db.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $request_id = $_GET['id'];
    $new_status = $_GET['status']; // SHIPPED သို့မဟုတ် CANCELLED
    $supplier_id = $_SESSION['supplier_id'];

    try {
        // မိမိနဲ့ဆိုင်တဲ့ Order ကိုပဲ Update လုပ်ခွင့်ရှိအောင် supplier_id ပါ စစ်ပါတယ်
        $stmt = $pdo->prepare("UPDATE stock_requests SET status = ? WHERE request_id = ? AND supplier_id = ?");
        $stmt->execute([$new_status, $request_id, $supplier_id]);

        header("Location: supplier_dashboard.php?success=status_updated");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}