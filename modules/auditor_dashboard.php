<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

// Authentication & Authorization Check
checkLogin();
authorize(['AUDITOR']); 
$active_page = 'dashboard';

try {
    // ၁။ Global Asset Valuation (Ecosystem တစ်ခုလုံး၏ တန်ဖိုးကို တွက်ချက်ခြင်း)
    $stmt = $pdo->query("SELECT SUM(quantity * price) as total_value, COUNT(product_id) as total_skus FROM products");
    $global_stats = $stmt->fetch();

    // ၂။ Branch-wise Aggregation (ဆိုင်ခွဲအလိုက် တန်ဖိုးခွဲခြမ်းစိတ်ဖြာခြင်း)
    $branch_stats = $pdo->query("
        SELECT b.branch_name, SUM(p.quantity * p.price) as branch_value, COUNT(p.product_id) as item_count 
        FROM branches b 
        LEFT JOIN products p ON b.branch_id = p.branch_id 
        GROUP BY b.branch_id, b.branch_name 
        ORDER BY branch_value DESC
    ")->fetchAll();
    
    // ၃။ Security Events (နောက်ဆုံးဖြစ်ပွားခဲ့သော Audit Logs များကို ဆွဲထုတ်ခြင်း)
    $logs = $pdo->query("SELECT l.*, u.username FROM audit_logs l JOIN users u ON l.user_id = u.user_id ORDER BY l.timestamp DESC LIMIT 6")->fetchAll();

    // ၄။ 🔥 Proactive Security Alert (အရေးကြီးသော လုပ်ဆောင်ချက်များကို Telegram ပို့ခြင်း)
    foreach($logs as $log) {
        // PURGE သို့မဟုတ် DELETE action များပါဝင်ပါက Auditor ဆီ အသိပေးမည်
        if (strpos($log['action'], 'PURGE') !== false || strpos($log['action'], 'DELETE') !== false) {
            $log_id = $log['log_id']; 
            // ထပ်ခါတလဲလဲ မပို့မိစေရန် Session ဖြင့် စစ်ဆေးခြင်း
            if (!isset($_SESSION['sent_logs'][$log_id])) {
                $alert = "🚨 <b>CRITICAL SECURITY AUDIT</b>\n"
                       . "--------------------------------\n"
                       . "⚠️ <b>Action:</b> " . $log['action'] . "\n"
                       . "👤 <b>User:</b> " . $log['username'] . "\n"
                       . "⏱ <b>Time:</b> " . date('d-M-Y h:i A', strtotime($log['timestamp'])) . "\n"
                       . "📝 <b>Details:</b> Potential Data Loss Event detected.";
                
                sendTelegramNotification($alert);
                $_SESSION['sent_logs'][$log_id] = true;
            }
        }
    }

    // ၅။ Manual Sync Report (Button နှိပ်မှ Telegram သို့ Report ပို့မည့်အပိုင်း)
    if (isset($_GET['sync_report'])) {
        $report = "📊 <b>ECOSYSTEM VALUATION REPORT</b>\n"
                . "--------------------------------\n"
                . "💰 <b>Total Value:</b> " . number_format($global_stats['total_value']) . " MMK\n"
                . "📦 <b>Total SKUs:</b> " . number_format($global_stats['total_skus']) . " Nodes\n"
                . "📍 <b>Top Branch:</b> " . ($branch_stats[0]['branch_name'] ?? 'N/A') . "\n"
                . "👤 <b>Requested by:</b> " . $_SESSION['username'] . "\n"
                . "📅 <b>As of:</b> " . date('d-M-Y H:i A');

        sendTelegramNotification($report);
        header("Location: auditor_dashboard.php?msg=report_sent");
        exit();
    }

} catch (PDOException $e) { 
    die("Audit Node Error: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditor Console | UniTrace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .animate-in { animation: fadeIn 0.4s ease-out forwards; opacity: 0; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased overflow-x-hidden">

    <?php include_once '../templates/sidebar_auditor.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300 min-h-screen">
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-10 animate-in flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div>
                <h2 class="text-5xl font-black text-slate-950 tracking-tighter uppercase italic leading-none">Security Console</h2>
                <p class="text-cyan-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-cyan-50 px-3 py-1 rounded-full inline-block italic border border-cyan-100">Immutable Compliance & Analytics Node</p>
            </div>
            <a href="?sync_report=true" class="bg-cyan-500 text-slate-950 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-950 hover:text-cyan-500 transition-all shadow-xl active:scale-95 flex items-center gap-3">
                <i class="fas fa-paper-plane"></i> Sync Report to Telegram
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10 animate-in" style="animation-delay: 0.1s;">
            <div class="bg-slate-950 p-10 rounded-[3.5rem] shadow-2xl text-white flex items-center gap-10 relative overflow-hidden group border border-cyan-900/20">
                <div class="absolute -right-6 -bottom-6 text-9xl text-cyan-500/5 font-black italic select-none"><i class="fas fa-shield-halved"></i></div>
                <div class="w-24 h-24 bg-cyan-500 rounded-[2.5rem] flex items-center justify-center text-4xl shadow-[0_0_40px_rgba(6,182,212,0.3)]">
                    <i class="fas fa-vault text-slate-950"></i>
                </div>
                <div>
                    <h4 class="text-cyan-400 text-[10px] font-black uppercase tracking-[0.3em] mb-2 opacity-80">Total Ecosystem Valuation</h4>
                    <p class="text-5xl font-black tracking-tighter italic">
                        <?= number_format($global_stats['total_value'] ?? 0); ?> 
                        <span class="text-xs font-bold text-slate-600 not-italic ml-2 uppercase tracking-widest">MMK</span>
                    </p>
                </div>
            </div>

            <div class="bg-white p-10 rounded-[3.5rem] shadow-sm border border-slate-100 flex items-center gap-10 group hover:shadow-xl hover:shadow-cyan-500/5 transition-all duration-500">
                <div class="w-24 h-24 bg-slate-50 text-slate-300 rounded-[2.5rem] flex items-center justify-center text-4xl group-hover:bg-cyan-500 group-hover:text-slate-950 group-hover:shadow-xl transition-all duration-500 shadow-inner">
                    <i class="fas fa-microchip"></i>
                </div>
                <div>
                    <h4 class="text-slate-400 text-[10px] font-black uppercase tracking-[0.3em] mb-2">Monitored Asset SKU</h4>
                    <p class="text-5xl font-black text-slate-900 tracking-tighter italic"><?= number_format($global_stats['total_skus'] ?? 0); ?></p>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8 mb-10">
            <div class="lg:col-span-2 bg-white rounded-[3.5rem] shadow-sm overflow-hidden border border-slate-100 animate-in" style="animation-delay: 0.2s;">
                <div class="p-10 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center">
                    <div>
                        <h3 class="font-black italic uppercase text-slate-800 tracking-tighter text-xl">Branch Integrity Audit</h3>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Value distribution per geographical node</p>
                    </div>
                    <i class="fas fa-project-diagram text-cyan-400 text-xl"></i>
                </div>
                <div class="p-6">
                    <table class="w-full">
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach($branch_stats as $bs): ?>
                            <tr class="hover:bg-slate-50/80 transition-all group">
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-1.5 h-6 bg-cyan-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <div>
                                            <p class="font-black text-slate-700 uppercase italic tracking-tighter text-lg leading-none mb-1"><?= htmlspecialchars($bs['branch_name']); ?></p>
                                            <p class="text-[9px] font-black text-slate-300 uppercase tracking-widest"><?= $bs['item_count']; ?> Live Assets Registered</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6 text-right">
                                    <p class="font-black text-slate-900 text-xl tracking-tighter mb-1"><?= number_format($bs['branch_value']); ?> <span class="text-[10px] text-slate-400 uppercase">MMK</span></p>
                                    <div class="w-48 bg-slate-100 h-1.5 rounded-full mt-2 ml-auto overflow-hidden shadow-inner">
                                        <div class="bg-cyan-500 h-full rounded-full shadow-[0_0_10px_rgba(6,182,212,0.5)]" 
                                             style="width: <?= ($global_stats['total_value'] > 0) ? ($bs['branch_value'] / $global_stats['total_value']) * 100 : 0; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-[3.5rem] shadow-sm overflow-hidden border border-slate-100 animate-in" style="animation-delay: 0.3s;">
                <div class="p-10 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center">
                    <h3 class="font-black italic uppercase text-slate-800 tracking-tighter text-xl">Live Trace</h3>
                    <span class="w-2 h-2 rounded-full bg-cyan-500 animate-ping"></span>
                </div>
                <div class="p-8 space-y-6">
                    <?php if(empty($logs)): ?>
                        <p class="text-center text-slate-300 font-black italic uppercase tracking-widest text-[10px] py-10">No recent logs detected</p>
                    <?php endif; ?>
                    <?php foreach($logs as $log): ?>
                    <div class="flex gap-4 relative pl-6 border-l-2 border-slate-50 hover:border-cyan-500 transition-colors duration-500 pb-2">
                        <div class="absolute -left-[5px] top-2 w-2 h-2 rounded-full bg-slate-200 group-hover:bg-cyan-500"></div>
                        <div class="flex-1">
                            <p class="text-[10px] font-black text-slate-900 uppercase leading-tight tracking-tight"><?= htmlspecialchars($log['action']); ?></p>
                            <div class="flex justify-between items-center mt-2">
                                <p class="text-[9px] font-bold text-cyan-600 tracking-widest uppercase italic"><?= htmlspecialchars($log['username']); ?></p>
                                <p class="text-[8px] font-black text-slate-300 uppercase"><?= date('h:i A', strtotime($log['timestamp'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <a href="audit_logs.php" class="block text-center p-4 bg-slate-950 text-cyan-500 rounded-2xl font-black text-[9px] uppercase tracking-[0.3em] hover:bg-cyan-500 hover:text-slate-950 transition-all duration-500 shadow-xl">Full Log Analysis</a>
                </div>
            </div>
        </div>

        <?php include_once '../includes/footer.php'; ?>
    </main>

    <script>
        // SweetAlert for Success Message
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('msg') && urlParams.get('msg') === 'report_sent') {
            Swal.fire({
                icon: 'success',
                title: 'REPORT DISPATCHED',
                text: 'Ecosystem analysis has been synchronized with Telegram.',
                confirmButtonColor: '#06b6d4',
                customClass: { popup: 'rounded-[3rem] p-10' }
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>