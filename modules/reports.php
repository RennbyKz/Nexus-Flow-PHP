<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['STOREKEEPER']); 
$active_page = 'reports';

$my_branch = $_SESSION['branch_id'] ?? null;

// ၁။ ပစ္စည်းအမျိုးအစားအလိုက် စုစုပေါင်းအရေအတွက်နှင့် တန်ဖိုးကို တွက်ချက်ခြင်း
// Database Concept: Aggregation & Grouping
$query = $pdo->prepare("
    SELECT 
        product_name, 
        quantity, 
        price, 
        (quantity * price) as total_value,
        metadata->>'threshold' as safety_limit
    FROM products 
    WHERE branch_id = ? 
    ORDER BY total_value DESC
");
$query->execute([$my_branch]);
$report_items = $query->fetchAll();

// ၂။ စုစုပေါင်း Branch Asset Value ကို တွက်ချက်ခြင်း
$total_branch_value = array_sum(array_column($report_items, 'total_value'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Inventory Report | UniFlow ERP</title>
</head>
<body class="bg-gray-100 flex overflow-x-hidden font-sans">

    <?php include_once '../templates/sidebar_storekeeper.php'; ?>

    <main class="main-wrapper min-h-screen flex-1 p-8">
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-10 flex justify-between items-center print:hidden">
            <div>
                <p class="text-emerald-500 text-[10px] font-black uppercase tracking-[0.3em] mb-2">Financial Insights</p>
                <h2 class="text-4xl font-black text-slate-800 tracking-tighter uppercase italic">Asset Valuation Report</h2>
            </div>
            <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl">
                <i class="fas fa-print mr-2"></i> Export to Print
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-50">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Net Worth</p>
                <p class="text-3xl font-black text-emerald-600"><?php echo number_format($total_branch_value); ?> <span class="text-xs italic text-slate-300">MMK</span></p>
            </div>
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-50">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Total SKUs</p>
                <p class="text-3xl font-black text-slate-800"><?php echo count($report_items); ?></p>
            </div>
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-50">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Report Date</p>
                <p class="text-lg font-black text-blue-500"><?php echo date('d F, Y'); ?></p>
            </div>
        </div>

        <div class="bg-white rounded-[3.5rem] shadow-sm overflow-hidden border border-gray-50">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="p-8 text-[10px] font-black uppercase text-slate-400">Product Description</th>
                        <th class="p-8 text-[10px] font-black uppercase text-slate-400 text-center">In-Stock</th>
                        <th class="p-8 text-[10px] font-black uppercase text-slate-400 text-right">Unit Price</th>
                        <th class="p-8 text-[10px] font-black uppercase text-slate-400 text-right">Total Valuation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach($report_items as $item): ?>
                    <tr class="hover:bg-slate-50 transition group">
                        <td class="p-8">
                            <span class="font-black text-slate-700 block uppercase italic tracking-tighter"><?php echo htmlspecialchars($item['product_name']); ?></span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase">Min Safety Limit: <?php echo $item['safety_limit'] ?? 0; ?> units</span>
                        </td>
                        <td class="p-8 text-center font-black text-slate-800 text-xl"><?php echo $item['quantity']; ?></td>
                        <td class="p-8 text-right font-bold text-slate-500"><?php echo number_format($item['price']); ?></td>
                        <td class="p-8 text-right font-black text-slate-800 text-lg"><?php echo number_format($item['total_value']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-900 text-white">
                    <tr>
                        <td colspan="3" class="p-8 font-black uppercase tracking-[0.2em] text-xs text-right">Total Branch Asset Value (MMK)</td>
                        <td class="p-8 text-right font-black text-2xl italic"><?php echo number_format($total_branch_value); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php include_once '../includes/footer.php'; ?>
    </main>
</body>
</html>