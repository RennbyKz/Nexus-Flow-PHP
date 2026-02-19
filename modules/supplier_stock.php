<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
if ($_SESSION['role'] !== 'SUPPLIER') { header("Location: ../index.php"); exit(); }

$my_supplier_id = $_SESSION['supplier_id'];
$active_page = 'stock';

// စာရင်းဆွဲထုတ်ခြင်း
$sql = "
    SELECT p.*, b.branch_name, 
           (p.metadata->>'threshold')::int as threshold
    FROM products p
    JOIN branches b ON p.branch_id = b.branch_id
    WHERE p.supplier_id = ?
    ORDER BY p.quantity ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$my_supplier_id]);
$stocks = $stmt->fetchAll();

// Summary Metrics
$total_skus = count($stocks);
$low_stock_count = 0;
foreach($stocks as $item) {
    if($item['quantity'] < $item['threshold']) $low_stock_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Monitor | SupplyNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .animate-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-800 antialiased">

    <?php include '../templates/sidebar_supplier.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[280px] transition-all duration-300">
        <?php include '../includes/header.php'; ?>

        <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 animate-in">
            <div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tighter uppercase italic leading-none">Stock Monitor</h2>
                <p class="text-emerald-600 text-[10px] font-black uppercase tracking-[0.3em] mt-3 bg-emerald-50 px-3 py-1 rounded-full inline-block">Real-time Global Inventory Trace</p>
            </div>
            
            <div class="flex gap-4">
                <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-slate-100">
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">Total SKU</span>
                    <span class="text-xl font-black text-slate-800 italic"><?= $total_skus ?></span>
                </div>
                <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-slate-100">
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block text-rose-400">Critical</span>
                    <span class="text-xl font-black text-rose-500 italic"><?= $low_stock_count ?></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 animate-in" style="animation-delay: 0.1s;">
            <?php if(empty($stocks)): ?>
                <div class="col-span-full py-20 text-center bg-white rounded-[3rem] border border-dashed border-slate-200">
                    <i class="fas fa-box-open text-4xl text-slate-200 mb-4 block"></i>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No assets mapped to your account</p>
                </div>
            <?php endif; ?>

            <?php foreach($stocks as $s): 
                $is_low = ($s['quantity'] < $s['threshold']);
                $is_out = ($s['quantity'] <= 0);
            ?>
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-500 group">
                <div class="flex justify-between items-start mb-6">
                    <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-500 shadow-inner">
                        <i class="fas fa-boxes-stacked text-sm"></i>
                    </div>
                    <div>
                        <?php if($is_out): ?>
                            <span class="px-3 py-1 bg-rose-500 text-white rounded-lg text-[8px] font-black uppercase tracking-widest">Out of Stock</span>
                        <?php elseif($is_low): ?>
                            <span class="px-3 py-1 bg-amber-50 text-amber-500 border border-amber-100 rounded-lg text-[8px] font-black uppercase tracking-widest animate-pulse">Low Level</span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-emerald-50 text-emerald-500 border border-emerald-100 rounded-lg text-[8px] font-black uppercase tracking-widest">Stable</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter leading-tight truncate"><?= htmlspecialchars($s['product_name']) ?></h3>
                    <div class="flex items-center gap-2 mt-2">
                        <i class="fas fa-map-marker-alt text-emerald-400 text-[10px]"></i>
                        <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($s['branch_name']) ?></span>
                    </div>
                </div>

                <div class="flex justify-between items-end pt-6 border-t border-dashed border-slate-100">
                    <div>
                        <span class="text-[8px] font-black text-slate-300 uppercase tracking-widest block">Min. Required</span>
                        <span class="text-sm font-black text-slate-500 italic"><?= $s['threshold'] ?> units</span>
                    </div>
                    <div class="text-right">
                        <span class="text-4xl font-black tracking-tighter italic <?= $is_low ? 'text-rose-500' : 'text-slate-900' ?>">
                            <?= $s['quantity'] ?>
                        </span>
                        <span class="block text-[8px] font-black text-slate-300 uppercase tracking-widest mt-1">Available Units</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-12 p-8 bg-slate-900 rounded-[3rem] shadow-2xl relative overflow-hidden animate-in" style="animation-delay: 0.2s;">
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="text-center md:text-left">
                    <h4 class="text-white font-black text-lg italic uppercase tracking-tighter">Need to replenish stock?</h4>
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">Monitor levels closely to avoid supply chain disruption</p>
                </div>
                <a href="supplier_orders.php" class="bg-emerald-500 text-slate-950 px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-white transition-all shadow-xl active:scale-95">
                    Check Supply Requests <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <i class="fas fa-shield-heart absolute -right-6 -bottom-6 text-8xl text-white opacity-5"></i>
        </div>

        <?php include '../includes/footer.php'; ?>
    </main>
</body>
</html>