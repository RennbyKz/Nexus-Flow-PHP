<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';
checkLogin();
authorize(['ADMIN']);
$active_page = 'dashboard';

// --- DATA FETCHING ---
$totalValuation = $pdo->query("SELECT SUM(quantity * price) FROM products")->fetchColumn() ?: 0;
$pendingShipments = $pdo->query("SELECT COUNT(*) FROM stock_requests WHERE status = 'SHIPPED'")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity < CAST(metadata->>'threshold' AS INTEGER)")->fetchColumn();
$activeBranches = $pdo->query("SELECT COUNT(*) FROM branches WHERE status = 'ONLINE'")->fetchColumn();
$totalStock = $pdo->query("SELECT SUM(quantity) FROM products")->fetchColumn() ?: 0;
$critical_items = $pdo->query("SELECT p.product_name, p.quantity, b.branch_name FROM products p JOIN branches b ON p.branch_id = b.branch_id WHERE p.quantity <= 2 LIMIT 3")->fetchAll();
// --- MATERIALIZED VIEW DATA ---
$branchSummaries = $pdo->query("SELECT * FROM mv_branch_inventory_summary ORDER BY total_valuation DESC")->fetchAll();

// --- MONTHLY GROWTH DATA (Chart Logic) ---
$chartData = $pdo->query("
    SELECT to_char(created_at, 'Mon') as month, SUM(quantity * price) as val 
    FROM products 
    GROUP BY month, extract(month from created_at)
    ORDER BY extract(month from created_at) ASC
")->fetchAll();

$latest_products = $pdo->query("
    SELECT p.*, b.branch_name 
    FROM products p 
    JOIN branches b ON p.branch_id = b.branch_id 
    ORDER BY p.product_id DESC 
    LIMIT 5
")->fetchAll();

if (isset($_GET['report_sync'])) {
    $report = "🚀 <b>COMMAND CENTER SYNC</b>\n"
            . "--------------------------------\n"
            . "📊 <b>Total Valuation:</b> " . number_format($totalValuation) . " MMK\n"
            . "🏢 <b>Active Nodes:</b> " . $activeBranches . "\n"
            . "🚨 <b>Critical Alerts:</b> " . $lowStock . "\n"
            . "🚚 <b>In-Transit:</b> " . $pendingShipments . "\n"
            . "👤 <b>Triggered By:</b> " . $_SESSION['username'] . "\n"
            . "📅 <b>Time:</b> " . date('d-M-Y H:i A');

    sendTelegramNotification($report);
    header("Location: admin_dashboard.php?msg=sync_success");
    exit();
}

foreach($critical_items as $item) {
    //  Session သို့မဟုတ် Flag တစ်ခုခံ၍ တစ်ကြိမ်သာ ပို့ရန် 
    if (!isset($_SESSION['alert_sent_' . $item['product_name']])) {
        $alert = "🚨 <b>CRITICAL LOW STOCK</b>\n"
               . "--------------------------------\n"
               . "📦 <b>Item:</b> " . $item['product_name'] . "\n"
               . "📍 <b>Branch:</b> " . $item['branch_name'] . "\n"
               . "📉 <b>Stock Left:</b> " . $item['quantity'] . " Units\n"
               . "💡 <b>Action:</b> Restock Required Immediately.";
        
        sendTelegramNotification($alert);
        $_SESSION['alert_sent_' . $item['product_name']] = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Command Center | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f7fa; }
        .main-content { margin-left: 320px; transition: all 0.4s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
        .stat-card { background: white; border: 1px solid rgba(28, 77, 141, 0.08); transition: all 0.4s ease; }
        .branch-card.hidden { display: none; }
    </style>
</head>

<body class="antialiased text-slate-900 overflow-x-hidden">
<div class="no-print"><?php include '../templates/sidebar_admin.php'; ?></div>

<main class="main-content min-h-screen p-8 lg:p-12">
    <div class="no-print"><?php include '../includes/header.php'; ?></div>

    <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-8 no-print">
        <div class="space-y-2">
            <span class="text-[10px] font-black uppercase tracking-[0.5em] text-[#1C4D8D]/60 italic block">Global Logistics Operations</span>
            <h2 class="text-5xl font-black text-[#1C4D8D] tracking-tighter uppercase italic leading-none">Command <span class="text-sky-500">Center</span></h2>
        </div>
        <button onclick="window.print()" class="bg-[#1C4D8D] text-white px-8 py-5 rounded-2xl font-black shadow-xl hover:bg-blue-800 transition-all active:scale-95 flex items-center gap-3">
            <i class="fas fa-file-invoice text-sky-300"></i>
            <span class="tracking-widest uppercase text-[10px]">Generate Audit Report</span>
        </button>
        <a href="?report_sync=true" class="bg-sky-500/10 text-sky-500 px-6 py-5 rounded-2xl font-black border border-sky-500/20 hover:bg-sky-500 hover:text-white transition-all text-[10px] uppercase tracking-widest flex items-center gap-3">
            <i class="fas fa-paper-plane"></i> Sync to Telegram
        </a>
    </div>

    <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm mb-12 animate-in">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center text-white"><i class="fas fa-chart-line text-xs"></i></div>
            <h3 class="text-xl font-black text-[#1C4D8D] uppercase italic tracking-tighter">Valuation Growth Analysis</h3>
        </div>
        <div id="growth_chart" style="width: 100%; height: 320px;"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
        <div class="stat-card rounded-[3rem] p-10 bg-[#1C4D8D] text-white shadow-2xl relative overflow-hidden">
            <i class="fa fa-vault absolute -right-6 -bottom-6 text-9xl text-white/5 italic"></i>
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-sky-300 mb-6 italic">Total Valuation</p>
            <h3 class="text-3xl font-black italic tracking-tighter leading-none"><?= number_format($totalValuation); ?> <span class="text-xs">MMK</span></h3>
        </div>

        <div class="stat-card rounded-[3rem] p-10 border-emerald-100">
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-400 mb-6 italic">Active Nodes</p>
            <h3 class="text-5xl font-black text-emerald-500 italic tracking-tighter"><?= $activeBranches; ?></h3>
        </div>

        <div class="stat-card rounded-[3rem] p-10 border-rose-100">
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-400 mb-6 italic">Critical Alerts</p>
            <h3 class="text-5xl font-black text-rose-500 italic tracking-tighter"><?= $lowStock; ?></h3>
        </div>

        <div class="stat-card rounded-[3rem] p-10">
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-400 mb-6 italic">In-Transit</p>
            <h3 class="text-5xl font-black text-[#1C4D8D] italic tracking-tighter"><?= $pendingShipments; ?></h3>
        </div>
    </div>

    <div class="mb-12">
        <div class="flex justify-between items-center mb-8 px-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-[#1C4D8D] rounded-lg flex items-center justify-center text-white"><i class="fas fa-satellite-dish text-xs"></i></div>
                <h3 class="text-2xl font-black text-[#1C4D8D] uppercase italic tracking-tighter">Node Intelligence</h3>
            </div>
            <button id="seeMoreBtn" onclick="toggleBranches()" class="text-[10px] font-black uppercase tracking-widest text-sky-500 hover:text-indigo-900 transition-colors">See More <i class="fas fa-chevron-down ml-1"></i></button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6" id="branchContainer">
            <?php foreach($branchSummaries as $index => $row): ?>
            <div class="branch-card bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm transition-all hover:shadow-lg group <?= $index >= 4 ? 'hidden' : '' ?>">
                <div class="flex justify-between items-start mb-6">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none"><?= htmlspecialchars($row['branch_name']) ?></span>
                    <i class="fas fa-signal text-emerald-400 text-[10px]"></i>
                </div>
                <h4 class="text-2xl font-black italic text-[#1C4D8D] tracking-tighter"><?= number_format($row['total_valuation']) ?> <span class="text-[8px] not-italic text-slate-300">MMK</span></h4>
                <div class="mt-4 flex justify-between items-center border-t border-slate-50 pt-4">
                    <span class="text-[9px] font-black text-sky-500 uppercase"><?= $row['total_stock'] ?> Units</span>
                    <span class="text-[8px] text-slate-300 font-bold uppercase italic"><?= date('H:i', strtotime($row['last_updated'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="table-card mb-12">
        <div class="p-10 border-b border-slate-50 bg-slate-50/10 flex justify-between items-center no-print">
            <h3 class="text-2xl font-black text-[#1C4D8D] italic uppercase tracking-tighter">Global Inventory Trace</h3>
            <i class="fas fa-layer-group text-slate-200 text-2xl"></i>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] uppercase font-black tracking-widest text-[#1C4D8D]">
                    <tr><th class="p-8">Asset Identifier</th><th class="p-8 text-center">Batch Vol</th><th class="p-8 text-right">Valuation</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($latest_products as $p): ?>
                    <tr class="hover:bg-sky-50 transition-colors group">
                        <td class="p-8 font-black text-[#1C4D8D] uppercase italic tracking-tighter"><?= htmlspecialchars($p['product_name']); ?></td>
                        <td class="p-8 text-center font-black italic text-2xl text-slate-900"><?= $p['quantity']; ?></td>
                        <td class="p-8 text-right font-black text-xl italic text-slate-900"><?= number_format($p['price']); ?> <span class="text-[9px] text-slate-300">MMK</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="no-print"><?php include '../includes/footer.php'; ?></div>
</main>

<script>
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        var data = google.visualization.arrayToDataTable([
            ['Month', 'Valuation'],
            <?php foreach($chartData as $c) { echo "['".$c['month']."', ".$c['val']."],"; } ?>
        ]);

        var options = {
            curveType: 'function',
            legend: { position: 'none' },
            colors: ['#0ea5e9'],
            hAxis: { textStyle: { color: '#94a3b8', fontSize: 10, bold: true }, gridlines: { color: 'transparent' } },
            vAxis: { textStyle: { color: '#94a3b8', fontSize: 10 }, gridlines: { color: '#f8fafc' } },
            chartArea: { width: '90%', height: '80%' },
            backgroundColor: 'transparent'
        };

        var chart = new google.visualization.LineChart(document.getElementById('growth_chart'));
        chart.draw(data, options);
    }

    function toggleBranches() {
        const branches = document.querySelectorAll('.branch-card');
        const btn = document.getElementById('seeMoreBtn');
        let currentlyHidden = false;

        branches.forEach((el, index) => {
            if (index >= 4) {
                el.classList.toggle('hidden');
                if (el.classList.contains('hidden')) currentlyHidden = true;
            }
        });

        btn.innerHTML = currentlyHidden ? 
            'See More <i class="fas fa-chevron-down ml-1"></i>' : 
            'See Less <i class="fas fa-chevron-up ml-1"></i>';
    }
</script>
</body>
</html>