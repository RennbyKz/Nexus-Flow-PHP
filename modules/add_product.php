<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['product_id'] ?? null;
    $name = $_POST['product_name'];
    $branch_id = $_POST['branch_id'];
    $supplier_id = $_POST['supplier_id']; // ⭐ အသစ်ထည့်သွင်းခြင်း
    $price = $_POST['price'];
    $qty = $_POST['quantity'];
    
    $metadata = json_encode([
        "threshold" => (int)$_POST['threshold'],
        "brand" => $_POST['brand'] ?? 'Generic',
        "unit" => $_POST['unit'] ?? 'pcs'
    ]);

    try {
        if ($id) {
            // Edit လုပ်ခြင်း Logic (Supplier ID ကိုပါ Update လုပ်ပါမည်)
            $sql = "UPDATE products SET product_name=?, price=?, quantity=?, supplier_id=?, metadata=?::jsonb WHERE product_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $price, $qty, $supplier_id, $metadata, $id]);
        } else {
            // Add အသစ်ထည့်ခြင်း Logic (Supplier ID ကိုပါ Insert လုပ်ပါမည်)
            $image_url = 'assets/uploads/products/default.png';
            
            $sql = "INSERT INTO products (branch_id, product_name, price, quantity, supplier_id, metadata, image_url) VALUES (?, ?, ?, ?, ?, ?::jsonb, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$branch_id, $name, $price, $qty, $supplier_id, $metadata, $image_url]);
        }
        header("Location: inventory.php?success=1");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}