<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
if ($_SESSION['role'] !== 'SUPPLIER') { header("Location: ../index.php"); exit(); }

$my_supplier_id = $_SESSION['supplier_id'];
$active_page = 'orders';

// --- ⭐ ၁။ DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $rid = $_GET['delete_id'];
    
    // မိမိပိုင်ဆိုင်သော အော်ဒါဖြစ်မှသာ ဖျက်ခွင့်ပေးရန် သေချာအောင် စစ်ဆေးခြင်း
    $stmt = $pdo->prepare("DELETE FROM stock_requests WHERE request_id = ? AND supplier_id = ?");
    $stmt->execute([$rid, $my_supplier_id]);
    
    header("Location: supplier_orders.php?msg=deleted");
    exit();
}

// အော်ဒါအားလုံးကို ဆွဲယူခြင်း
$sql = "SELECT r.*, b.branch_name 
        FROM stock_requests r 
        JOIN branches b ON r.branch_id = b.branch_id
        WHERE r.supplier_id = ? 
        ORDER BY r.requested_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$my_supplier_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supply Ledger | SupplyNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .animate-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; width: 100%; }
            .report-card { border: none !important; box-shadow: none !important; }
            table { width: 100%; border-collapse: collapse; font-size: 10px; }
            th, td { border-bottom: 1px solid #eee !important; padding: 12px !important; }
            .print-header { display: block !important; margin-bottom: 30px; border-bottom: 2px solid #10b981; }
        }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-800 antialiased">

    <div class="no-print">
        <?php include '../templates/sidebar_supplier.php'; ?>
    </div>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[280px] transition-all duration-300 main-content">
        
        <div class="no-print">
            <?php include '../includes/header.php'; ?>
        </div>

        <div class="hidden print-header pb-6">
            <h1 class="text-2xl font-black uppercase italic text-slate-900">SupplyNode | Procurement Invoice Report</h1>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Generated: <?= date('d M Y, h:i A') ?></p>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-6 no-print">
            <div class="animate-in">
                <h2 class="text-4xl font-black text-slate-900 tracking-tighter uppercase italic leading-none">Fulfillment Ledger</h2>
                <p class="text-emerald-600 text-[10px] font-black uppercase tracking-[0.3em] mt-3 bg-emerald-50 px-3 py-1 rounded-full inline-block">Review and Archive Transactions</p>
            </div>
            <button onclick="window.print()" class="bg-white border border-slate-200 text-slate-900 px-8 py-4 rounded-[2rem] font-black shadow-sm hover:bg-slate-50 transition tracking-widest uppercase text-[10px] flex items-center gap-2">
                <i class="fas fa-file-pdf text-rose-500"></i> Export Statement
            </button>
        </div>

        <div class="bg-white rounded-[3.5rem] shadow-sm border border-slate-100 overflow-hidden animate-in report-card" style="animation-delay: 0.1s;">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="p-8">Timeline</th>
                        <th class="p-8">Asset & Destination</th>
                        <th class="p-8 text-center">Volume</th>
                        <th class="p-8">Status</th>
                        <th class="p-8 text-right no-print">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if(empty($orders)): ?>
                        <tr><td colspan="5" class="p-24 text-center text-slate-300 font-bold italic tracking-widest uppercase text-xs">No records found</td></tr>
                    <?php endif; ?>

                    <?php foreach($orders as $o): ?>
                    <tr class="hover:bg-slate-50/50 transition-all group">
                        <td class="p-8">
                            <span class="font-bold text-slate-700 text-xs block leading-none mb-1"><?= date('M d, Y', strtotime($o['requested_at'])) ?></span>
                            <span class="text-[9px] text-slate-400 font-black uppercase tracking-tighter italic"><?= date('h:i A', strtotime($o['requested_at'])) ?></span>
                        </td>
                        <td class="p-8">
                            <div>
                                <span class="text-lg font-black text-slate-800 block italic uppercase tracking-tighter"><?= htmlspecialchars($o['product_name']) ?></span>
                                <span class="text-[9px] font-extrabold text-emerald-500 uppercase tracking-widest"><?= htmlspecialchars($o['branch_name']) ?></span>
                            </div>
                        </td>
                        <td class="p-8 text-center">
                            <span class="text-2xl font-black text-slate-900 italic"><?= $o['quantity'] ?></span>
                        </td>
                        <td class="p-8">
                            <span class="px-4 py-2 rounded-[1.2rem] text-[9px] font-black uppercase tracking-widest border
                                <?= $o['status'] === 'PENDING' ? 'bg-amber-50 text-amber-500 border-amber-100' : 
                                   ($o['status'] === 'SHIPPED' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-indigo-50 text-indigo-600 border-indigo-100') ?>">
                                <?= $o['status'] ?>
                            </span>
                        </td>
                        <td class="p-8 text-right space-x-2 no-print">
                            <?php if($o['status'] === 'PENDING'): ?>
                                <a href="update_order_status.php?id=<?= $o['request_id'] ?>&status=SHIPPED" 
                                   class="p-3 bg-slate-900 text-white rounded-xl hover:bg-emerald-500 transition-all inline-block shadow-lg">
                                    <i class="fas fa-truck-loading"></i>
                                </a>
                            <?php endif; ?>
                            
                            <button onclick="confirmDelete(<?= $o['request_id']; ?>, '<?= addslashes($o['product_name']); ?>')" 
                                    class="p-3 bg-rose-50 text-rose-300 rounded-xl hover:bg-rose-500 hover:text-white transition-all shadow-sm">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php include '../includes/footer.php'; ?>
    </main>

    <script>
        // ⭐ Delete Confirmation
        function confirmDelete(id, name) {
            Swal.fire({
                title: 'TERMINATE RECORD?',
                text: `Are you sure you want to remove "${name}" from your ledger?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#0f172a',
                confirmButtonText: 'Yes, Delete',
                customClass: { popup: 'rounded-[3rem]' }
            }).then((result) => {
                if (result.isConfirmed) window.location.href = `supplier_orders.php?delete_id=${id}`;
            });
        }

        // Success Alerts
        const params = new URLSearchParams(window.location.search);
        if(params.get('msg') === 'deleted') {
            Swal.fire({ icon: 'success', title: 'DELETED', text: 'Transaction has been archived.', confirmButtonColor: '#0f172a', customClass: { popup: 'rounded-[3rem]' } });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>