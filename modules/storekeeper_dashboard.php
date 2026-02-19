<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php'; 

checkLogin();
authorize(['STOREKEEPER']); 
$active_page = 'dashboard';

$my_branch = $_SESSION['branch_id'] ?? null;
if (!$my_branch) { header("Location: ../logout.php"); exit(); }

try {
    // ၁။ Branch Statistics (Aggregation)
    $stats = $pdo->prepare("SELECT COUNT(*) as total_items, SUM(quantity * price) as inventory_value FROM products WHERE branch_id = ?");
    $stats->execute([$my_branch]);
    $summary = $stats->fetch();

    // ၂။ Low Stock Alerts (JSONB threshold check)
    $low_stock_query = $pdo->prepare("SELECT * FROM products WHERE branch_id = ? AND quantity < (metadata->>'threshold')::int ORDER BY quantity ASC LIMIT 5");
    $low_stock_query->execute([$my_branch]);
    $alerts = $low_stock_query->fetchAll();
} catch (PDOException $e) { die("Operational Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Console | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .animate-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased selection:bg-blue-100 selection:text-blue-600">

    <?php include_once '../templates/sidebar_storekeeper.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300">
        
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 animate-in">
            <div>
                <h2 class="text-5xl font-black text-slate-950 tracking-tighter uppercase italic leading-none">Live Console</h2>
                <p class="text-blue-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-blue-50 px-4 py-1.5 rounded-full inline-block border border-blue-100 italic">
                    Node: <?php echo $_SESSION['branch_name'] ?? 'Mandalay Hub'; ?>
                </p>
            </div>
            <div class="flex gap-3">
                <a href="stock_in.php" class="bg-emerald-500 text-white px-8 py-4 rounded-[2rem] font-black text-[10px] uppercase tracking-widest hover:bg-slate-900 transition-all shadow-xl shadow-emerald-500/20 active:scale-95">
                    <i class="fas fa-arrow-down-left mr-2"></i> Register Inbound
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12 animate-in" style="animation-delay: 0.1s;">
            <div class="bg-white p-10 rounded-[3.5rem] shadow-sm border border-slate-100 group hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-500 cursor-pointer">
                <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-[2rem] flex items-center justify-center text-3xl group-hover:bg-blue-600 group-hover:text-white transition-all duration-500 shadow-inner">
                    <i class="fas fa-cubes-stacked"></i>
                </div>
                <div class="mt-6">
                    <h4 class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-1">Local Asset Count</h4>
                    <p class="text-4xl font-black text-slate-800 tracking-tighter"><?php echo number_format($summary['total_items'] ?? 0); ?> <span class="text-sm font-bold opacity-20">SKUs</span></p>
                </div>
            </div>

            <div class="lg:col-span-2 bg-slate-900 p-10 rounded-[3.5rem] shadow-2xl flex items-center justify-between group overflow-hidden relative border border-slate-800">
                <div class="absolute -right-10 -top-10 text-[12rem] text-white/5 italic font-black pointer-events-none group-hover:-translate-y-5 transition-transform duration-700">NODE</div>
                <div class="flex items-center gap-8 relative z-10">
                    <div class="w-20 h-20 bg-emerald-500 text-white rounded-[2rem] flex items-center justify-center text-3xl shadow-[0_10px_30px_rgba(16,185,129,0.3)]">
                        <i class="fas fa-vault"></i>
                    </div>
                    <div>
                        <h4 class="text-emerald-500/60 text-[10px] font-black uppercase tracking-[0.2em] mb-1 italic font-bold">Node Valuation</h4>
                        <p class="text-5xl font-black text-white tracking-tighter uppercase italic leading-none">
                            <?php echo number_format($summary['inventory_value'] ?? 0); ?> 
                            <span class="text-xs font-bold opacity-30 italic not-italic tracking-widest ml-2">MMK</span>
                        </p>
                    </div>
                </div>
                <a href="my_inventory.php" class="relative z-10 w-16 h-16 bg-white/10 rounded-[1.5rem] flex items-center justify-center text-white hover:bg-blue-600 transition-all shadow-xl backdrop-blur-md border border-white/5 group-hover:rotate-12">
                    <i class="fas fa-arrow-right text-lg"></i>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-[4rem] shadow-sm overflow-hidden border border-slate-100 animate-in" style="animation-delay: 0.2s;">
            <div class="p-10 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                <div>
                    <h3 class="font-black italic uppercase text-slate-800 tracking-tighter text-2xl">Safety Protocol Alert</h3>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-1 italic">Automated Threshold Monitoring System</p>
                </div>
                <div class="flex items-center gap-3 bg-rose-50 px-6 py-2.5 rounded-full border border-rose-100 animate-pulse">
                    <span class="w-2.5 h-2.5 bg-rose-500 rounded-full shadow-[0_0_10px_rgba(244,63,94,0.8)]"></span>
                    <span class="text-rose-500 text-[10px] font-black uppercase tracking-[0.2em]">Active Breach Detected</span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <tbody class="divide-y divide-slate-50">
                        <?php if(empty($alerts)): ?>
                            <tr><td class="p-24 text-center text-slate-300 italic font-black uppercase tracking-[0.3em] text-xs">Local nodes are operational and balanced</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach($alerts as $a): ?>
                        <tr class="hover:bg-rose-50/20 transition-all duration-500 group">
                            <td class="p-10">
                                <div class="flex items-center gap-8">
                                    <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-[1.5rem] flex items-center justify-center text-xl shadow-inner group-hover:bg-rose-500 group-hover:text-white transition-all">
                                        <i class="fas fa-triangle-exclamation"></i>
                                    </div>
                                    <div>
                                        <span class="font-black text-slate-800 block text-xl italic uppercase tracking-tighter leading-none mb-1"><?php echo htmlspecialchars($a['product_name']); ?></span>
                                        <span class="text-[10px] text-rose-400 font-black uppercase tracking-[0.1em] italic">Current Count: <?php echo $a['quantity']; ?> Units Left</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-10 text-right">
                                <a href="stock_in.php?refill=<?php echo $a['product_id']; ?>" class="inline-flex items-center gap-4 bg-slate-950 text-white px-10 py-5 rounded-[2rem] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-blue-600 transition-all shadow-2xl active:scale-95 group-hover:translate-x-2">
                                    Initialize Refill <i class="fas fa-arrow-right text-[8px] opacity-40"></i>
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