<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['MANAGER']); 
$active_page = 'manager_inventory';
$my_branch = $_SESSION['branch_id'];

try {
    // ပစ္စည်းစာရင်းနှင့်အတူ တန်ဖိုးများကို တစ်ခါတည်း တွက်ချက်ခြင်း
    $stmt = $pdo->prepare("
        SELECT *, (quantity * price) as asset_value 
        FROM products 
        WHERE branch_id = ? 
        ORDER BY quantity ASC
    ");
    $stmt->execute([$my_branch]);
    $items = $stmt->fetchAll();

    // Branch Total Value တွက်ချက်ခြင်း
    $total_value = array_sum(array_column($items, 'asset_value'));
} catch (PDOException $e) { die("Inventory Sync Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Repository | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .animate-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        @media print { .no-print { display: none !important; } .main-content { margin-left: 0 !important; } }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased overflow-x-hidden">

    <?php include_once '../templates/sidebar_manager.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300 main-content">
        
        <div class="no-print"><?php include_once '../includes/header.php'; ?></div>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 animate-in">
            <div>
                <h2 class="text-5xl font-black text-slate-950 tracking-tighter uppercase italic leading-none">Local Repository</h2>
                <p class="text-violet-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-violet-50 px-4 py-1.5 rounded-full inline-block border border-violet-100 italic">
                    Branch Assets Oversight: <?= $_SESSION['branch_name'] ?>
                </p>
            </div>
            
            <div class="flex gap-4 no-print">
                <div class="bg-white px-8 py-4 rounded-[2rem] shadow-sm border border-slate-100 flex items-center gap-4">
                    <div class="text-right border-r border-slate-100 pr-4">
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-1">Total Valuation</span>
                        <span class="text-xl font-black text-emerald-600 italic leading-none"><?= number_format($total_value) ?> <span class="text-[10px] not-italic">MMK</span></span>
                    </div>
                    <div class="text-right pl-2">
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-1">Active SKU</span>
                        <span class="text-xl font-black text-violet-600 italic leading-none"><?= count($items) ?></span>
                    </div>
                </div>
                <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-4 rounded-[2rem] font-black text-[10px] uppercase tracking-widest hover:bg-violet-600 transition shadow-2xl active:scale-95">
                    <i class="fas fa-print mr-2 text-violet-400"></i> Print Audit
                </button>
            </div>
        </div>

        <div class="bg-white rounded-[4rem] shadow-sm border border-slate-100 overflow-hidden animate-in" style="animation-delay: 0.1s;">
            <div class="p-10 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center">
                <h3 class="font-black italic uppercase text-slate-800 tracking-tighter text-2xl">Asset Ledger</h3>
                <div class="relative no-print">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                    <input type="text" id="inventorySearch" placeholder="Quick find SKU..." 
                           class="pl-10 pr-6 py-3 bg-white border border-slate-100 rounded-2xl outline-none focus:ring-4 focus:ring-violet-500/10 font-bold text-xs w-64 transition-all">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="inventoryTable">
                    <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="p-8">Description</th>
                            <th class="p-8">Cost Unit</th>
                            <th class="p-8 text-center">Availability</th>
                            <th class="p-8 text-right">Net Valuation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach($items as $item): 
                            // JSON Metadata မှ threshold ကို စစ်ဆေးခြင်း
                            $meta = is_string($item['metadata']) ? json_decode($item['metadata'], true) : $item['metadata'];
                            $threshold = $meta['threshold'] ?? 10;
                            $is_low = $item['quantity'] < $threshold;
                            $percent = min(($item['quantity'] / ($threshold * 3)) * 100, 100);
                        ?>
                        <tr class="hover:bg-slate-50 transition-all duration-300 group">
                            <td class="p-8">
                                <div class="flex items-center gap-6">
                                    <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-xl text-slate-400 group-hover:bg-violet-600 group-hover:text-white transition-all duration-500 shadow-inner">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <div>
                                        <span class="font-black text-slate-800 block text-lg italic uppercase tracking-tighter leading-none mb-1"><?= htmlspecialchars($item['product_name']) ?></span>
                                        <span class="text-[9px] font-black <?= $is_low ? 'text-rose-500 animate-pulse' : 'text-slate-400' ?> uppercase tracking-widest italic">
                                            <?= $is_low ? '<i class="fas fa-warning"></i> Critical: Replenish Now' : 'Status: Operations Normal' ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-8">
                                <span class="font-bold text-slate-500 text-sm"><?= number_format($item['price']) ?></span>
                                <span class="text-[9px] font-black text-slate-300 block uppercase tracking-widest">MMK / UNIT</span>
                            </td>
                            <td class="p-8">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-2xl font-black <?= $is_low ? 'text-rose-600' : 'text-slate-900' ?> italic leading-none"><?= $item['quantity'] ?></span>
                                    <div class="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden shadow-inner">
                                        <div class="h-full <?= $is_low ? 'bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.5)]' : 'bg-violet-500' ?> rounded-full" 
                                             style="width: <?= $percent ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-8 text-right">
                                <span class="font-black text-slate-900 text-xl italic tracking-tighter"><?= number_format($item['asset_value']) ?></span>
                                <span class="text-[9px] font-black text-emerald-500 block uppercase tracking-widest">Liquidity Value</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include_once '../includes/footer.php'; ?>
    </main>

    <script>
        // Real-time Inventory Search
        document.getElementById('inventorySearch').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let rows = document.querySelector("#inventoryTable tbody").rows;
            for (let i = 0; i < rows.length; i++) {
                let text = rows[i].cells[0].textContent.toUpperCase();
                rows[i].style.display = text.includes(filter) ? "" : "none";
            }
        });
    </script>
</body>
</html>