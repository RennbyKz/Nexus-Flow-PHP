<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
$my_supplier_id = $_SESSION['supplier_id'] ?? null;
$active_page = 'dashboard';

// --- ⭐ DATA AGGREGATION (FOR ANALYTICS) ---

// ၁။ စုစုပေါင်း မှာယူမှု အရေအတွက် (All time)
$total_orders = $pdo->prepare("SELECT COUNT(*) FROM stock_requests WHERE supplier_id = ?");
$total_orders->execute([$my_supplier_id]);
$total_requests_count = $total_orders->fetchColumn();

// ၂။ စုစုပေါင်း ရောင်းရငွေ ခန့်မှန်းခြေ (Revenue Estimate)
$revenue_sql = $pdo->prepare("SELECT SUM(quantity * price) FROM stock_requests WHERE supplier_id = ? AND status != 'CANCELLED'");
$revenue_sql->execute([$my_supplier_id]);
$total_revenue = $revenue_sql->fetchColumn() ?: 0;

// ၃။ မိမိသွင်းထားသော ပစ္စည်းများ၏ လက်ရှိ Stock အခြေအနေ
$inventory_sql = "
    SELECT p.product_name, p.quantity, b.branch_name, 
           (p.metadata->>'threshold')::int as threshold
    FROM products p
    JOIN branches b ON p.branch_id = b.branch_id
    WHERE p.supplier_id = ?
";
$my_stock = $pdo->prepare($inventory_sql);
$my_stock->execute([$my_supplier_id]);
$stock_data = $my_stock->fetchAll();

