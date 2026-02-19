<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';
checkLogin();
authorize(['ADMIN']);
$active_page = 'branches';

// --- ၁။ CRUD Logic ---
if (isset($_POST['add_branch'])) {
    $stmt = $pdo->prepare("INSERT INTO branches (branch_name, location, status) VALUES (?, ?, 'ONLINE')");
    $stmt->execute([$_POST['branch_name'], $_POST['location']]);
    header("Location: branch_control.php?msg=added"); exit();
}
if (isset($_POST['update_branch'])) {
    $stmt = $pdo->prepare("UPDATE branches SET branch_name = ?, location = ? WHERE branch_id = ?");
    $stmt->execute([$_POST['branch_name'], $_POST['location'], $_POST['branch_id']]);
    header("Location: branch_control.php?msg=updated"); exit();
}
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM branches WHERE branch_id = ?")->execute([$_GET['delete_id']]);
    header("Location: branch_control.php?msg=deleted"); exit();
}
if (isset($_GET['toggle_id'])) {
    $new_status = ($_GET['current'] == 'ONLINE') ? 'OFFLINE' : 'ONLINE';
    $pdo->prepare("UPDATE branches SET status = ? WHERE branch_id = ?")->execute([$new_status, $_GET['toggle_id']]);
    header("Location: branch_control.php?msg=toggled"); exit();
}

// --- ၂။ DATA FETCHING (Materialized View နှင့် ချိတ်ဆက်ခြင်း) ---
// JOIN သုံးပြီး Branch အလိုက် Stock Summary ကို ယူပါမည်
$sql = "SELECT b.*, 
               COALESCE(mv.total_products, 0) as total_products, 
               COALESCE(mv.total_valuation, 0) as total_valuation 
        FROM branches b 
        LEFT JOIN mv_branch_inventory_summary mv ON b.branch_name = mv.branch_name
        ORDER BY b.branch_id ASC";
