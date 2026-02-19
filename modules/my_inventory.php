<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['STOREKEEPER']); 
$active_page = 'my_inventory';

$my_branch = $_SESSION['branch_id'] ?? null;
$success_flag = false;
$error_msg = "";

// --- ⭐ STOCK UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $pid = $_POST['product_id'];
    $new_qty = $_POST['quantity'];
    $pname = $_POST['product_name'];

    try {
        $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE product_id = ? AND branch_id = ?");
        $stmt->execute([$new_qty, $pid, $my_branch]);
        
        logActivity($pdo, "Inventory Sync: Adjusted @$pname to $new_qty units");
        $success_flag = true;
    } catch (Exception $e) {
        $error_msg = "Sync Failed: " . $e->getMessage();
    }
}

// Data Fetching
$products = $pdo->prepare("SELECT * FROM products WHERE branch_id = ? ORDER BY product_name ASC");
$products->execute([$my_branch]);
$items = $products->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Repository | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .animate-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media print { .no-print { display: none !important; } .main-content { margin-left: 0 !important; } }
        .swal2-popup { border-radius: 2rem !important; font-family: sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased overflow-x-hidden">

    <?php include_once '../templates/sidebar_storekeeper.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300 main-content">
        
        <div class="no-print"><?php include_once '../includes/header.php'; ?></div>

        <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 animate-in">
            <div>
                <h2 class="text-4xl font-black text-slate-950 tracking-tighter uppercase italic leading-none">Stock Repository</h2>
                <p class="text-blue-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-blue-50 px-3 py-1 rounded-full inline-block">Local Asset Management Node</p>
            </div>
            
            <div class="flex gap-3 no-print">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="text" id="stockSearch" placeholder="Find asset SKU..." 
                           class="pl-10 pr-6 py-4 bg-white border border-slate-100 rounded-2xl outline-none focus:ring-4 focus:ring-blue-500/10 font-bold text-xs w-64 transition-all">
                </div>
                <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition shadow-2xl active:scale-95">
                    <i class="fas fa-file-pdf mr-2 text-rose-400"></i> Export Audit
                </button>
            </div>
        </div>

        <div class="bg-white rounded-[3.5rem] shadow-sm border border-slate-100 overflow-hidden animate-in" style="animation-delay: 0.1s;">
            <table class="w-full text-left border-collapse" id="stockTable">
                <thead class="bg-slate-50 text-[9px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="p-8">Asset Categorization</th>
                        <th class="p-8 text-center">Volume on Hand</th>
                        <th class="p-8 text-right">Node Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($items as $item): 
                        $meta = is_string($item['metadata']) ? json_decode($item['metadata'], true) : $item['metadata'];
                        $threshold = $meta['threshold'] ?? 5;
                        $is_low = $item['quantity'] < $threshold;
                    ?>
                    <tr class="hover:bg-slate-50/80 transition group">
                        <td class="p-8">
                            <div class="flex items-center gap-6">
                                <div class="w-14 h-14 bg-slate-50 text-slate-300 rounded-2xl flex items-center justify-center text-xl group-hover:bg-blue-600 group-hover:text-white transition-all shadow-inner">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <div>
                                    <span class="font-black text-slate-800 block text-lg italic uppercase tracking-tighter leading-none mb-1"><?= htmlspecialchars($item['product_name']) ?></span>
                                    <span class="text-[9px] font-black uppercase tracking-widest <?= $is_low ? 'text-rose-500 animate-pulse' : 'text-slate-400' ?>">
                                        <?= $is_low ? '<i class="fas fa-warning"></i> Breach: Below Threshold' : 'Status: Optimal Volume' ?>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="p-8 text-center">
                            <span class="text-3xl font-black text-slate-900 italic <?= $is_low ? 'text-rose-600' : '' ?>">
                                <?= $item['quantity'] ?>
                            </span>
                            <span class="text-[8px] font-black text-slate-300 block uppercase tracking-widest mt-1">Confirmed Units</span>
                        </td>
                        <td class="p-8 text-right no-print">
                            <button onclick="openUpdateModal(<?= $item['product_id'] ?>, '<?= addslashes($item['product_name']) ?>', <?= $item['quantity'] ?>)" 
                                    class="bg-slate-900 text-white px-8 py-4 rounded-2xl text-[9px] font-black uppercase tracking-widest hover:bg-emerald-500 transition-all shadow-xl active:scale-95">
                                Adjust Volume
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php include_once '../includes/footer.php'; ?>
    </main>

    <div id="updateModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[5000] flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-[3.5rem] w-full max-w-md p-10 shadow-2xl border border-white/20 animate-in">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-sliders-h text-3xl"></i>
                </div>
                <h3 class="text-2xl font-black uppercase italic tracking-tighter text-slate-950">Volume Adjustment</h3>
                <p id="modalProductName" class="text-blue-500 text-[10px] font-black uppercase tracking-widest mt-2 bg-blue-50 px-4 py-1 rounded-full inline-block"></p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="product_id" id="modal_pid">
                <input type="hidden" name="product_name" id="modal_pname">
                
                <div class="mb-10">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-4 ml-2 text-center">New Confirmable Quantity</label>
                    <input type="number" name="quantity" id="modal_qty" required
                           class="w-full p-8 bg-slate-50 border-none rounded-[2rem] font-black text-5xl text-slate-900 focus:ring-4 focus:ring-blue-500/10 transition outline-none shadow-inner text-center">
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit" name="update_stock" class="w-full bg-slate-950 text-white py-6 rounded-[2rem] font-black uppercase tracking-[0.2em] text-[10px] hover:bg-emerald-500 transition-all shadow-2xl">Authorize Change</button>
                    <button type="button" onclick="closeModal()" class="w-full py-2 text-slate-400 font-bold uppercase text-[10px] tracking-widest">Abort Process</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ⭐ Search Filter Logic
        document.getElementById('stockSearch').addEventListener('keyup', function() {
            let val = this.value.toUpperCase();
            let rows = document.querySelector("#stockTable tbody").rows;
            for (let row of rows) {
                let text = row.cells[0].textContent.toUpperCase();
                row.style.display = text.includes(val) ? "" : "none";
            }
        });

        function openUpdateModal(id, name, qty) {
            document.getElementById('modal_pid').value = id;
            document.getElementById('modal_pname').value = name;
            document.getElementById('modal_qty').value = qty;
            document.getElementById('modalProductName').innerText = name;
            document.getElementById('updateModal').classList.remove('hidden');
        }
        function closeModal() { document.getElementById('updateModal').classList.add('hidden'); }

        // ⭐ Alerts
        <?php if($success_flag): ?>
            Swal.fire({ icon: 'success', title: 'NODE SYNCED', text: 'Inventory volume successfully authorized.', confirmButtonColor: '#10b981' });
        <?php endif; ?>
        <?php if($error_msg): ?>
            Swal.fire({ icon: 'error', title: 'SYNC FAILURE', text: '<?= $error_msg ?>', confirmButtonColor: '#ef4444' });
        <?php endif; ?>
    </script>
</body>
</html>