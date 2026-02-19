<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
$active_page = 'inventory'; 

// --- ၁။ Restore Logic ---
if (isset($_GET['restore_id'])) {
    $id = $_GET['restore_id'];
    try {
        // ပစ္စည်းအချက်အလက်ကို အရင်ဆွဲထုတ်ခြင်း (Notification ထဲမှာပြရန်)
        $infoStmt = $pdo->prepare("SELECT p.product_name, b.branch_name FROM products p JOIN branches b ON p.branch_id = b.branch_id WHERE p.product_id = ?");
        $infoStmt->execute([$id]);
        $item = $infoStmt->fetch();

        $stmt = $pdo->prepare("UPDATE products SET is_deleted = FALSE WHERE product_id = ?");
        $stmt->execute([$id]);

        // ⭐ Detailed Telegram Notification
        $msg = "♻️ <b>ASSET RESTORED TO LIVE</b>\n"
             . "--------------------------------\n"
             . "📦 <b>Item:</b> " . $item['product_name'] . "\n"
             . "🆔 <b>Node ID:</b> #" . $id . "\n"
             . "📍 <b>Location:</b> " . $item['branch_name'] . "\n"
             . "👤 <b>Authorized By:</b> " . $_SESSION['username'] . "\n"
             . "📅 <b>Date:</b> " . date('d-M-Y H:i A');
        sendTelegramNotification($msg);

        header("Location: inventory_archive.php?msg=restored");
        exit();
    } catch (PDOException $e) { $error = "Restore Failed: " . $e->getMessage(); }
}

// --- ၂။ Hard Delete Logic ---
if (isset($_GET['purge_id'])) {
    $id = $_GET['purge_id'];
    try {
        // မဖျက်ခင် အချက်အလက်ယူထားခြင်း
        $infoStmt = $pdo->prepare("SELECT p.product_name, b.branch_name, p.price, p.quantity FROM products p JOIN branches b ON p.branch_id = b.branch_id WHERE p.product_id = ?");
        $infoStmt->execute([$id]);
        $item = $infoStmt->fetch();

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM product_archives WHERE product_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$id]);
        $pdo->commit();

        // ⭐ Detailed Telegram Notification (Critical Warning)
        $msg = "💀 <b>CRITICAL: PERMANENT PURGE</b>\n"
             . "--------------------------------\n"
             . "📦 <b>Item:</b> " . $item['product_name'] . "\n"
             . "🆔 <b>Node ID:</b> #" . $id . "\n"
             . "📍 <b>Origin:</b> " . $item['branch_name'] . "\n"
             . "📊 <b>Last Vol:</b> " . $item['quantity'] . " Units\n"
             . "💰 <b>Value:</b> " . number_format($item['price']) . " MMK\n"
             . "🚨 <b>Action:</b> Permanent Data Erasure\n"
             . "👤 <b>Authorized By:</b> " . $_SESSION['username'] . "\n"
             . "⏰ <b>Timestamp:</b> " . date('d-M-Y H:i A');
        sendTelegramNotification($msg);

        header("Location: inventory_archive.php?msg=purged");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Purge Error: " . $e->getMessage());
    }
}

// --- ၃။ Fetch Archived Data ---
$sql = "SELECT p.*, b.branch_name 
        FROM products p 
        JOIN branches b ON p.branch_id = b.branch_id 
        WHERE p.is_deleted = TRUE 
        ORDER BY p.product_id DESC";
$archived_products = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Asset Archive | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f7fa; }
        
        .main-content { margin-left: 320px; transition: all 0.4s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }

        .archive-card { 
            background: white; 
            border-radius: 3.5rem; 
            overflow: hidden; 
            border: 1px solid rgba(244, 63, 94, 0.1); 
            box-shadow: 0 20px 50px -15px rgba(244, 63, 94, 0.05); 
        }

        .animate-reveal { animation: reveal 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; }
        @keyframes reveal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        tbody tr { transition: all 0.3s ease; }
        tbody tr:hover { background: rgba(244, 63, 94, 0.02); }
    </style>
