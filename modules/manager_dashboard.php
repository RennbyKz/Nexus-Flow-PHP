<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['MANAGER']); 
$active_page = 'dashboard';

$my_branch = $_SESSION['branch_id'];

try {
    // ၁။ မိမိဆိုင်ခွဲ၏ စုစုပေါင်းပစ္စည်းအရေအတွက်နှင့် တန်ဖိုး
    $stmt = $pdo->prepare("SELECT COUNT(*) as skus, SUM(quantity * price) as val FROM products WHERE branch_id = ?");
    $stmt->execute([$my_branch]);
    $branch_stats = $stmt->fetch();

    // ၂။ Pending Stock Requests
    $pending_stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_requests WHERE branch_id = ? AND status = 'PENDING'");
    $pending_stmt->execute([$my_branch]);
    $pending_requests = $pending_stmt->fetchColumn();

    // ၃။ Critical Stock Alerts (Threshold Check)
    $low_stock = $pdo->prepare("SELECT * FROM products WHERE branch_id = ? AND quantity < (metadata->>'threshold')::int ORDER BY quantity ASC LIMIT 5");
    $low_stock->execute([$my_branch]);
    $alerts = $low_stock->fetchAll();

} catch (PDOException $e) { die("Operational Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Console | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .animate-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased">

    <?php include_once '../templates/sidebar_manager.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300">
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 animate-in">
            <div>
                <h2 class="text-5xl font-black text-slate-900 tracking-tighter uppercase italic leading-none">Branch Console</h2>
                <p class="text-violet-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-violet-50 px-4 py-1.5 rounded-full inline-block border border-violet-100 italic">
                    Node Origin: <?php echo $_SESSION['branch_name'] ?? 'Authorized Branch'; ?>
                </p>
            </div>
            <a href="stock_requests.php" class="bg-slate-900 text-white px-8 py-4 rounded-[2rem] font-black text-[10px] uppercase tracking-widest hover:bg-violet-600 transition-all shadow-2xl active:scale-95">
                <i class="fas fa-plus-circle mr-2"></i> Create Procurement Request
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12 animate-in" style="animation-delay: 0.1s;">
            <div class="bg-white p-10 rounded-[3.5rem] shadow-sm border border-slate-100 group hover:shadow-xl transition-all duration-500">
                <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-violet-600 group-hover:text-white transition-all duration-500 shadow-inner">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
                <h4 class="text-slate-400 text-[9px] font-black uppercase tracking-[0.2em] mb-2">Local Asset SKU</h4>
                <p class="text-4xl font-black text-slate-900 tracking-tighter italic"><?php echo number_format($branch_stats['skus']); ?></p>
            </div>

            <div class="bg-white p-10 rounded-[3.5rem] shadow-sm border border-slate-100 group hover:shadow-xl transition-all duration-500">
                <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-500 shadow-inner">
                    <i class="fas fa-vault"></i>
                </div>
                <h4 class="text-slate-400 text-[9px] font-black uppercase tracking-[0.2em] mb-2">Inventory Valuation</h4>
                <p class="text-4xl font-black text-slate-900 tracking-tighter italic"><?php echo number_format($branch_stats['val']); ?> <span class="text-xs not-italic text-slate-400 ml-1">MMK</span></p>
            </div>

            <div class="bg-violet-600 p-10 rounded-[3.5rem] shadow-2xl text-white relative overflow-hidden group">
                <i class="fas fa-truck-loading absolute -right-4 -bottom-4 text-8xl text-white/10 italic group-hover:scale-110 transition-transform duration-700"></i>
                <h4 class="text-white/60 text-[9px] font-black uppercase tracking-[0.2em] mb-2 italic">Procurement Status</h4>
                <p class="text-5xl font-black italic tracking-tighter"><?php echo $pending_requests; ?></p>
                <p class="text-[9px] font-bold mt-4 uppercase tracking-[0.3em] bg-white/10 py-1.5 px-4 rounded-full inline-block">Awaiting HQ Sync</p>
            </div>
        </div>

        <div class="bg-white rounded-[4rem] shadow-sm overflow-hidden border border-slate-100 animate-in" style="animation-delay: 0.2s;">
            <div class="p-10 border-b border-slate-50 flex justify-between items-center">
                <div>
                    <h3 class="font-black italic uppercase text-slate-800 tracking-tighter text-2xl">Critical Replenishment</h3>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Assets falling below safety threshold</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-rose-500 rounded-full animate-ping"></span>
                    <span class="text-rose-500 text-[10px] font-black uppercase tracking-widest">Active Alerts</span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <tbody class="divide-y divide-slate-50">
                        <?php if(empty($alerts)): ?>
                            <tr><td class="p-24 text-center text-slate-300 italic font-black uppercase tracking-widest text-xs">All branch stock levels are within safety parameters</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach($alerts as $a): ?>
                        <tr class="hover:bg-slate-50 transition-all duration-300 group">
                            <td class="p-10 flex items-center gap-6">
                                <div class="w-12 h-12 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center shadow-inner">
                                    <i class="fas fa-triangle-exclamation"></i>
                                </div>
                                <div>
                                    <span class="font-black text-slate-800 block text-lg italic uppercase tracking-tighter leading-none mb-1"><?php echo htmlspecialchars($a['product_name']); ?></span>
                                    <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest"><?php echo $a['category'] ?? 'General Asset'; ?></span>
                                </div>
                            </td>
                            <td class="p-10">
                                <span class="text-2xl font-black text-rose-500 italic"><?php echo $a['quantity']; ?></span>
                                <span class="block text-[8px] font-black text-rose-300 uppercase tracking-widest">Current Hand</span>
                            </td>
                            <td class="p-10 text-right">
                                <a href="stock_requests.php?auto_refill=<?php echo $a['product_id']; ?>" class="bg-slate-900 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-violet-600 transition-all shadow-xl inline-block">
                                    Request Restock
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include_once '../includes/footer.php'; ?>
    </main>
</body>
</html>