$branches = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Network Nodes | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .main-content { margin-left: 320px; transition: all 0.4s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
        .table-card { background: white; border-radius: 2.5rem; border: 1px solid rgba(28, 77, 141, 0.1); overflow: hidden; box-shadow: 0 20px 50px -15px rgba(28, 77, 141, 0.05); }
        .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background: #cbd5e1; transition: .4s; border-radius: 30px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background: #10b981; }
        input:checked + .slider:before { transform: translateX(22px); }
        .animate-reveal { animation: reveal 0.5s ease-out forwards; opacity: 0; }
        @keyframes reveal { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="antialiased text-slate-900 overflow-x-hidden">

    <?php include '../templates/sidebar_admin.php'; ?>

    <main class="main-content min-h-screen p-8 lg:p-12">
        <?php include '../includes/header.php'; ?>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-8 animate-reveal">
            <div>
                <h2 class="text-5xl font-black text-[#1C4D8D] tracking-tighter uppercase italic leading-none">Branch <span class="text-sky-500">Nodes</span></h2>
                <div class="flex items-center gap-4 mt-4">
                    <span class="w-12 h-1 bg-[#1C4D8D] rounded-full"></span>
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.5em] italic">System Network Infrastructure</p>
                </div>
            </div>
            
            <div class="relative w-full md:w-72">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                <input type="text" id="nodeSearch" placeholder="Trace node ID..." 
                       class="w-full pl-12 pr-4 py-4 bg-white border border-slate-100 rounded-2xl outline-none focus:ring-4 focus:ring-sky-500/10 font-bold text-xs">
            </div>

            <button onclick="openModal()" class="bg-[#1C4D8D] text-white px-10 py-5 rounded-2xl font-black shadow-2xl hover:bg-blue-800 transition-all active:scale-95 flex items-center gap-4">
                <i class="fas fa-plus-circle text-sky-300"></i>
                <span class="tracking-widest uppercase text-[10px]">Initialize New Node</span>
            </button>
        </div>

        <div class="table-card animate-reveal" style="animation-delay: 0.1s;">
            <div class="overflow-x-auto">
                <table id="nodeTable">
                    <thead class="bg-slate-50 text-[#1C4D8D] text-[10px] font-black uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="p-8 text-left">Node Identity</th>
                            <th class="p-8 text-left">Operational Area</th>
                            <th class="p-8 text-center">Stock Level</th>
                            <th class="p-8 text-right">Net Valuation</th>
                            <th class="p-8 text-center">Status</th>
                            <th class="p-8 text-right">Decisions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach($branches as $b): ?>
                        <tr class="hover:bg-sky-50/30 transition-all node-row">
                            <td class="p-8">
                                <div class="flex items-center gap-5">
                                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-[#1C4D8D] shadow-sm border border-slate-100">
                                        <i class="fas fa-network-wired"></i>
                                    </div>
                                    <div>
                                        <span class="font-black text-[#1C4D8D] block text-base uppercase italic tracking-tighter leading-none mb-1 node-name"><?= htmlspecialchars($b['branch_name']); ?></span>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest italic">ID: NODE_<?= $b['branch_id']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-8">
                                <div class="flex items-start gap-2 text-slate-500">
                                    <i class="fas fa-map-pin mt-1 text-sky-400 text-xs"></i>
                                    <span class="text-xs font-bold leading-tight"><?= htmlspecialchars($b['location']); ?></span>
                                </div>
                            </td>
                            <td class="p-8 text-center">
                                <span class="text-2xl font-black text-sky-500 italic tracking-tighter"><?= $b['total_products']; ?></span>
                                <p class="text-[7px] font-black text-slate-300 uppercase">Assets</p>
                            </td>
                            <td class="p-8 text-right">
                                <span class="text-xl font-black text-[#1C4D8D] italic tracking-tighter"><?= number_format($b['total_valuation']); ?></span>
                                <p class="text-[7px] font-black text-slate-300 uppercase italic">MMK Consolidated</p>
                            </td>
                            <td class="p-8 text-center">
                                <label class="switch">
                                    <input type="checkbox" <?= ($b['status'] == 'ONLINE') ? 'checked' : ''; ?> 
                                           onchange="window.location='?toggle_id=<?= $b['branch_id']; ?>&current=<?= $b['status']; ?>'">
                                    <span class="slider"></span>
                                </label>
                            </td>
                            <td class="p-8 text-right space-x-2">
                                <button onclick="openEditModal(<?= $b['branch_id']; ?>, '<?= addslashes($b['branch_name']); ?>', '<?= addslashes($b['location']); ?>')" 
                                        class="w-10 h-10 bg-slate-50 text-[#1C4D8D] rounded-xl hover:bg-sky-100 transition-all">
                                    <i class="fas fa-sliders-h text-xs"></i>
                                </button>
                                <button onclick="confirmDelete(<?= $b['branch_id']; ?>, '<?= addslashes($b['branch_name']); ?>')" 
                                        class="w-10 h-10 bg-rose-50 text-rose-400 rounded-xl hover:bg-rose-500 hover:text-white transition-all">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </main>

    <div id="branchModal" class="hidden fixed inset-0 bg-[#1C4D8D]/60 backdrop-blur-md z-[5000] flex items-center justify-center p-6" onclick="handleBackdropClick(event)">
        <div class="bg-white rounded-[3.5rem] w-full max-w-lg shadow-2xl overflow-hidden animate-reveal">
            <div class="bg-[#1C4D8D] p-12 text-white flex justify-between items-center">
                <div>
                    <h3 id="modalTitle" class="text-3xl font-black italic uppercase tracking-tighter">Node Config</h3>
                    <p class="text-[9px] font-black text-sky-300 uppercase tracking-widest mt-2 italic">Parameter Initialization</p>
                </div>
                <button onclick="closeModal()" class="text-2xl hover:rotate-90 transition-transform">&times;</button>
            </div>
            
            <form id="branchForm" method="POST" class="p-12 space-y-10">
                <input type="hidden" name="branch_id" id="f_id">
                <input type="text" name="branch_name" id="f_name" placeholder="Assign Technical Name" class="w-full p-6 bg-slate-50 rounded-[2rem] font-bold outline-none" required>
                <textarea name="location" id="f_loc" placeholder="Physical Address Details" class="w-full p-6 bg-slate-50 rounded-[2rem] font-bold h-32 outline-none" required></textarea>
                <button type="submit" name="add_branch" id="f_btn" class="w-full bg-[#1C4D8D] text-white py-8 rounded-[2.5rem] font-black uppercase tracking-widest text-[11px] shadow-2xl hover:bg-blue-800 transition-all">Synchronize Protocol</button>
            </form>
        </div>
    </div>

    <script>
        // ⭐ Live Node Filter Logic
        document.getElementById('nodeSearch').addEventListener('input', function(e) {
            const val = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.node-row');
            rows.forEach(row => {
                const name = row.querySelector('.node-name').innerText.toLowerCase();
                row.style.display = name.includes(val) ? '' : 'none';
            });
        });

        function openModal() { document.getElementById('modalTitle').innerText = "Register Node"; document.getElementById('f_btn').name = "add_branch"; document.getElementById('branchForm').reset(); document.getElementById('branchModal').classList.remove('hidden'); }
        function openEditModal(id, name, loc) { document.getElementById('modalTitle').innerText = "Update Config"; document.getElementById('f_btn').name = "update_branch"; document.getElementById('f_id').value = id; document.getElementById('f_name').value = name; document.getElementById('f_loc').value = loc; document.getElementById('branchModal').classList.remove('hidden'); }
        function closeModal() { document.getElementById('branchModal').classList.add('hidden'); }
        function handleBackdropClick(e) { if (e.target.id === 'branchModal') closeModal(); }
        document.addEventListener('keydown', (e) => { if (e.key === "Escape") closeModal(); });

        function confirmDelete(id, name) {
            Swal.fire({
                title: '<span class="italic font-black uppercase">TERMINATE NODE?</span>',
                text: `Authorize removal of "${name}" from network?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Terminate',
                customClass: { popup: 'rounded-[3rem] p-8' }
            }).then((res) => { if (res.isConfirmed) window.location.href = `branch_control.php?delete_id=${id}`; });
        }
    </script>
</body>
</html>