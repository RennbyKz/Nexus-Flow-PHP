<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['STOREKEEPER']); 
$active_page = 'alerts';

$my_branch = $_SESSION['branch_id'] ?? null;

// JSONB metadata မှ threshold ကိုယူ၍ လက်ရှိ quantity နှင့် နှိုင်းယှဉ်ကာ စစ်ထုတ်ခြင်း
try {
    $alert_query = $pdo->prepare("
        SELECT *, 
        (metadata->>'threshold')::int as safety_limit 
        FROM products 
        WHERE branch_id = ? 
        AND quantity < (metadata->>'threshold')::int
        ORDER BY quantity ASC
    ");
    $alert_query->execute([$my_branch]);
    $critical_items = $alert_query->fetchAll();
} catch (PDOException $e) { die("Security Protocol Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safety Protocol | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .animate-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased overflow-x-hidden">

    <?php include_once '../templates/sidebar_storekeeper.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300">
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 animate-in">
            <div>
                <h2 class="text-5xl font-black text-slate-950 tracking-tighter uppercase italic leading-none">Safety Protocol</h2>
                <p class="text-rose-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-rose-50 px-4 py-1.5 rounded-full inline-block border border-rose-100 italic">
                    Node Vulnerability Monitoring
                </p>
            </div>
            <div class="flex items-center gap-3 bg-white px-8 py-4 rounded-[2rem] shadow-sm border border-slate-100 group">
                <span class="relative flex h-4 w-4">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-600 shadow-[0_0_10px_rgba(225,29,72,0.5)]"></span>
                </span>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-rose-600 transition-colors">Active Node Scanning</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-10 animate-in" style="animation-delay: 0.1s;">
            <div class="bg-slate-950 p-10 rounded-[3.5rem] shadow-2xl relative overflow-hidden group">
                <i class="fas fa-biohazard absolute -right-4 -bottom-4 text-7xl text-white/5 italic group-hover:scale-110 transition-transform duration-700"></i>
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-2 italic">Breach Frequency</p>
                <p class="text-5xl font-black text-white italic tracking-tighter"><?php echo count($critical_items); ?></p>
                <p class="text-[10px] font-bold mt-4 uppercase tracking-[0.2em] text-rose-500 bg-rose-500/10 py-1 px-4 rounded-full inline-block">Action Required</p>
            </div>
            
            <div class="lg:col-span-3 bg-white p-10 rounded-[3.5rem] shadow-sm border border-slate-100 flex items-center gap-10">
                <div class="w-20 h-20 <?= count($critical_items) > 0 ? 'bg-rose-50 text-rose-500' : 'bg-emerald-50 text-emerald-500' ?> rounded-[2rem] flex items-center justify-center text-3xl shadow-inner transition-all duration-500">
                    <i class="fas <?= count($critical_items) > 0 ? 'fa-shield-virus' : 'fa-shield-check' ?>"></i>
                </div>
                <div>
                    <h4 class="text-slate-900 font-black text-2xl italic uppercase tracking-tighter leading-none mb-2">
                        Status: <?= count($critical_items) > 0 ? '<span class="text-rose-600">Compromised</span>' : '<span class="text-emerald-500">Optimal Integrity</span>' ?>
                    </h4>
                    <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest leading-relaxed">
                        The system is currently auditing all local repository nodes. <br> 
                        <span class="text-blue-500 italic">Target: Minimum Safety Threshold (JSON Meta-Sync)</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[4rem] shadow-sm border border-slate-100 overflow-hidden animate-in" style="animation-delay: 0.2s;">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 tracking-[0.3em] border-b">
                    <tr>
                        <th class="p-10">Source Asset Node</th>
                        <th class="p-10 text-center">In-Stock</th>
                        <th class="p-10 text-center">Safety Limit</th>
                        <th class="p-10 text-right">Synchronization</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if(empty($critical_items)): ?>
                        <tr>
                            <td colspan="4" class="p-32 text-center group">
                                <div class="w-24 h-24 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check-double text-3xl"></i>
                                </div>
                                <p class="text-slate-300 italic font-black uppercase tracking-[0.4em] text-xs">All inventory nodes are within safety parameters</p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach($critical_items as $item): ?>
                    <tr class="hover:bg-rose-50/20 transition-all duration-500 group">
                        <td class="p-10">
                            <div class="flex items-center gap-8">
                                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-[1.5rem] flex items-center justify-center group-hover:bg-rose-600 group-hover:text-white transition-all shadow-inner">
                                    <i class="fas fa-radiation text-xl"></i>
                                </div>
                                <div>
                                    <span class="font-black text-slate-800 block text-xl italic uppercase tracking-tighter leading-none mb-1 group-hover:text-rose-600 transition-colors"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                    <span class="text-[10px] font-black text-rose-400 uppercase tracking-widest italic">Inventory Violation Detected</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-10 text-center">
                            <span class="text-4xl font-black text-rose-600 italic leading-none group-hover:scale-110 transition-transform inline-block"><?php echo $item['quantity']; ?></span>
                            <span class="text-[9px] font-black text-slate-300 block uppercase mt-2 tracking-widest">Units Left</span>
                        </td>
                        <td class="p-10 text-center">
                            <div class="inline-block px-6 py-2 bg-slate-50 rounded-full border border-slate-100">
                                <span class="font-black text-slate-400 italic text-xl"><?php echo $item['safety_limit']; ?></span>
                                <span class="text-[8px] font-black text-slate-300 block uppercase tracking-tighter mt-0.5">Limit</span>
                            </div>
                        </td>
                        <td class="p-10 text-right">
                            <a href="my_inventory.php" class="bg-slate-950 text-white px-10 py-5 rounded-[2rem] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-blue-600 transition-all shadow-2xl active:scale-95 group-hover:translate-x-2">
                                Resolve Breach <i class="fas fa-arrow-right text-[8px] opacity-40 ml-2"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php include_once '../includes/footer.php'; ?>
    </main>
</body>
</html>