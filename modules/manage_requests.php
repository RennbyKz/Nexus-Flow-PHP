<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
authorize(['ADMIN']); 
$active_page = 'requests';

// --- ⭐ APPROVE LOGIC ---
if (isset($_GET['approve_id'])) {
    $rid = $_GET['approve_id'];
    try {
        $update = $pdo->prepare("UPDATE stock_requests SET status = 'APPROVED', approved_by = ? WHERE request_id = ?");
        $success = $update->execute([$_SESSION['user_id'], $rid]);

        if ($success) {
            header("Location: manage_requests.php?msg=approved");
        } else {
            throw new Exception("Update failed.");
        }
    } catch (Exception $e) {
        header("Location: manage_requests.php?error=" . urlencode($e->getMessage()));
    }
    exit();
}

// --- ⭐ REJECT LOGIC ---
if (isset($_POST['reject_request'])) {
    $rid = $_POST['request_id'];
    $remark = $_POST['remark'];
    $stmt = $pdo->prepare("UPDATE stock_requests SET status = 'REJECTED', remark = ?, approved_by = ? WHERE request_id = ?");
    $stmt->execute([$remark, $_SESSION['user_id'], $rid]);
    header("Location: manage_requests.php?msg=rejected");
    exit();
}

$requests_sql = "
    SELECT r.*, b.branch_name 
    FROM stock_requests r 
    JOIN branches b ON r.branch_id = b.branch_id 
    WHERE r.status = 'PENDING' 
    ORDER BY r.requested_at ASC
";
$requests = $pdo->query($requests_sql)->fetchAll();

$pending_count = count($requests);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Requests | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f7fa; }
        
        .main-content { margin-left: 320px; transition: all 0.4s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }

        .executive-card {
            background: white;
            border: 1px solid rgba(28, 77, 141, 0.1);
            border-radius: 3rem;
            overflow: hidden;
            box-shadow: 0 20px 50px -15px rgba(28, 77, 141, 0.05);
        }

        .animate-reveal { animation: reveal 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; }
        @keyframes reveal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Table Row Hover Sync */
        tbody tr { transition: all 0.3s ease; }
        tbody tr:hover { background: rgba(161, 227, 249, 0.05); }
    </style>
