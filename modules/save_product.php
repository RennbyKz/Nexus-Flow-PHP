<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
authorize(['ADMIN', 'MANAGER']);

if (isset($_POST['save_product'])) {
    $product_id   = $_POST['product_id'];
    $product_name = trim($_POST['product_name']);
    $branch_id    = $_POST['branch_id'];
    $supplier_id  = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
    $price        = $_POST['price'];
    $quantity     = $_POST['quantity'];
    
    // ⭐ အသစ်ထည့်သွင်းထားသော field များ
    $category     = !empty($_POST['category']) ? trim($_POST['category']) : 'General';
    $metadata     = $_POST['metadata']; // inventory.php ကပို့လိုက်တဲ့ JSON string

    // 📸 ၁။ ဓာတ်ပုံတင်ခြင်း Logic
    $image_url = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $target_dir = "../assets/uploads/products/";
        $file_name = time() . "_" . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_url = "assets/uploads/products/" . $file_name;
        }
    }

    try {
        if (!empty($product_id)) {
            // 📝 ၂။ Edit (Update) လုပ်ခြင်း Logic
            if ($image_url) {
                // ဓာတ်ပုံအသစ်ပါလျှင် image_url ကိုပါ update လုပ်သည်
                $sql = "UPDATE products 
                        SET product_name = ?, branch_id = ?, supplier_id = ?, price = ?, quantity = ?, category = ?, metadata = ?, image_url = ?
                        WHERE product_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$product_name, $branch_id, $supplier_id, $price, $quantity, $category, $metadata, $image_url, $product_id]);
            } else {
                // ဓာတ်ပုံအသစ်မပါလျှင် image_url ကို ချန်လှပ်ထားသည်
                $sql = "UPDATE products 
                        SET product_name = ?, branch_id = ?, supplier_id = ?, price = ?, quantity = ?, category = ?, metadata = ?
                        WHERE product_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$product_name, $branch_id, $supplier_id, $price, $quantity, $category, $metadata, $product_id]);
            }
        } else {
            // 🆕 ၃။ အသစ်ထည့်ခြင်း Logic
            $final_photo = $image_url ? $image_url : 'assets/uploads/products/default.png';
            $sql = "INSERT INTO products (product_name, branch_id, supplier_id, price, quantity, category, metadata, image_url) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$product_name, $branch_id, $supplier_id, $price, $quantity, $category, $metadata, $final_photo]);
        }
        
        header("Location: inventory.php?msg=success");
        exit();
    } catch (PDOException $e) {
        die("Update Error: " . $e->getMessage());
    }
}