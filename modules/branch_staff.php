<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['MANAGER']); 
$active_page = 'branch_staff';
$my_branch = $_SESSION['branch_id'];

try {
    // ⭐ ID 3 (STOCKKEEPER) ကို တိုက်ရိုက်စစ်ဆေးခြင်းဖြင့် ပိုမိုတိကျစေသည်
    $sql = "SELECT u.user_id, u.username, u.status,
            (SELECT action FROM audit_logs WHERE user_id = u.user_id ORDER BY timestamp DESC LIMIT 1) as last_activity,
            (SELECT timestamp FROM audit_logs WHERE user_id = u.user_id ORDER BY timestamp DESC LIMIT 1) as last_seen
            FROM users u
            WHERE u.branch_id = ? AND u.role_id = 3
            ORDER BY u.username ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$my_branch]);
    $staffs = $stmt->fetchAll();
} catch (PDOException $e) { die("Personnel Sync Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Personnel Node | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .animate-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased overflow-x-hidden">

    <?php include_once '../templates/sidebar_manager.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300">
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 animate-in">
            <div>
                <h2 class="text-5xl font-black text-slate-950 tracking-tighter uppercase italic leading-none">Personnel Node</h2>
                <p class="text-violet-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-violet-50 px-4 py-1.5 rounded-full inline-block border border-violet-100 italic leading-none">
                    Node Operators: <?= $_SESSION['branch_name'] ?>
                </p>
            </div>
            <div class="bg-white px-8 py-4 rounded-[2rem] shadow-sm border border-slate-100">
                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-1 text-right">Registered Staff</span>
                <span class="text-2xl font-black text-violet-600 italic leading-none"><?= count($staffs) ?> Units</span>
            </div>
        </div>

        <div class="bg-white rounded-[4rem] shadow-sm border border-slate-100 overflow-hidden animate-in" style="animation-delay: 0.1s;">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-950 text-[10px] font-black uppercase text-violet-400 tracking-widest border-b border-violet-900/20">
                    <tr>
                        <th class="p-10">Operator Identity</th>
                        <th class="p-10">Latest Operational Trace</th>
                        <th class="p-10 text-center">Node State</th>
                        <th class="p-10 text-right">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if(empty($staffs)): ?>
                        <tr><td colspan="4" class="p-32 text-center text-slate-300 font-black italic uppercase tracking-[0.4em] text-xs">No operators assigned to this branch node.</td></tr>
                    <?php endif; ?>

                    <?php foreach($staffs as $s): ?>
                    <tr class="hover:bg-slate-50/80 transition-all group duration-300">
                        <td class="p-10">
                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 bg-slate-100 rounded-3xl flex items-center justify-center text-slate-400 group-hover:bg-violet-600 group-hover:text-white transition-all duration-500 shadow-inner">
                                    <i class="fas fa-user-gear text-xl"></i>
                                </div>
                                <div>
                                    <span class="font-black text-slate-800 block text-xl italic uppercase tracking-tighter leading-none mb-1 group-hover:text-violet-600 transition-colors">@<?= htmlspecialchars($s['username']) ?></span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest italic leading-none">Security Cleared</span>
                                </div>
                            </div>
                        </td>

                       <td class="p-10">
                            <?php if($s['last_activity']): ?>
                                <a href="staff_activity.php?uid=<?= $s['user_id'] ?>" class="group/trace block">
                                    <p class="text-xs font-bold text-slate-700 leading-snug italic mb-1 group-hover/trace:text-blue-600 transition-colors">
                                        "<?= htmlspecialchars($s['last_activity']) ?>"
                                    </p>
                                    <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest italic flex items-center gap-2">
                                        <i class="far fa-clock"></i> <?= date('d M, h:i A', strtotime($s['last_seen'])) ?> 
                                        <span class="underline decoration-blue-200 decoration-2 underline-offset-4 ml-2 group-hover/trace:text-blue-700">View Full Trace <i class="fas fa-external-link-alt ml-1 text-[7px]"></i></span>
                                    </span>
                                </a>
                            <?php else: ?>
                                <span class="text-[10px] text-slate-300 font-bold uppercase italic tracking-widest">No activity trace</span>
                            <?php endif; ?>
                        </td>

                        <td class="p-10 text-center">
                            <div class="inline-flex items-center gap-3 bg-slate-50 px-5 py-2 rounded-full border border-slate-100">
                                <span class="w-2 h-2 rounded-full <?= $s['status'] === 'ACTIVE' ? 'bg-emerald-500 animate-pulse shadow-[0_0_8px_#10b981]' : 'bg-rose-500' ?>"></span>
                                <span class="font-black text-[10px] uppercase tracking-widest <?= $s['status'] === 'ACTIVE' ? 'text-emerald-600' : 'text-rose-500' ?>"><?= $s['status'] ?></span>
                            </div>
                        </td>

                        <td class="p-10 text-right">
                            <span class="font-mono text-xs text-slate-300 font-bold tracking-tighter italic">OP-00<?= $s['user_id'] ?></span>
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