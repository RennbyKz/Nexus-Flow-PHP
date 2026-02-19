<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['AUDITOR']); 
$active_page = 'audit_logs';

// --- ⭐ AUDITOR SPECIFIC FILTERS ---
$selected_severity = $_GET['severity'] ?? 'ALL'; 
$date_filter = $_GET['log_date'] ?? '';

try {
    $query = "SELECT l.*, u.username, r.role_name, b.branch_name 
              FROM audit_logs l 
              JOIN users u ON l.user_id = u.user_id 
              JOIN roles r ON u.role_id = r.role_id
              LEFT JOIN branches b ON u.branch_id = b.branch_id 
              WHERE 1=1";
    $params = [];

    if ($selected_severity !== 'ALL') {
        if ($selected_severity === 'CRITICAL') {
            $query .= " AND (l.action ILIKE '%DELETE%' OR l.action ILIKE '%REJECT%' OR l.action ILIKE '%CANCEL%')";
        } elseif ($selected_severity === 'SENSITIVE') {
            $query .= " AND (l.action ILIKE '%UPDATE%' OR l.action ILIKE '%APPROVE%')";
        }
    }

    if ($date_filter) {
        $query .= " AND DATE(l.timestamp) = ?";
        $params[] = $date_filter;
    }

    $query .= " ORDER BY l.timestamp DESC LIMIT 200";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) { die("Audit Node Failure: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Ledger | UniTrace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .animate-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media print { .no-print { display: none !important; } .main-content { margin-left: 0 !important; } }
    </style>
</head>
<body class="bg-slate-50 flex font-sans antialiased text-slate-800">

    <?php include_once '../templates/sidebar_auditor.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300 main-content">
        
        <?php include_once '../includes/header.php'; ?>

        <header class="flex justify-between items-start mb-12 no-print">
            <div class="animate-in">
                <h2 class="text-5xl font-black text-slate-900 tracking-tighter uppercase italic leading-none">Compliance Ledger</h2>
                <p class="text-cyan-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-cyan-50 px-3 py-1 rounded-full inline-block">Advanced Forensic Trace Analysis</p>
            </div>
            <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-cyan-600 transition shadow-2xl active:scale-95">
                <i class="fas fa-file-contract mr-2"></i> Certify Logs (PDF)
            </button>
        </header>

        <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 mb-8 no-print animate-in" style="animation-delay: 0.1s;">
            <form action="" method="GET" class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[8px] font-black text-slate-400 uppercase mb-2 ml-4 tracking-widest">Severity Level</label>
                    <select name="severity" class="w-full p-4 bg-slate-50 border-none rounded-2xl outline-none font-bold text-[10px] uppercase text-slate-600 tracking-widest cursor-pointer focus:ring-2 focus:ring-cyan-500/20 transition-all">
                        <option value="ALL">All Transactions</option>
                        <option value="CRITICAL" <?= $selected_severity == 'CRITICAL' ? 'selected' : '' ?>>Critical Deletions</option>
                        <option value="SENSITIVE" <?= $selected_severity == 'SENSITIVE' ? 'selected' : '' ?>>Sensitive Updates</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[8px] font-black text-slate-400 uppercase mb-2 ml-4 tracking-widest">Audit Date</label>
                    <input type="date" name="log_date" value="<?= $date_filter ?>" class="w-full p-4 bg-slate-50 border-none rounded-2xl outline-none font-bold text-[10px] text-slate-600 uppercase focus:ring-2 focus:ring-cyan-500/20 transition-all">
                </div>
                <div class="pt-6">
                    <button type="submit" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-cyan-600 transition-all shadow-xl">Apply Audit Filter</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-[3.5rem] shadow-sm border border-slate-100 overflow-hidden animate-in" style="animation-delay: 0.2s;">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-widest">Sequence</th>
                        <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-widest">Entity & Role</th>
                        <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-widest">Action Description</th>
                        <th class="p-8 text-[10px] font-black uppercase text-slate-400 tracking-widest">Integrity Level</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($logs as $l): 
                        $is_critical = stripos($l['action'], 'DELETE') !== false || stripos($l['action'], 'REJECT') !== false;
                        $is_update = stripos($l['action'], 'UPDATE') !== false || stripos($l['action'], 'APPROVE') !== false;
                    ?>
                    <tr class="hover:bg-slate-50/50 transition-colors duration-300">
                        <td class="p-8">
                            <span class="font-bold text-slate-700 text-xs block"><?= date('d M, Y', strtotime($l['timestamp'])) ?></span>
                            <span class="text-[10px] text-cyan-600 font-black italic tracking-tighter uppercase"><?= date('h:i A', strtotime($l['timestamp'])) ?></span>
                        </td>
                        <td class="p-8">
                            <div>
                                <span class="font-black text-slate-800 block text-sm italic tracking-widest uppercase"><?= htmlspecialchars($l['username']) ?></span>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest"><?= $l['role_name'] ?> • <?= $l['branch_name'] ?? 'HQ' ?></span>
                            </div>
                        </td>
                        <td class="p-8">
                            <p class="text-slate-500 font-bold leading-relaxed text-xs max-w-md italic">"<?= htmlspecialchars($l['action']) ?>."</p>
                        </td>
                        <td class="p-8">
                            <?php if($is_critical): ?>
                                <span class="px-3 py-1 bg-rose-50 text-rose-500 border border-rose-100 rounded-lg text-[8px] font-black uppercase animate-pulse">Critical Change</span>
                            <?php elseif($is_update): ?>
                                <span class="px-3 py-1 bg-cyan-50 text-cyan-600 border border-cyan-100 rounded-lg text-[8px] font-black uppercase">Sensitive Update</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-slate-100 text-slate-400 rounded-lg text-[8px] font-black uppercase">Informational</span>
                            <?php endif; ?>
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