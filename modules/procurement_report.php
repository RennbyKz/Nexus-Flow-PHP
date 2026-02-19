<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
authorize(['ADMIN']);
$active_page = 'procurement';

// --- ၁။ Filter Logic ---
$cat_filter = $_GET['category'] ?? 'ALL';

// --- ၂။ Main Data Query (FTS & Analytics Optimized) ---
$sql = "
    SELECT 
        p.product_id, p.product_name, p.quantity, p.price, p.category,
        (p.quantity * p.price) as total_value,
        s.name as supplier_name, b.branch_name, sr.status as request_status,
        p.metadata->>'threshold' as threshold
    FROM products p
    JOIN suppliers s ON p.supplier_id = s.supplier_id
    JOIN branches b ON p.branch_id = b.branch_id
    LEFT JOIN stock_requests sr ON p.product_name = sr.product_name 
    WHERE 1=1";

if ($cat_filter !== 'ALL') {
    $sql .= " AND p.category = " . $pdo->quote($cat_filter);
}

$sql .= " ORDER BY p.product_id DESC";
$report_data = $pdo->query($sql)->fetchAll();

// --- ၃။ Summary Data (Materialized Logic) ---
$grand_total = array_sum(array_column($report_data, 'total_value'));
$total_assets = count($report_data);
$total_suppliers = $pdo->query("SELECT COUNT(supplier_id) FROM suppliers")->fetchColumn();
$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

