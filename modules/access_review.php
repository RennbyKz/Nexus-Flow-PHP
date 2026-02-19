<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

// Access Control - Auditor နှင့် Admin သာ ဝင်ရောက်ခွင့်ရှိသည်
checkLogin();
authorize(['AUDITOR', 'ADMIN']); 
$active_page = 'access_review';

// --- ၁။ SEARCH & FILTER LOGIC ---
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role_filter'] ?? '';

// User Activity နှင့် Privilege များကို ပေါင်းစပ်ဆွဲထုတ်ခြင်း
$sql = "SELECT u.user_id, u.username, u.status, r.role_name, 
               COUNT(l.log_id) as total_actions,
               MAX(l.timestamp) as last_activity
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        LEFT JOIN audit_logs l ON u.user_id = l.user_id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND u.username ILIKE ?";
    $params[] = "%$search%";
}
if (!empty($role_filter)) {
    $sql .= " AND u.role_id = ?";
    $params[] = $role_filter;
}

$sql .= " GROUP BY u.user_id, u.username, u.status, r.role_name
          ORDER BY last_activity DESC NULLS LAST";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users_review = $stmt->fetchAll();

// ၂။ Telegram Sync Logic: အကျဉ်းချုပ် Report ကို Telegram ပို့ခြင်း
if (isset($_GET['sync_access'])) {
    $total_users = count($users_review);
    $active_users = count(array_filter($users_review, fn($u) => $u['status'] === 'ACTIVE'));
    
    $report = "🛡️ <b>USER ACCESS SNAPSHOT</b>\n"
            . "--------------------------------\n"
            . "👥 <b>Total Managed:</b> $total_users Operators\n"
            . "🟢 <b>Active Nodes:</b> $active_users\n"
            . "🔴 <b>Disabled Nodes:</b> " . ($total_users - $active_users) . "\n"
            . "👤 <b>Audited By:</b> " . $_SESSION['username'] . "\n"
            . "📅 <b>Date:</b> " . date('d-M-Y');

    sendTelegramNotification($report);
    header("Location: access_review.php?msg=sync_complete");
    exit();
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Review | UniTrace</title>
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
                <h2 class="text-4xl font-black text-slate-950 tracking-tighter uppercase italic">Access Review</h2>
                <p class="text-cyan-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-cyan-50 px-3 py-1 rounded-full inline-block italic border border-cyan-100">User Privilege & Activity Audit</p>
            </div>
            <a href="?sync_access=true" class="bg-slate-950 text-cyan-500 px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-500 hover:text-slate-950 transition-all shadow-xl active:scale-95 flex items-center gap-3">
                <i class="fas fa-shield-alt"></i> Dispatch Access Report
            </a>
        </div>

        <div class="mb-8 animate-in" style="animation-delay: 0.1s;">
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-wrap gap-4 items-center">
                <form action="" method="GET" class="flex flex-wrap gap-4 flex-1">
                    <div class="relative flex-1 min-w-[250px]">
                        <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search operator by name..." 
                               class="w-full pl-12 pr-4 py-4 bg-slate-50 border-none rounded-2xl outline-none focus:ring-4 focus:ring-cyan-500/10 font-bold text-sm">
                    </div>
                    
                    <select name="role_filter" class="p-4 bg-slate-50 border-none rounded-2xl outline-none font-bold text-sm text-slate-500 min-w-[180px]">
                        <option value="">All Security Roles</option>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['role_id'] ?>" <?= $role_filter == $r['role_id'] ? 'selected' : '' ?>><?= $r['role_name'] ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="bg-slate-950 text-cyan-500 px-8 py-4 rounded-2xl font-black uppercase text-[10px] hover:bg-cyan-500 hover:text-slate-950 transition tracking-[0.2em]">Execute Search</button>
                    
                    <?php if(!empty($search) || !empty($role_filter)): ?>
                        <a href="access_review.php" class="p-4 bg-rose-50 text-rose-500 rounded-2xl font-black text-[10px] uppercase flex items-center transition hover:bg-rose-500 hover:text-white"><i class="fas fa-sync-alt"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden animate-in" style="animation-delay: 0.2s;">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-950 text-[10px] font-black uppercase text-cyan-500 tracking-widest border-b border-cyan-900/20">
                        <tr>
                            <th class="p-8">Operator Identity</th>
                            <th class="p-8">Security Role</th>
                            <th class="p-8 text-center">Activity Count</th>
                            <th class="p-8">Last Trace</th>
                            <th class="p-8 text-right">Account State</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if(empty($users_review)): ?>
                            <tr><td colspan="5" class="p-24 text-center text-slate-300 font-bold italic tracking-widest uppercase text-xs">No operators detected in scope.</td></tr>
                        <?php endif; ?>

                        <?php foreach($users_review as $u): ?>
                        <tr class="hover:bg-slate-50/80 transition-all group">
                            <td class="p-8">
                                <span class="font-black text-slate-800 block text-lg italic uppercase leading-none mb-1"><?= htmlspecialchars($u['username']) ?></span>
                                <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest italic border-l-2 border-cyan-500 pl-2">Identity Node #<?= $u['user_id'] ?></span>
                            </td>
                            <td class="p-8">
                                <span class="px-4 py-2 bg-slate-100 rounded-xl text-[10px] font-black uppercase border border-slate-200"><?= $u['role_name'] ?></span>
                            </td>
                            <td class="p-8 text-center">
                                <span class="text-xl font-black text-cyan-600 italic"><?= $u['total_actions'] ?></span>
                                <span class="block text-[8px] font-black text-slate-300 uppercase mt-1">Audit Traces</span>
                            </td>
                            <td class="p-8">
                                <span class="font-bold text-slate-500 text-xs italic">
                                    <?= $u['last_activity'] ? date('d M, Y | H:i', strtotime($u['last_activity'])) : '⚠️ Never Connected' ?>
                                </span>
                                <?php 
                                    // Inactive Risk Detection
                                    if ($u['last_activity']) {
                                        $days = (time() - strtotime($u['last_activity'])) / 86400;
                                        if ($days > 30) echo '<span class="block text-[8px] text-rose-500 font-black uppercase mt-1 italic animate-pulse">● Dormant Risk Node</span>';
                                    }
                                ?>
                            </td>
                            <td class="p-8 text-right">
                                <span class="px-4 py-2 rounded-full text-[9px] font-black uppercase tracking-widest <?= $u['status'] === 'ACTIVE' ? 'bg-cyan-50 text-cyan-600 border border-cyan-100' : 'bg-rose-50 text-rose-500 border border-rose-100' ?>">
                                    <?= $u['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include_once '../includes/footer.php'; ?>
    </main>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('msg') && urlParams.get('msg') === 'sync_complete') {
            Swal.fire({
                icon: 'success',
                title: 'ACCESS DISPATCHED',
                text: 'User privilege report synchronized with Telegram.',
                confirmButtonColor: '#06b6d4',
                customClass: { popup: 'rounded-[3rem] p-10' }
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>