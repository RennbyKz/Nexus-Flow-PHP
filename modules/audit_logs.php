<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

// Access Control
checkLogin();
authorize(['ADMIN']); 
$active_page = 'audit_logs';

// --- ၁။ DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $log_id = $_GET['delete_id'];
    $pdo->prepare("DELETE FROM audit_logs WHERE log_id = ?")->execute([$log_id]);
    header("Location: audit_logs.php?msg=deleted"); 
    exit();
}

// --- ၂။ CRITICAL WIPE LOGIC (With Telegram Alert) ---
if (isset($_POST['clear_all'])) {
    try {
        $pdo->query("TRUNCATE TABLE audit_logs");

        // ⭐ Advanced Security: Telegram Notification on Ledger Wipe
        $msg = "🚨 <b>SECURITY CRITICAL: AUDIT LEDGER WIPED</b>\n"
             . "--------------------------------\n"
             . "⚠️ <b>Action:</b> TRUNCATE TABLE audit_logs\n"
             . "👤 <b>Operator:</b> " . $_SESSION['username'] . "\n"
             . "📅 <b>Time:</b> " . date('d-M-Y H:i A') . "\n"
             . "🚨 <b>Warning:</b> All forensic history has been permanently erased.";
        
        sendTelegramNotification($msg);

        header("Location: audit_logs.php?msg=cleared"); 
        exit();
    } catch (PDOException $e) { die("Wipe Error: " . $e->getMessage()); }
}

// --- ၃။ SEARCH & FILTER LOGIC (PostgreSQL Optimized) ---
$selected_branch = $_GET['branch_id'] ?? 'ALL';
$date_filter = $_GET['log_date'] ?? '';

try {
    $branches = $pdo->query("SELECT branch_id, branch_name FROM branches")->fetchAll();
    
    // SQL Construction
    $query = "SELECT l.*, u.username, b.branch_name 
              FROM audit_logs l 
              JOIN users u ON l.user_id = u.user_id 
              LEFT JOIN branches b ON u.branch_id = b.branch_id 
              WHERE 1=1";
    $params = [];

    if ($selected_branch !== 'ALL') {
        $query .= " AND u.branch_id = ?";
        $params[] = $selected_branch;
    }
    if ($date_filter) {
        // PostgreSQL-specific range filtering for BRIN Index efficiency
        $query .= " AND l.timestamp >= ? AND l.timestamp < (?::date + '1 day'::interval)";
        $params[] = $date_filter;
        $params[] = $date_filter;
    }

    $query .= " ORDER BY l.timestamp DESC LIMIT 150";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) { die("Trace Error: " . $e->getMessage()); }

