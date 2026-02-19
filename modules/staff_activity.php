<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['MANAGER']); 

$target_user = $_GET['uid'] ?? null;
$my_branch = $_SESSION['branch_id'];

if (!$target_user) { header("Location: branch_staff.php"); exit(); }

// --- ⭐ ၁။ DELETE LOGIC ---
if (isset($_GET['del_id'])) {
    $pdo->prepare("DELETE FROM audit_logs WHERE log_id = ?")->execute([$_GET['del_id']]);
    header("Location: staff_activity.php?uid=$target_user&msg=deleted"); exit();
}

if (isset($_POST['wipe_staff_history'])) {
    $pdo->prepare("DELETE FROM audit_logs WHERE user_id = ?")->execute([$target_user]);
    header("Location: staff_activity.php?uid=$target_user&msg=wiped"); exit();
}

// ၂။ User အချက်အလက်နှင့် မှတ်တမ်းများ ဆွဲယူခြင်း
try {
    $u_stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ? AND branch_id = ?");
    $u_stmt->execute([$target_user, $my_branch]);
    $user_info = $u_stmt->fetch();

    if (!$user_info) { die("Access Denied: Node mismatch."); }

    $stmt = $pdo->prepare("SELECT * FROM audit_logs WHERE user_id = ? ORDER BY timestamp DESC");
    $stmt->execute([$target_user]);
    $activities = $stmt->fetchAll();
} catch (PDOException $e) { die("Trace Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Trace | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @media print { .no-print { display: none !important; } .main-content { margin-left: 0 !important; } }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased overflow-x-hidden">

    <?php include_once '../templates/sidebar_manager.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300 main-content">
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 no-print">
            <div>
                <a href="branch_staff.php" class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-4 inline-block hover:translate-x-1 transition-transform">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Personnel Node
                </a>
                <h2 class="text-4xl font-black text-slate-950 tracking-tighter uppercase italic leading-none">Activity Trace</h2>
                <p class="text-violet-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3">Operator: @<?= htmlspecialchars($user_info['username']) ?></p>
            </div>
            <div class="flex gap-3">
                <button onclick="confirmWipe()" class="bg-rose-50 text-rose-500 px-6 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest border border-rose-100 hover:bg-rose-500 hover:text-white transition">
                    <i class="fas fa-trash-sweep mr-2"></i> Wipe History
                </button>
                <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-2xl hover:bg-violet-600 transition">
                    <i class="fas fa-file-pdf mr-2 text-rose-400"></i> Export Report
                </button>
            </div>
        </div>

        <div class="hidden print:block mb-10 border-b-4 border-slate-900 pb-6 text-center">
            <h1 class="text-3xl font-black uppercase italic">Operator Activity Audit</h1>
            <p class="text-sm font-bold mt-2">Target Operator: @<?= htmlspecialchars($user_info['username']) ?> | Node: <?= $_SESSION['branch_name'] ?></p>
            <p class="text-[10px] uppercase tracking-widest mt-1">Generated on: <?= date('d M Y, h:i A') ?></p>
        </div>

        <div class="bg-white rounded-[4rem] shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] border-b">
                    <tr>
                        <th class="p-10">Time Sequence</th>
                        <th class="p-10">Operational Detail</th>
                        <th class="p-10 text-right no-print">Decision</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if(empty($activities)): ?>
                        <tr><td colspan="3" class="p-32 text-center text-slate-300 font-black italic uppercase tracking-[0.4em] text-xs">No activity traces found for this node operator.</td></tr>
                    <?php endif; ?>

                    <?php foreach($activities as $a): ?>
                    <tr class="hover:bg-slate-50/50 transition-all duration-300 group">
                        <td class="p-10">
                            <span class="font-bold text-slate-700 text-xs block mb-1"><?= date('d M, Y', strtotime($a['timestamp'])) ?></span>
                            <span class="text-[10px] text-violet-500 font-black italic uppercase italic tracking-tighter"><?= date('h:i:s A', strtotime($a['timestamp'])) ?></span>
                        </td>
                        <td class="p-10">
                            <p class="text-slate-600 font-bold leading-relaxed text-sm italic">"<?= htmlspecialchars($a['action']) ?>."</p>
                        </td>
                        <td class="p-10 text-right no-print">
                            <button onclick="confirmDelete(<?= $a['log_id'] ?>)" class="w-10 h-10 rounded-xl bg-rose-50 text-rose-300 hover:bg-rose-500 hover:text-white transition-all shadow-inner">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <form id="wipeForm" method="POST" style="display:none;"><input type="hidden" name="wipe_staff_history" value="1"></form>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'DELETE TRACE?',
                text: "Remove this specific activity record?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Delete'
            }).then((res) => { if (res.isConfirmed) window.location.href = `staff_activity.php?uid=<?= $target_user ?>&del_id=${id}`; });
        }

        function confirmWipe() {
            Swal.fire({
                title: 'WIPE OPERATOR HISTORY?',
                text: "This will permanently erase ALL logs for @<?= htmlspecialchars($user_info['username']) ?>!",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Wipe All'
            }).then((res) => { if (res.isConfirmed) document.getElementById('wipeForm').submit(); });
        }

        const params = new URLSearchParams(window.location.search);
        if(params.has('msg')) {
            Swal.fire({ icon: 'success', title: 'AUDIT SYNCED', confirmButtonColor: '#7c3aed' });
            window.history.replaceState({}, document.title, window.location.pathname + `?uid=<?= $target_user ?>`);
        }
    </script>
</body>
</html>