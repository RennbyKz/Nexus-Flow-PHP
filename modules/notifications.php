<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
// Role ၅ ခုစလုံးကို ခွင့်ပြုထားသည်
authorize(['ADMIN', 'MANAGER', 'STOREKEEPER', 'AUDITOR', 'SUPPLIER']);

$active_page = 'notifications';
$role = strtoupper($_SESSION['role']);
$my_branch = $_SESSION['branch_id'] ?? null;
$my_supplier = $_SESSION['supplier_id'] ?? null; // Supplier အတွက်

$notifications = [];

try {
    // 1. ADMIN & AUDITOR: စနစ်တစ်ခုလုံး၏ အရေးကြီးလုပ်ဆောင်ချက်များ (Audit Logs)
    if ($role === 'ADMIN' || $role === 'AUDITOR') {
        $stmt = $pdo->query("
            SELECT action AS title, timestamp AS date, 'system' AS type, 'Security Log' AS sub
            FROM audit_logs ORDER BY timestamp DESC LIMIT 15
        ");
        $notifications = $stmt->fetchAll();
    } 
    
    // 2. MANAGER & STOREKEEPER: မိမိဆိုင်ခွဲ၏ ပစ္စည်းလက်ကျန် သတိပေးချက်
    elseif ($role === 'MANAGER' || $role === 'STOREKEEPER') {
        $stmt = $pdo->prepare("
            SELECT product_name || ' is running low!' AS title, 'Low Stock' AS type, 
                   'Inventory Warning' AS sub, CURRENT_TIMESTAMP AS date
            FROM products
            WHERE branch_id = ? AND quantity < (metadata->>'threshold')::int
        ");
        $stmt->execute([$my_branch]);
        $notifications = $stmt->fetchAll();
    }

    // 3. SUPPLIER: မိမိထံသို့ ရောက်ရှိလာသော Order သစ်များ
    elseif ($role === 'SUPPLIER') {
        $stmt = $pdo->prepare("
            SELECT 'New request for ' || product_name AS title, 'Order' AS type, 
                   'Incoming Procurement' AS sub, requested_at AS date
            FROM stock_requests
            WHERE supplier_id = ? AND status = 'PENDING'
            ORDER BY requested_at DESC
        ");
        $stmt->execute([$my_supplier]);
        $notifications = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Notifications | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .animate-in { animation: slideUp 0.5s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased overflow-x-hidden">

<?php 
    $sidebar_path = "../templates/sidebar_" . strtolower($role) . ".php";
    if (file_exists($sidebar_path)) include_once $sidebar_path; 
?>

<main class="min-h-screen ml-0 lg:ml-[280px] px-6 py-8 transition-all duration-300">
    <?php include_once '../includes/header.php'; ?>

    <div class="max-w-5xl mx-auto">
        <div class="mb-12 flex justify-between items-end animate-in">
            <div>
                <h2 class="text-5xl font-black text-slate-900 tracking-tighter uppercase italic leading-none">Notifications</h2>
                <p class="text-blue-600 text-[10px] font-black uppercase tracking-[0.4em] mt-4 bg-blue-50 px-4 py-1.5 rounded-full inline-block italic">
                    <?= $role ?> Channel: Real-time Monitoring Active
                </p>
            </div>
            <div class="bg-white px-6 py-4 rounded-[2rem] shadow-sm border border-slate-100 hidden md:block">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Unread Alerts</span>
                <span class="text-2xl font-black text-blue-600 italic"><?= count($notifications) ?></span>
            </div>
        </div>

        <div class="grid gap-4">
            <?php if (empty($notifications)): ?>
                <div class="bg-white p-24 rounded-[4rem] text-center border border-slate-100 shadow-sm animate-in">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-bell-slash text-slate-200 text-3xl"></i>
                    </div>
                    <p class="text-slate-300 font-black uppercase tracking-[0.3em] text-xs">No active alerts for your role</p>
                </div>
            <?php endif; ?>

            <?php foreach ($notifications as $i => $n): ?>
                <?php 
                    $is_system = ($n['type'] === 'system');
                    $is_order = ($n['type'] === 'Order');
                ?>
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border-l-[12px] flex items-center gap-8 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 animate-in"
                     style="animation-delay: <?= $i * 0.05 ?>s; 
                            border-color: <?= $is_system ? '#3b82f6' : ($is_order ? '#10b981' : '#f43f5e') ?>;">

                    <div class="w-16 h-16 flex items-center justify-center rounded-3xl shadow-inner
                        <?= $is_system ? 'bg-blue-50 text-blue-500' : ($is_order ? 'bg-emerald-50 text-emerald-500' : 'bg-rose-50 text-rose-500') ?>">
                        <i class="fas <?= $is_system ? 'fa-shield-halved' : ($is_order ? 'fa-shopping-cart' : 'fa-triangle-exclamation') ?> text-xl"></i>
                    </div>

                    <div class="flex-1">
                        <div class="flex justify-between items-start mb-1">
                            <h4 class="text-slate-800 font-black uppercase italic tracking-tighter text-xl">
                                <?= htmlspecialchars($n['title']) ?>
                            </h4>
                            <span class="text-[8px] font-black px-3 py-1 rounded-full uppercase tracking-widest
                                <?= $is_system ? 'bg-blue-100 text-blue-600' : ($is_order ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600') ?>">
                                <?= $n['sub'] ?>
                            </span>
                        </div>
                        <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.2em] italic flex items-center gap-2">
                            <i class="far fa-clock text-blue-400"></i>
                            <?= date('d M, Y | h:i A', strtotime($n['date'])) ?>
                        </p>
                    </div>

                    <div class="no-print">
                        <a href="<?= $is_order ? 'manage_orders.php' : ($is_system ? 'audit_logs.php' : 'my_inventory.php') ?>" 
                           class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 hover:bg-slate-900 hover:text-white transition-all">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
</body>
</html>