</head>
<body class="antialiased text-slate-900">

    <?php include '../templates/sidebar_admin.php'; ?>

    <main class="main-content min-h-screen p-8 lg:p-12 relative flex flex-col">
        <?php include '../includes/header.php'; ?>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-8 animate-reveal">
            <div class="space-y-2">
                <span class="text-[10px] font-black uppercase tracking-[0.5em] text-[#1C4D8D]/60 italic block">Allocation Surveillance</span>
                <h2 class="text-6xl font-black text-[#1C4D8D] tracking-tighter uppercase italic leading-none">Incoming <span class="text-sky-500">Requests</span></h2>
            </div>
            <div class="flex gap-4">
                <div class="bg-white px-8 py-5 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
                    <div class="w-10 h-10 bg-[#A1E3F9]/20 rounded-xl flex items-center justify-center text-[#1C4D8D]">
                        <i class="fas fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">Queue Status</span>
                        <span class="text-2xl font-black text-[#1C4D8D] italic tracking-tighter"><?= $pending_count ?> Nodes Pending</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="executive-card animate-reveal" style="animation-delay: 0.1s;">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-[#1C4D8D] text-[10px] font-black uppercase tracking-[0.25em] border-b border-slate-100">
                        <tr>
                            <th class="p-10">Asset & Timestamp</th>
                            <th class="p-10">Originating Node</th>
                            <th class="p-10 text-center">Batch Vol</th>
                            <th class="p-10 text-right">Decision Hub</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if(empty($requests)): ?>
                            <tr><td colspan="4" class="p-40 text-center">
                                <div class="opacity-20">
                                    <i class="fas fa-clipboard-check text-6xl mb-4"></i>
                                    <p class="font-black italic uppercase tracking-[0.4em] text-[10px]">Queue is currently optimized and empty</p>
                                </div>
                            </td></tr>
                        <?php endif; ?>

                        <?php foreach($requests as $r): ?>
                        <tr class="group">
                            <td class="p-10">
                                <div class="flex items-center gap-6">
                                    <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-[#1C4D8D] group-hover:text-white transition-all">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <div>
                                        <span class="text-xl font-black text-slate-800 block italic tracking-tighter uppercase leading-none mb-2"><?= htmlspecialchars($r['product_name']) ?></span>
                                        <span class="text-[9px] text-sky-500 font-bold uppercase italic tracking-widest flex items-center gap-2">
                                            <i class="far fa-clock"></i> HANDSHAKE: <?= date('d M Y | H:i', strtotime($r['requested_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-10">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-map-pin text-sky-400"></i>
                                    <span class="font-black italic text-slate-500 text-sm uppercase tracking-tighter"><?= htmlspecialchars($r['branch_name']) ?></span>
                                </div>
                            </td>
                            <td class="p-10 text-center">
                                <span class="text-4xl font-black text-[#1C4D8D] italic tracking-tighter leading-none"><?= $r['quantity'] ?></span>
                                <span class="block text-[8px] font-black text-slate-300 uppercase mt-2 tracking-widest italic">Target Units</span>
                            </td>
                            <td class="p-10 text-right">
                                <div class="flex justify-end gap-3">
                                    <button onclick="confirmApprove(<?= $r['request_id'] ?>, '<?= addslashes($r['product_name']) ?>')" 
                                            class="px-8 py-4 bg-emerald-500 text-white rounded-xl font-black text-[9px] uppercase tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-600 transition-all active:scale-90">
                                        Authorize
                                    </button>
                                    <button onclick="openRejectModal(<?= $r['request_id'] ?>)" 
                                            class="px-8 py-4 bg-rose-50 text-rose-500 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-rose-500 hover:text-white transition-all active:scale-90">
                                        Decline
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-auto pt-10"><?php include '../includes/footer.php'; ?></div>
    </main>

    <div id="rejectModal" class="hidden fixed inset-0 bg-[#1C4D8D]/80 backdrop-blur-xl z-[5000] flex items-center justify-center p-6" onclick="handleBackdropClick(event)">
        <div class="bg-white rounded-[4rem] w-full max-w-md shadow-2xl overflow-hidden animate-reveal relative">
            <i class="fas fa-ban absolute -right-6 -bottom-6 text-[12rem] text-slate-50 italic font-black pointer-events-none"></i>
            
            <div class="bg-slate-950 p-10 text-white flex justify-between items-center relative">
                <div>
                    <h3 class="text-3xl font-black uppercase italic tracking-tighter">Decline Sync</h3>
                    <p class="text-[9px] font-black text-rose-400 uppercase tracking-[0.3em] mt-2 italic">Security Protocol Reason Required</p>
                </div>
                <button onclick="closeModal()" class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center text-white hover:bg-rose-500 transition-all text-xl">&times;</button>
            </div>

            <form method="POST" class="p-10 relative z-10">
                <input type="hidden" name="request_id" id="reject_id">
                <div class="mb-8">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-3 italic">Reasoning Ledger</label>
                    <textarea name="remark" placeholder="Provide technical justification for rejection..." required 
                              class="w-full p-6 bg-slate-50 border-none rounded-3xl outline-none h-40 font-bold text-sm text-slate-700 shadow-inner focus:ring-8 focus:ring-rose-500/5 transition-all resize-none"></textarea>
                </div>
                <div class="flex flex-col gap-4">
                    <button type="submit" name="reject_request" class="w-full bg-slate-950 text-white p-7 rounded-[2rem] font-black uppercase tracking-[0.3em] text-[10px] shadow-2xl hover:bg-rose-600 transition-all active:scale-95">
                        Confirm Node Refusal
                    </button>
                    <button type="button" onclick="closeModal()" class="w-full text-slate-400 font-black uppercase text-[9px] tracking-widest py-2 hover:text-slate-900 transition-colors">Abort Process</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmApprove(id, name) {
            Swal.fire({
                title: '<span class="italic font-black uppercase">AUTHORIZE SYNC?</span>',
                text: `Approve the release of ${name} to branch node.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#1C4D8D',
                confirmButtonText: 'YES, AUTHORIZE',
                customClass: { popup: 'rounded-[3rem] p-10' }
            }).then((result) => { if (result.isConfirmed) window.location.href = `manage_requests.php?approve_id=${id}`; });
        }
        function openRejectModal(id) {
            document.getElementById('reject_id').value = id;
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        function closeModal() { document.getElementById('rejectModal').classList.add('hidden'); }
        function handleBackdropClick(e) { if(e.target.id === 'rejectModal') closeModal(); }
        document.addEventListener('keydown', (e) => { if(e.key === 'Escape') closeModal(); });

        // Notifications
        const params = new URLSearchParams(window.location.search);
        if(params.get('msg')) {
            Swal.fire({ 
                icon: 'success', 
                title: '<span class="italic font-black uppercase tracking-tighter">PROTOCOL UPDATED</span>', 
                text: 'The request has been processed and synced.', 
                confirmButtonColor: '#1C4D8D',
                customClass: { popup: 'rounded-[3rem] p-10' }
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>