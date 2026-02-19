<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
authorize(['ADMIN']);
$active_page = 'shipments';

// --- ၁။ DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    $pdo->prepare("DELETE FROM stock_requests WHERE request_id = ? AND status = 'RECEIVED'")->execute([$del_id]);
    header("Location: shipment_tracking.php?msg=deleted");
    exit();
}

// --- ၂။ RECEIVE LOGIC ---
if (isset($_POST['confirm_receive'])) {
    $rid = $_POST['request_id'];
    $actual_qty = $_POST['actual_qty'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM stock_requests WHERE request_id = ?");
        $stmt->execute([$rid]);
        $order = $stmt->fetch();

        if ($order) {
            $remark = ($actual_qty != $order['quantity']) ? "Shortage: Received $actual_qty of " . $order['quantity'] : "Full order received";
            $updateReq = $pdo->prepare("UPDATE stock_requests SET status = 'RECEIVED', received_at = NOW(), remark = ? WHERE request_id = ?");
            $updateReq->execute([$remark, $rid]);
            $pdo->commit();
            header("Location: shipment_tracking.php?msg=success");
            exit();
        }
    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $error = "Update Error: " . $e->getMessage();
    }
}

// --- ၃။ DATA RETRIEVAL ---
$filter = $_GET['filter'] ?? 'ALL';
$sql = "SELECT sr.*, s.name as supplier_name, b.branch_name 
        FROM stock_requests sr
        JOIN suppliers s ON sr.supplier_id = s.supplier_id
        JOIN branches b ON sr.branch_id = b.branch_id
        WHERE sr.status IN ('SHIPPED', 'RECEIVED')";

if ($filter === 'SHIPPED') $sql .= " AND sr.status = 'SHIPPED'";
if ($filter === 'RECEIVED') $sql .= " AND sr.status = 'RECEIVED'";