// ၄။ Purchase Requests (နောက်ဆုံး ၅ ခုသာ ပြရန်)
$orders_sql = "SELECT * FROM stock_requests WHERE supplier_id = ? ORDER BY requested_at DESC LIMIT 5";
$orders = $pdo->prepare($orders_sql);
$orders->execute([$my_supplier_id]);
$order_requests = $orders->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Terminal | SupplyNode</title>
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

        <div class="mb-10 animate-in">
            <h2 class="text-5xl font-black text-slate-950 tracking-tighter uppercase italic leading-none">Partner Terminal</h2>
            <p class="text-emerald-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-emerald-50 px-3 py-1 rounded-full inline-block italic">Supply Chain Intelligence Hub</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 animate-in" style="animation-delay: 0.1s;">
            <div class="bg-slate-950 p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden group">
                <i class="fas fa-chart-line absolute -right-4 -bottom-4 text-7xl text-emerald-500 opacity-10 group-hover:scale-110 transition-transform"></i>
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Gross Business Value</span>
                <h4 class="text-3xl font-black text-white mt-2 italic"><?= number_format($total_revenue) ?> <span class="text-xs text-slate-600 not-italic uppercase ml-1">MMK</span></h4>
            </div>
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Lifespan Orders</span>
                <h4 class="text-3xl font-black text-slate-900 mt-2 italic"><?= $total_requests_count ?> <span class="text-xs text-slate-300 not-italic uppercase ml-1">Invoices</span></h4>
            </div>
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Current Inventory SKU</span>
                <h4 class="text-3xl font-black text-emerald-500 mt-2 italic"><?= count($stock_data) ?> <span class="text-xs text-slate-300 not-italic uppercase ml-1">Products</span></h4>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-8">
            
            <div class="xl:col-span-2 space-y-6 animate-in" style="animation-delay: 0.2s;">
                <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 h-full">
                    <div class="flex justify-between items-center mb-8">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-inner"><i class="fas fa-layer-group"></i></div>
                            <h3 class="font-black italic uppercase text-slate-400 text-[10px] tracking-widest">Live Stock Monitoring</h3>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if(empty($stock_data)): ?>
                            <div class="py-10 text-center border-2 border-dashed border-slate-100 rounded-[2rem]">
                                <p class="text-[10px] font-black text-slate-300 uppercase italic">No active assets discovered</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach($stock_data as $s): 
                            $is_low = ($s['quantity'] < $s['threshold']);
                        ?>
                            <div class="group flex justify-between items-center p-6 bg-slate-50 rounded-[2.2rem] border border-transparent hover:border-emerald-200 hover:bg-white transition-all duration-500">
                                <div>
                                    <span class="font-black text-slate-800 block text-sm italic uppercase tracking-tight"><?= htmlspecialchars($s['product_name']) ?></span>
                                    <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest flex items-center gap-2 mt-1">
                                        <i class="fas fa-map-marker-alt text-emerald-400"></i> <?= htmlspecialchars($s['branch_name']) ?>
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="text-lg font-black <?= $is_low ? 'text-rose-500' : 'text-slate-900' ?> italic"><?= $s['quantity'] ?></span>
                                    <span class="block text-[8px] font-black <?= $is_low ? 'text-rose-300 animate-pulse' : 'text-slate-300' ?> uppercase">In Hand</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-3 space-y-6 animate-in" style="animation-delay: 0.3s;">
                <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 h-full overflow-hidden">
                    <div class="flex justify-between items-center mb-8">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-slate-900 text-white rounded-2xl flex items-center justify-center shadow-xl rotate-3"><i class="fas fa-shopping-cart"></i></div>
                            <h3 class="font-black italic uppercase text-slate-400 text-[10px] tracking-widest">Recent 5 Supply Requests</h3>
                        </div>
                        <a href="supplier_orders.php" class="text-[9px] font-black uppercase text-emerald-600 hover:underline tracking-widest">Archive <i class="fas fa-chevron-right ml-1"></i></a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach($order_requests as $o): ?>
                                <tr class="group hover:bg-slate-50 transition-colors">
                                    <td class="py-6">
                                        <span class="block text-slate-800 text-sm font-black uppercase italic tracking-tighter leading-tight"><?= htmlspecialchars($o['product_name']) ?></span>
                                        <span class="text-[9px] text-slate-400 uppercase font-bold tracking-widest italic"><?= date('M d, Y', strtotime($o['requested_at'])) ?></span>
                                    </td>
                                    
                                    <td class="py-6 text-center">
                                        <div class="inline-block px-3 py-1 bg-slate-100 rounded-lg">
                                            <span class="font-black text-slate-800 text-xs italic">x<?= $o['quantity'] ?></span>
                                        </div>
                                    </td>

                                    <td class="py-6 text-right">
                                        <div class="flex flex-col items-end gap-2">
                                            <?php if($o['status'] === 'PENDING'): ?>
                                                <div class="flex gap-2">
                                                    <a href="update_order_status.php?id=<?= $o['request_id'] ?>&status=SHIPPED" 
                                                       class="bg-emerald-500 text-white px-5 py-2 rounded-[1.2rem] text-[9px] font-black uppercase tracking-widest hover:bg-slate-900 transition shadow-lg shadow-emerald-100 active:scale-95">
                                                        Dispatch
                                                    </a>
                                                </div>

                                            <?php elseif($o['status'] === 'RECEIVED' && strpos($o['remark'], 'Shortage') !== false): ?>
                                                <div class="bg-rose-50 border border-rose-100 p-3 rounded-2xl text-right max-w-[150px]">
                                                    <span class="text-rose-600 font-black text-[8px] uppercase tracking-tighter italic">Shortage Alert</span>
                                                </div>

                                            <?php else: ?>
                                                <span class="px-4 py-2 rounded-[1.2rem] text-[8px] font-black uppercase tracking-widest border border-slate-100 
                                                    <?= $o['status'] === 'SHIPPED' ? 'bg-emerald-50 text-emerald-600' : ($o['status'] === 'RECEIVED' ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-100 text-slate-400') ?>">
                                                    <?= $o['status'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <?php include '../includes/footer.php'; ?>
    </main>
</body>
</html>