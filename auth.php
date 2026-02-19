<?php
// auth.php
session_start();
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // ၁။ User အချက်အလက်ကို ဆွဲထုတ်ခြင်း (supplier_id ပါ တစ်ခါတည်း ပါလာမည်)
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.username = ? AND u.status = 'ACTIVE'
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // ၂။ Password Verification
        if ($user && password_verify($password, $user['password_hash'])) {
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = strtoupper($user['role_name']);
            $_SESSION['branch_id'] = $user['branch_id'];
            $_SESSION['branch_name'] = $user['branch_name'] ?? 'Root HQ';

            // ⭐ သင်၏ Schema အရ supplier_id သည် users table ထဲမှာတင် ရှိနေပါသည်
            if ($_SESSION['role'] === 'SUPPLIER') {
                if (!empty($user['supplier_id'])) {
                    $_SESSION['supplier_id'] = $user['supplier_id'];
                } else {
                    // Supplier ဖြစ်သော်လည်း supplier_id မရှိလျှင် Error ပြမည်
                    header("Location: index.php?error=no_supplier_link");
                    exit();
                }
            }

            // Audit Log
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
            $logStmt->execute([$user['user_id'], "User Login: Success"]);

            // Redirect to Dashboard
            $dashboard = "modules/" . strtolower($user['role_name']) . "_dashboard.php";
            header("Location: " . $dashboard);
            exit();

        } else {
            header("Location: index.php?error=invalid_credentials");
            exit();
        }

    } catch (PDOException $e) {
        die("Authentication Error: " . $e->getMessage());
    }
}