// Low Stock Alert Count for Procurement
$critical_count = 0;
foreach($report_data as $r) {
    if($r['quantity'] <= ($r['threshold'] ?? 5)) $critical_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Procurement Intelligence | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f7fa; }
        .main-content { margin-left: 320px; transition: all 0.4s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
        .executive-card { background: white; border: 1px solid rgba(28, 77, 141, 0.1); border-radius: 3rem; overflow: hidden; box-shadow: 0 20px 50px -15px rgba(28, 77, 141, 0.05); }
        .animate-reveal { animation: reveal 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; }
        @keyframes reveal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @media print { .no-print { display: none !important; } .main-content { margin-left: 0 !important; padding: 0 !important; } .executive-card { border: 1px solid #eee; border-radius: 1rem; } tfoot { background-color: #1C4D8D !important; color: white !important; } }
    </style>
</head>
<body class="antialiased text-slate-900">

    <div class="no-print">
        <?php include '../templates/sidebar_admin.php'; ?>
    </div>

    <main class="main-content min-h-screen p-8 lg:p-12 relative flex flex-col">
        <div class="no-print"><?php include '../includes/header.php'; ?></div>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-8 animate-reveal no-print">
            <div class="space-y-2">
                <span class="text-[10px] font-black uppercase tracking-[0.5em] text-[#1C4D8D]/60 italic block">Procurement Intelligence Node</span>
                <h2 class="text-6xl font-black text-[#1C4D8D] tracking-tighter uppercase italic leading-none">Global <span class="text-sky-500">Procurement</span></h2>
            </div>
            <button onclick="window.print()" class="bg-[#1C4D8D] text-white px-10 py-5 rounded-2xl font-black shadow-xl hover:bg-blue-800 transition-all text-[10px] uppercase tracking-widest flex items-center gap-3">
                <i class="fas fa-file-pdf text-sky-300"></i> Generate Audit PDF
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12 no-print animate-reveal" style="animation-delay: 0.1s;">
            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm group hover:border-[#1C4D8D] transition-all">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-4 italic">Capital Investment</span>
                <h4 class="text-3xl font-black text-[#1C4D8D] italic tracking-tighter leading-none"><?= number_format($grand_total) ?> <span class="text-[10px] font-bold text-slate-300 not-italic">MMK</span></h4>
            </div>

            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm group hover:border-rose-400 transition-all">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-4 italic">Critical Re-orders</span>
                <h4 class="text-5xl font-black text-rose-500 italic tracking-tighter leading-none"><?= $critical_count ?></h4>
            </div>

            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm group hover:border-sky-400 transition-all">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-4 italic">Asset Breadth</span>
                <h4 class="text-5xl font-black text-sky-500 italic tracking-tighter leading-none"><?= $total_assets ?></h4>
            </div>

            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm group hover:border-emerald-400 transition-all">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-4 italic">Supply Partners</span>
                <h4 class="text-5xl font-black text-emerald-500 italic tracking-tighter leading-none"><?= $total_suppliers ?></h4>
            </div>
        </div>

        <div class="mb-10 no-print animate-reveal" style="animation-delay: 0.2s;">
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100">
                <form action="" method="GET" class="flex flex-wrap md:flex-nowrap items-center gap-6">
                    <div class="flex-1 space-y-2">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] ml-4">Filter by Asset Classification</label>
                        <select name="category" onchange="this.form.submit()" 
                                class="w-full p-5 bg-slate-50 border-none rounded-2xl font-black text-[11px] uppercase tracking-widest text-[#1C4D8D] outline-none cursor-pointer">
                            <option value="ALL">--- Monitor All Categories ---</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $cat_filter === $cat ? 'selected' : '' ?>><?= strtoupper($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pt-6">
                        <a href="procurement_report.php" class="bg-rose-50 text-rose-500 px-10 py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest border border-rose-100 hover:bg-rose-500 hover:text-white transition-all">Reset Analytics</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="executive-card animate-reveal" style="animation-delay: 0.3s;">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-[#1C4D8D] text-[10px] font-black uppercase tracking-[0.25em] border-b border-slate-100">
                        <tr>
                            <th class="p-10">Vendor Source</th>
                            <th class="p-10">Asset Specifications</th>
                            <th class="p-10 text-center">Node Volume</th>
                            <th class="p-10">Operational Node</th>
                            <th class="p-10 text-right">Net Asset Valuation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach($report_data as $row): 
                            $is_low = ($row['quantity'] <= ($row['threshold'] ?? 5));
                        ?>
                        <tr class="hover:bg-sky-50 transition-all duration-300 group">
                            <td class="p-10">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-[#1C4D8D] group-hover:bg-[#1C4D8D] group-hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-handshake-angle"></i>
                                    </div>
                                    <div>
                                        <span class="font-black text-[#1C4D8D] uppercase block tracking-tighter text-base leading-none mb-1"><?= htmlspecialchars($row['supplier_name']); ?></span>
                                        <span class="text-[9px] text-emerald-500 font-bold uppercase tracking-widest italic">Verified Supply Link</span>
                                    </div>
                                </div>
                            </td>

                            <td class="p-10">
                                <span class="font-black text-slate-800 block text-lg italic tracking-tighter leading-none mb-2 uppercase"><?= htmlspecialchars($row['product_name']); ?></span>
                                <span class="text-[9px] bg-slate-100 px-3 py-1 rounded-md font-black text-slate-400 uppercase tracking-widest"><?= $row['category']; ?></span>
                            </td>

                            <td class="p-10 text-center">
                                <span class="font-black <?= $is_low ? 'text-rose-500' : 'text-slate-900' ?> text-4xl italic tracking-tighter leading-none"><?= $row['quantity']; ?></span>
                                <div class="mt-2">
                                    <?php if($is_low): ?>
                                        <span class="text-[8px] font-black text-rose-500 uppercase tracking-widest animate-pulse">Critical Breach</span>
                                    <?php else: ?>
                                        <span class="text-[8px] font-black text-slate-300 uppercase tracking-widest">Optimal Node</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="p-10 font-bold text-slate-500 italic uppercase tracking-tight">
                                <i class="fas fa-map-pin mr-2 text-sky-400"></i><?= htmlspecialchars($row['branch_name']); ?>
                            </td>

                            <td class="p-10 text-right">
                                <div class="flex flex-col items-end">
                                    <span class="font-black text-[#1C4D8D] text-2xl italic tracking-tighter leading-none mb-1"><?= number_format($row['total_value']); ?></span>
                                    <span class="text-[9px] text-slate-300 font-bold uppercase tracking-widest">MMK Valuation</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-[#1C4D8D] text-white">
                        <tr>
                            <td colspan="4" class="p-12 text-right font-black uppercase tracking-[0.3em] italic text-sm">Global Consolidated Investment Valuation:</td>
                            <td class="p-12 text-right">
                                <span class="text-sky-300 font-black text-5xl italic tracking-tighter block mb-1">
                                    <?= number_format($grand_total); ?>
                                </span>
                                <span class="text-[10px] font-black uppercase tracking-[0.5em] text-white/40">MMK Treasury Reserve</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-12 no-print"><?php include '../includes/footer.php'; ?></div>
    </main>

</body>
</html>