// --- 💡 Forensic Helper Function ---
function highlightAction($action) {
    $action = htmlspecialchars($action);
    if (stripos($action, 'DELETE') !== false || stripos($action, 'TRUNCATE') !== false || stripos($action, 'PURGE') !== false) 
        return "🔴 <span class='text-rose-500 font-black'>$action</span>";
    if (stripos($action, 'INSERT') !== false || stripos($action, 'ADD') !== false) 
        return "🟢 <span class='text-emerald-500 font-black'>$action</span>";
    if (stripos($action, 'UPDATE') !== false || stripos($action, 'RESTORE') !== false) 
        return "🟡 <span class='text-amber-500 font-black'>$action</span>";
    return "🔵 <span class='text-sky-500'>$action</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Trace | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=JetBrains+Mono:wght@500&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f7fa; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .main-content { margin-left: 320px; transition: all 0.4s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
        .executive-card { background: white; border: 1px solid rgba(28, 77, 141, 0.1); border-radius: 3rem; overflow: hidden; box-shadow: 0 20px 50px -15px rgba(28, 77, 141, 0.05); }
        .animate-reveal { animation: reveal 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; }
        @keyframes reveal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @media print { .no-print { display: none !important; } .main-content { margin-left: 0 !important; padding: 0 !important; } .executive-card { border: 1px solid #eee; box-shadow: none; border-radius: 1rem; } }
    </style>
</head>
<body class="antialiased text-slate-900 overflow-x-hidden">

    <div class="no-print"><?php include_once "../templates/sidebar_admin.php"; ?></div>

    <main class="main-content min-h-screen p-8 lg:p-12 relative flex flex-col">
        <div class="no-print"><?php include_once '../includes/header.php'; ?></div>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-8 animate-reveal no-print">
            <div class="space-y-2">
                <span class="text-[10px] font-black  tracking-[0.5em] text-[#1C4D8D]/60 italic block">Forensic Activity Ledger</span>
                <h2 class="text-6xl font-black text-[#1C4D8D] tracking-tighter  italic leading-none">System <span class="text-sky-500">Trace</span></h2>
            </div>
            <div class="flex gap-4">
                <button onclick="window.print()" class="bg-white text-[#1C4D8D] border border-slate-200 px-8 py-5 rounded-2xl font-black shadow-sm text-[10px]  tracking-widest flex items-center gap-3 active:scale-95">
                    <i class="fas fa-file-pdf text-rose-500"></i> Export Forensic PDF
                </button>
                <form method="POST" onsubmit="return confirmClear(event)">
                    <button type="submit" name="clear_all" class="bg-rose-50 text-rose-500 px-8 py-5 rounded-2xl font-black text-[10px]  tracking-widest border border-rose-100 hover:bg-rose-500 hover:text-white transition-all active:scale-95">
                        <i class="fas fa-biohazard mr-2"></i> Wipe Ledger
                    </button>
                </form>
            </div>
        </div>

        <div class="mb-10 no-print animate-reveal" style="animation-delay: 0.1s;">
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100">
                <form method="GET" class="flex flex-wrap md:flex-nowrap items-center gap-6 flex-1">
                    <div class="flex-1 space-y-2">
                        <label class="text-[9px] font-black text-slate-400  tracking-widest ml-4">Terminal Node Scope</label>
                        <select name="branch_id" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-black text-[11px]  tracking-widest text-[#1C4D8D] outline-none">
                            <option value="ALL">Global Network scope</option>
                            <?php foreach($branches as $b): ?>
                                <option value="<?= $b['branch_id']; ?>" <?= $selected_branch == $b['branch_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($b['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex-1 space-y-2">
                        <label class="text-[9px] font-black text-slate-400  tracking-widest ml-4">Temporal Filter</label>
                        <input type="date" name="log_date" value="<?= $date_filter; ?>" class="w-full p-4 bg-slate-50 border-none rounded-2xl font-black text-[11px] outline-none text-[#1C4D8D]">
                    </div>
                    <div class="pt-6">
                        <button type="submit" class="bg-slate-900 text-white px-10 py-5 rounded-2xl font-black text-[10px]  tracking-widest hover:bg-[#1C4D8D] transition-all">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="executive-card animate-reveal" style="animation-delay: 0.2s;">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[#1C4D8D] text-[10px] font-black  tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="p-10">Timestamp Cluster</th>
                            <th class="p-10">Operator Entity</th>
                            <th class="p-10">System Interaction</th>
                            <th class="p-10">Origin Node</th>
                            <th class="p-10 text-right no-print">Control</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if(empty($logs)): ?>
                            <tr><td colspan="5" class="p-20 text-center text-slate-300 font-black italic  tracking-widest">No trace data detected in current scope</td></tr>
                        <?php endif; ?>
                        <?php foreach($logs as $l): ?>
                        <tr class="hover:bg-sky-50/30 transition-all duration-300 group">
                            <td class="p-10">
                                <span class="font-black text-slate-700 text-sm block mb-1"><?= date('d M, Y', strtotime($l['timestamp'])); ?></span>
                                <span class="text-[10px] text-sky-500 font-black  italic mono">
                                    <i class="far fa-clock mr-1"></i> <?= date('H:i:s:v', strtotime($l['timestamp'])); ?>
                                </span>
                            </td>
                            <td class="p-10">
                                <div class="flex items-center gap-4">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-[#1C4D8D] group-hover:text-white transition-all"><i class="fas fa-fingerprint text-xs"></i></div>
                                    <span class="font-black text-slate-800  text-xs italic tracking-tighter">@<?= htmlspecialchars($l['username']); ?></span>
                                </div>
                            </td>
                            <td class="p-10 font-bold text-xs italic leading-relaxed text-slate-500">
                                <?= highlightAction($l['action']); ?>
                            </td>
                            <td class="p-10">
                                <span class="inline-block bg-slate-50 px-4 py-2 rounded-full text-slate-400 text-[9px] font-black  tracking-widest border border-slate-100 italic">
                                    <i class="fas fa-network-wired mr-2 text-sky-300"></i><?= htmlspecialchars($l['branch_name'] ?? 'System Core'); ?>
                                </span>
                            </td>
                            <td class="p-10 text-right no-print">
                                <button onclick="confirmDelete(<?= $l['log_id']; ?>)" class="w-10 h-10 rounded-xl bg-rose-50 text-rose-300 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center ml-auto">
                                    <i class="fas fa-trash-can text-xs"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-auto pt-10 no-print"><?php include_once '../includes/footer.php'; ?></div>
    </main>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'PURGE ENTRY?',
                text: "Authorized removal of specific forensic record.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'YES, DELETE',
                customClass: { popup: 'rounded-[3rem] p-10' }
            }).then((res) => { if (res.isConfirmed) window.location.href = `audit_logs.php?delete_id=${id}`; });
        }

        function confirmClear(e) {
            e.preventDefault();
            Swal.fire({
                title: 'WIPE CORE LEDGER?',
                text: "Permanent erasure of ALL system history cannot be undone.",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'CONFIRM WIPE',
                customClass: { popup: 'rounded-[3rem] p-10' }
            }).then((res) => { if (res.isConfirmed) e.target.submit(); });
        }

        const params = new URLSearchParams(window.location.search);
        if(params.has('msg')) {
            Swal.fire({ icon: 'success', title: 'AUDIT UPDATED', text: 'Forensic records synchronized.', confirmButtonColor: '#1C4D8D', customClass: { popup: 'rounded-[3rem] p-10' } });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>