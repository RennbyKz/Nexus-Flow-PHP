<?php
include_once '../config/db.php';

if (isset($_GET['supplier_id'])) {
    $sid = $_GET['supplier_id'];
    // Supplier ID နှင့် ချိတ်ဆက်ထားသော products များကို ရှာခြင်း
    $stmt = $pdo->prepare("SELECT product_name FROM products WHERE supplier_id = ? GROUP BY product_name");
    $stmt->execute([$sid]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($products);
    exit();
}