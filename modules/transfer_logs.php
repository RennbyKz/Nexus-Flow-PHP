<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['MANAGER']); 
$active_page = 'transfer_logs';
$my_branch = $_SESSION['branch_id'];

// Filter Logic
$date_filter = $_GET['log_date'] ?? '';

try {
    $sql = "SELECT l.*, u.username 
            FROM audit_logs l
            JOIN users u ON l.user_id = u.user_id
            WHERE u.branch_id = ? AND (l.action ILIKE '%STOCK%' OR l.action ILIKE '%PRODUCT%')";
    
    if ($date_filter) {
        $sql .= " AND DATE(l.timestamp) = :d_filter";
    }

    $sql .= " ORDER BY l.timestamp DESC LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    if ($date_filter) {
        $stmt->bindParam(':d_filter', $date_filter);
    }
    $stmt->execute([$my_branch]);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) { die("Trace Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transfer Logs | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased">

    <?php include_once '../templates/sidebar_manager.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300">
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div>
                <h2 class="text-4xl font-black text-slate-950 tracking-tighter uppercase italic">Operation Traces</h2>
                <p class="text-violet-600 text-[10px] font-black uppercase tracking-[0.4em] mt-2">Local Activity Forensics</p>
            </div>
            
            <form action="" method="GET" class="no-print bg-white p-3 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
                <input type="date" name="log_date" value="<?= $date_filter ?>" class="bg-slate-50 border-none rounded-xl px-4 py-2 text-[10px] font-black outline-none focus:ring-2 focus:ring-violet-500/20">
                <button type="submit" class="bg-slate-900 text-white px-6 py-2 rounded-xl font-black uppercase text-[10px] tracking-widest hover:bg-violet-600 transition shadow-lg">Filter</button>
            </form>
        </div>

        <div class="bg-white rounded-[3.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] border-b">
                    <tr>
                        <th class="p-8">Time Sequence</th>
                        <th class="p-8">Operator Alias</th>
                        <th class="p-8">Operational Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="3" class="p-24 text-center text-slate-200 font-black italic uppercase tracking-widest text-xs">No local traces found in current node.</td></tr>
                    <?php endif; ?>

                    <?php foreach($logs as $l): ?>
                    <tr class="hover:bg-slate-50 transition-all duration-300">
                        <td class="p-8">
                            <span class="font-bold text-slate-700 text-xs block mb-1"><?= date('d M, Y', strtotime($l['timestamp'])) ?></span>
                            <span class="text-[10px] text-violet-500 font-black italic uppercase italic tracking-tighter"><?= date('h:i:s A', strtotime($l['timestamp'])) ?></span>
                        </td>
                        <td class="p-8">
                            <span class="font-black text-slate-800 uppercase italic tracking-tighter text-sm underline decoration-violet-200 decoration-2 italic underline-offset-4">@<?= htmlspecialchars($l['username']) ?></span>
                        </td>
                        <td class="p-8">
                            <p class="text-slate-500 font-bold leading-relaxed text-xs italic">"<?= htmlspecialchars($l['action']) ?>."</p>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>