</head>
<body class="antialiased text-slate-900 overflow-x-hidden">

    <div class="no-print">
        <?php include '../templates/sidebar_admin.php'; ?>
    </div>

    <main class="main-content min-h-screen p-8 lg:p-12 relative flex flex-col">
        <div class="no-print sticky top-0 z-[999]">
            <?php include '../includes/header.php'; ?>
        </div>

        <div class="p-4">
            <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-8 animate-reveal">
                <div class="space-y-2">
                    <span class="text-[10px] font-black uppercase tracking-[0.5em] text-rose-500/60 italic block">Restricted Decommissioned Node</span>
                    <h2 class="text-6xl font-black text-slate-900 tracking-tighter uppercase italic leading-none">Asset <span class="text-rose-500">Archive</span></h2>
                </div>
                <a href="inventory.php" class="bg-[#1C4D8D] text-white px-10 py-5 rounded-2xl font-black shadow-xl hover:bg-blue-800 transition-all text-[10px] uppercase tracking-widest flex items-center gap-3 active:scale-95 group">
                    <i class="fas fa-arrow-left group-hover:-translate-x-2 transition-transform"></i> Return to Live Inventory
                </a>
            </div>

            <div class="archive-card animate-reveal" style="animation-delay: 0.1s;">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-rose-50/30 text-rose-600 text-[10px] font-black uppercase tracking-[0.25em] border-b border-rose-100">
                            <tr>
                                <th class="p-10">Archived Identity</th>
                                <th class="p-10">Origin Hub</th>
                                <th class="p-10 text-center">Retained Vol</th>
                                <th class="p-10 text-right">Valuation</th>
                                <th class="p-10 text-right">Recovery Protocol</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach($archived_products as $p): ?>
                            <tr class="group">
                                <td class="p-10">
                                    <div class="flex items-center gap-6 opacity-60 group-hover:opacity-100 transition-all duration-500">
                                        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-rose-100 group-hover:text-rose-500 transition-all">
                                            <i class="fas fa-box-archive text-2xl"></i>
                                        </div>
                                        <div>
                                            <span class="text-2xl font-black text-slate-700 block italic tracking-tighter uppercase leading-none mb-2"><?= htmlspecialchars($p['product_name']); ?></span>
                                            <span class="text-[9px] font-black text-rose-400 bg-rose-50 px-3 py-1 rounded-md uppercase border border-rose-100 italic">Status: Decommissioned</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-10">
                                    <div class="flex items-center gap-3 opacity-50 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-map-pin text-slate-300"></i>
                                        <span class="font-black italic text-slate-500 text-sm uppercase tracking-tighter"><?= htmlspecialchars($p['branch_name']); ?></span>
                                    </div>
                                </td>
                                <td class="p-10 text-center">
                                    <span class="text-3xl font-black italic tracking-tighter text-slate-300 group-hover:text-slate-900 transition-colors"><?= $p['quantity']; ?></span>
                                    <p class="text-[8px] font-black text-slate-200 uppercase mt-2 tracking-widest">Units Cached</p>
                                </td>
                                <td class="p-10 text-right">
                                    <div class="flex flex-col items-end opacity-40 group-hover:opacity-100 transition-opacity">
                                        <span class="font-black text-slate-500 text-2xl italic tracking-tighter"><?= number_format($p['price']); ?></span>
                                        <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest">MMK UNIT</span>
                                    </div>
                                </td>
                                <td class="p-10 text-right">
                                    <div class="flex justify-end gap-3">
                                        <button onclick="confirmRestore(<?= $p['product_id']; ?>, '<?= addslashes($p['product_name']); ?>')" 
                                                class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-all shadow-sm active:scale-90" title="Restore to Node">
                                            <i class="fas fa-rotate-left"></i>
                                        </button>
                                        <button onclick="confirmPurge(<?= $p['product_id']; ?>, '<?= addslashes($p['product_name']); ?>')" 
                                                class="w-12 h-12 bg-rose-50 text-rose-300 hover:bg-rose-500 hover:text-white rounded-2xl flex items-center justify-center transition-all active:scale-90 shadow-sm" title="Permanent Purge">
                                            <i class="fas fa-biohazard"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($archived_products)): ?>
                            <tr>
                                <td colspan="5" class="p-40 text-center">
                                    <div class="space-y-6 opacity-20">
                                        <i class="fas fa-shield-virus text-8xl text-slate-300"></i>
                                        <p class="text-slate-400 font-black italic uppercase tracking-[0.5em] text-[10px]">No archived assets detected in restricted storage</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="no-print mt-auto pt-10">
            <?php include '../includes/footer.php'; ?>
        </div>
    </main>

    <script>
        function confirmRestore(id, name) {
            Swal.fire({
                title: '<span class="italic font-black uppercase tracking-tighter">RESTORE ASSET NODE?</span>',
                text: `Authorization to move "${name}" back to live operational status.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#1C4D8D',
                confirmButtonText: 'YES, AUTHORIZE',
                customClass: { popup: 'rounded-[3rem] p-10' }
            }).then((res) => { if (res.isConfirmed) window.location.href = `inventory_archive.php?restore_id=${id}`; });
        }

        function confirmPurge(id, name) {
            Swal.fire({
                title: '<span class="italic font-black uppercase tracking-tighter text-rose-600">CRITICAL: PERMANENT PURGE?</span>',
                text: `Permanent erasure of "${name}" from system core cannot be reversed.`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#1C4D8D',
                confirmButtonText: 'CONFIRM DESTRUCTION',
                customClass: { popup: 'rounded-[3rem] p-10' }
            }).then((res) => { if (res.isConfirmed) window.location.href = `inventory_archive.php?purge_id=${id}`; });
        }

        const params = new URLSearchParams(window.location.search);
        if(params.has('msg')) {
            const msg = params.get('msg');
            const config = msg === 'restored' 
                ? { icon: 'success', title: 'ASSET RESTORED', text: 'Data node synchronized back to live inventory.' }
                : { icon: 'warning', title: 'ASSET PURGED', text: 'Data node permanently removed from the ledger.' };
            
            Swal.fire({ ...config, confirmButtonColor: '#1C4D8D', customClass: { popup: 'rounded-[3rem] p-10' } });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>