$sql .= " ORDER BY sr.received_at DESC, sr.requested_at DESC";
$shipments = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logistics Hub | OMNI FLOW</title>
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
            border-radius: 3.5rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .executive-card:hover { transform: translateY(-10px); box-shadow: 0 30px 60px -15px rgba(28, 77, 141, 0.1); }

        .animate-reveal { animation: reveal 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; }
        @keyframes reveal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="antialiased text-slate-900 overflow-x-hidden">

    <?php include '../templates/sidebar_admin.php'; ?>

    <main class="main-content min-h-screen p-8 lg:p-12 relative flex flex-col">
        <div class="no-print"><?php include '../includes/header.php'; ?></div>

        <div class="mb-12 flex flex-col xl:flex-row justify-between items-start xl:items-end gap-8 animate-reveal">
            <div class="space-y-2">
                <span class="text-[10px] font-black uppercase tracking-[0.5em] text-[#1C4D8D]/60 italic block">Supply Chain Surveillance</span>
                <h2 class="text-6xl font-black text-[#1C4D8D] tracking-tighter uppercase italic leading-none">Logistics <span class="text-sky-500">Hub</span></h2>
            </div>
            
            <div class="flex flex-wrap gap-4 no-print">
                <button onclick="exportToPDF()" class="bg-white text-[#1C4D8D] border border-slate-200 px-8 py-5 rounded-2xl font-black shadow-sm text-[10px] uppercase tracking-widest flex items-center gap-3 hover:bg-slate-50 transition-all">
                    <i class="fas fa-file-pdf text-rose-500"></i> Export Audit
                </button>
                
                <div class="bg-slate-200/50 p-2 rounded-[2rem] flex gap-2">
                    <a href="?filter=ALL" class="px-6 py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition <?= $filter === 'ALL' ? 'bg-[#1C4D8D] text-white shadow-lg' : 'text-slate-500 hover:bg-white/50' ?>">All</a>
                    <a href="?filter=SHIPPED" class="px-6 py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition <?= $filter === 'SHIPPED' ? 'bg-amber-500 text-white shadow-lg' : 'text-slate-500 hover:bg-white/50' ?>">In-Transit</a>
                    <a href="?filter=RECEIVED" class="px-6 py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition <?= $filter === 'RECEIVED' ? 'bg-emerald-500 text-white shadow-lg' : 'text-slate-500 hover:bg-white/50' ?>">History</a>
                </div>
            </div>
        </div>

        <div id="printArea" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-10 animate-reveal" style="animation-delay: 0.1s;">
            <?php foreach($shipments as $s): ?>
            <div class="executive-card p-10 flex flex-col justify-between relative group">
                
                <?php if($s['status'] === 'RECEIVED'): ?>
                <button onclick="confirmDelete(<?= $s['request_id'] ?>)" class="absolute right-10 top-10 text-slate-200 hover:text-rose-500 transition-all z-20 group-hover:scale-125">
                    <i class="fas fa-trash-can text-lg"></i>
                </button>
                <?php endif; ?>

                <div>
                    <div class="flex justify-between items-start mb-8">
                        <div class="w-16 h-16 flex items-center justify-center rounded-[2rem] shadow-inner <?= $s['status'] === 'SHIPPED' ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600' ?>">
                            <i class="fas <?= $s['status'] === 'SHIPPED' ? 'fa-truck-fast animate-pulse' : 'fa-check-double' ?> text-2xl"></i>
                        </div>
                        <div class="text-right">
                            <span class="block text-[8px] font-black text-slate-300 uppercase tracking-widest italic mb-1">Entry Protocol</span>
                            <span class="text-xs font-black text-[#1C4D8D]">#LOG-<?= str_pad($s['request_id'], 5, '0', STR_PAD_LEFT) ?></span>
                        </div>
                    </div>

                    <h3 class="text-3xl font-black text-[#1C4D8D] uppercase italic mb-2 tracking-tighter truncate leading-none"><?= htmlspecialchars($s['product_name']) ?></h3>
                    <div class="flex items-center gap-2 mb-8">
                        <span class="w-2 h-2 rounded-full bg-sky-400 shadow-[0_0_8px_#38bdf8]"></span>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Source: <?= htmlspecialchars($s['supplier_name']) ?></p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-10">
                        <div class="p-5 bg-slate-50 rounded-[2rem] border border-slate-100 group-hover:bg-white transition-colors">
                            <span class="block text-[8px] font-black text-slate-300 uppercase tracking-widest mb-2 italic">Batch Vol</span>
                            <span class="text-3xl font-black text-[#1C4D8D] italic tracking-tighter"><?= $s['quantity'] ?></span>
                        </div>
                        <div class="p-5 bg-slate-50 rounded-[2rem] border border-slate-100 group-hover:bg-white transition-colors">
                            <span class="block text-[8px] font-black text-slate-300 uppercase tracking-widest mb-2 italic">Target Node</span>
                            <span class="text-[10px] font-black text-slate-600 uppercase tracking-tight truncate block"><?= $s['branch_name'] ?></span>
                        </div>
                    </div>
                </div>

                <?php if($s['status'] === 'SHIPPED'): ?>
                    <button onclick="openReceiveModal(<?= $s['request_id'] ?>, '<?= addslashes($s['product_name']) ?>', <?= $s['quantity'] ?>)" 
                            class="w-full bg-slate-950 text-white p-6 rounded-[2.5rem] font-black uppercase tracking-[0.3em] hover:bg-emerald-600 transition-all active:scale-95 shadow-2xl flex items-center justify-center gap-4 group/btn">
                        <i class="fas fa-barcode group-hover/btn:rotate-12 transition-transform"></i> Confirm Delivery
                    </button>
                <?php else: ?>
                    <div class="pt-8 border-t border-dashed border-slate-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[9px] text-slate-300 font-bold uppercase tracking-widest mb-1 italic">Verified Timestamp</p>
                                <p class="text-[11px] text-[#1C4D8D] font-black italic tracking-tighter"><?= date('d M, Y | H:i', strtotime($s['received_at'])) ?></p>
                            </div>
                            <?php if($s['remark']): ?>
                                <span class="px-4 py-1.5 bg-sky-50 text-sky-600 rounded-full text-[8px] font-black uppercase border border-sky-100 italic"><?= $s['remark'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-auto pt-10"><?php include '../includes/footer.php'; ?></div>
    </main>

    <div id="receiveModal" class="hidden fixed inset-0 bg-[#1C4D8D]/80 backdrop-blur-xl z-[5000] flex items-center justify-center p-6">
        <div class="bg-white rounded-[4rem] w-full max-w-md shadow-2xl overflow-hidden animate-reveal relative">
            <i class="fas fa-boxes-packing absolute -right-6 -bottom-6 text-[12rem] text-slate-50 italic font-black pointer-events-none"></i>
            
            <div class="bg-emerald-600 p-12 text-white text-center relative overflow-hidden">
                <div class="w-20 h-20 bg-white/20 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <i class="fas fa-truck-ramp-box text-3xl"></i>
                </div>
                <h3 class="text-3xl font-black italic uppercase tracking-tighter">Goods Verification</h3>
                <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-[0.4em] mt-3">Inventory Node Check-in</p>
            </div>

            <form method="POST" class="p-12 space-y-10 relative z-10">
                <input type="hidden" name="request_id" id="modal_request_id">
                <div class="text-center space-y-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.4em] italic">Final Received Quantity</label>
                    <input type="number" name="actual_qty" id="modal_actual_qty" required 
                           class="w-full bg-slate-50 border-none rounded-[3rem] p-10 text-center text-7xl font-black text-[#1C4D8D] outline-none focus:ring-8 focus:ring-emerald-500/5 shadow-inner tracking-tighter">
                </div>
                <div class="flex flex-col gap-4">
                    <button type="submit" name="confirm_receive" class="w-full bg-slate-950 text-white p-8 rounded-[2.5rem] font-black uppercase tracking-[0.3em] text-[12px] hover:bg-emerald-600 transition-all active:scale-95 shadow-2xl">Authorize Entry</button>
                    <button type="button" onclick="closeModal()" class="w-full text-slate-400 font-black uppercase text-[9px] tracking-widest py-2 hover:text-slate-900 transition-colors">Abort Sync</button>
                </div>
            </form>
        </div>
    </div>

    

    <script>
        // Modal & Sync Logic
        function openReceiveModal(id, name, qty) {
            document.getElementById('modal_request_id').value = id;
            document.getElementById('modal_actual_qty').value = qty;
            document.getElementById('receiveModal').classList.remove('hidden');
        }
        function closeModal() { document.getElementById('receiveModal').classList.add('hidden'); }
        document.addEventListener('keydown', (e) => { if(e.key === 'Escape') closeModal(); });

        // Protocol Notifications
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'success') {
            Swal.fire({ icon: 'success', title: '<span class="italic font-black uppercase tracking-tighter">NODE SYNCED</span>', text: 'Asset successfully added to local repository.', confirmButtonColor: '#10b981', customClass: { popup: 'rounded-[3rem] p-10' } });
        }
        if(urlParams.get('msg') === 'deleted') {
            Swal.fire({ icon: 'info', title: '<span class="italic font-black uppercase tracking-tighter">LOG PURGED</span>', text: 'Transaction history successfully archived.', confirmButtonColor: '#1C4D8D', customClass: { popup: 'rounded-[3rem] p-10' } });
        }

        function confirmDelete(id) {
            Swal.fire({
                title: '<span class="italic font-black uppercase">PURGE HISTORY?</span>',
                text: "Permanent removal of this entry from logistics ledger.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#1C4D8D',
                confirmButtonText: 'YES, PURGE',
                customClass: { popup: 'rounded-[3rem] p-10' }
            }).then((result) => { if (result.isConfirmed) window.location.href = `shipment_tracking.php?delete_id=${id}`; })
        }

        // Executive PDF Export
        function exportToPDF() {
            const currentDateTime = new Date().toLocaleString();
            let tableRows = "";
            <?php foreach($shipments as $index => $s): ?>
                tableRows += `<tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 15px; text-align: center; font-size: 10px;">#<?= $index + 1 ?></td>
                    <td style="padding: 15px; font-weight: 800; text-transform: uppercase; font-style: italic;"><?= htmlspecialchars($s['product_name']) ?></td>
                    <td style="padding: 15px; font-size: 11px;"><?= htmlspecialchars($s['supplier_name']) ?></td>
                    <td style="padding: 15px; text-align: center; font-weight: 800; font-size: 14px;"><?= $s['quantity'] ?></td>
                    <td style="padding: 15px; font-size: 11px; text-transform: uppercase;"><?= htmlspecialchars($s['branch_name']) ?></td>
                    <td style="padding: 15px; text-align: center; color: <?= $s['status'] === 'SHIPPED' ? '#d97706' : '#10b981' ?>; font-weight: 900; font-size: 9px; text-transform: uppercase;">
                        <?= $s['status'] ?>
                    </td>
                    <td style="padding: 15px; font-size: 9px; font-family: monospace;">
                        <?= $s['received_at'] ? date('d/M/Y H:i', strtotime($s['received_at'])) : '---' ?>
                    </td>
                </tr>`;
            <?php endforeach; ?>

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`<html><head><title>LOG_REPT_${Date.now()}</title>
                <style>
                    body { font-family: 'Plus Jakarta Sans', sans-serif; padding: 50px; color: #1e293b; }
                    .header { text-align: center; border-bottom: 4px solid #1C4D8D; padding-bottom: 30px; margin-bottom: 40px; }
                    .header h1 { margin: 0; text-transform: uppercase; font-style: italic; font-weight: 900; letter-spacing: -1px; color: #1C4D8D; }
                    .header p { margin: 10px 0; font-size: 10px; font-weight: 800; letter-spacing: 4px; color: #94a3b8; text-transform: uppercase; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th { background: #f8fafc; color: #1C4D8D; text-transform: uppercase; font-size: 9px; letter-spacing: 2px; padding: 20px 15px; border-bottom: 2px solid #e2e8f0; text-align: left; }
                    .footer { margin-top: 60px; border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; font-weight: 800; text-transform: uppercase; }
                </style>
            </head><body>
                <div class="header"><h1>Logistics Hub Ledger</h1><p>System Intelligence Report - Official Copy</p></div>
                <table><thead><tr><th>No.</th><th>Asset Identity</th><th>Supplier Node</th><th>Vol</th><th>Node Target</th><th>Protocol</th><th>Timestamp</th></tr></thead>
                <tbody>${tableRows}</tbody></table>
                <div class="footer"><div>© 2026 OMNI FLOW ERP SYSTEM</div><div>Authorized Digital Fingerprint: ${currentDateTime}</div></div>
            </body></html>`);
            printWindow.document.close();
            setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
        }
    </script>
</body>
</html>