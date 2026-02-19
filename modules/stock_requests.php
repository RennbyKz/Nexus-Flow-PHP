<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['MANAGER']); 
$active_page = 'stock_requests';
$my_branch = $_SESSION['branch_id'];
$success_flag = false;
$error_msg = "";

// --- ⭐ ၁။ DELETE LOGIC (Single & Global) ---
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM stock_requests WHERE request_id = ? AND branch_id = ?")->execute([$_GET['delete_id'], $my_branch]);
    header("Location: stock_requests.php?msg=deleted"); exit();
}

if (isset($_POST['wipe_history'])) {
    $pdo->prepare("DELETE FROM stock_requests WHERE branch_id = ?")->execute([$my_branch]);
    header("Location: stock_requests.php?msg=wiped"); exit();
}

// ၂။ ပစ္စည်းအသစ်တောင်းခံခြင်း Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $supplier_id = $_POST['supplier_id'] ?? null;
    $product_name = $_POST['product_name'] ?? null;
    $request_qty = $_POST['quantity'] ?? 0;
    $remark = $_POST['remark'] ?? ''; 

    if ($supplier_id && $product_name && $request_qty > 0) {
        try {
            $sql = "INSERT INTO stock_requests (supplier_id, product_name, quantity, branch_id, remark, status) 
                    VALUES (?, ?, ?, ?, ?, 'PENDING')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$supplier_id, $product_name, $request_qty, $my_branch, $remark]);
            $success_flag = true;
        } catch (PDOException $e) { $error_msg = "Database Error: " . $e->getMessage(); }
    }
}

// ၃။ Initial Data Fetching
$suppliers = $pdo->query("SELECT supplier_id, name FROM suppliers ORDER BY name ASC")->fetchAll();

// ၄။ Filter Logic
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

$query = "SELECT r.*, s.name as supplier_name FROM stock_requests r 
          LEFT JOIN suppliers s ON r.supplier_id = s.supplier_id 
          WHERE r.branch_id = ?";
$params = [$my_branch];

if ($search) { $query .= " AND r.product_name ILIKE ?"; $params[] = "%$search%"; }
if ($status_filter) { $query .= " AND r.status = ?"; $params[] = $status_filter; }

$query .= " ORDER BY r.requested_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Procurement Ledger | OMNI INVENTORY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @media print { .no-print { display: none !important; } .main-content { margin-left: 0 !important; } }
        .swal2-popup { border-radius: 2.5rem !important; font-family: sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased">

    <?php include_once '../templates/sidebar_manager.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300 main-content">
        
        <div class="no-print"><?php include_once '../includes/header.php'; ?></div>

        <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div>
                <h2 class="text-4xl font-black text-slate-950 tracking-tighter uppercase italic leading-none">Procurement Node</h2>
                <p class="text-violet-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-violet-50 px-3 py-1 rounded-full inline-block">Strategic Replenishment Ledger</p>
            </div>
            <div class="flex gap-3 no-print">
                <button onclick="confirmWipe()" class="bg-rose-50 text-rose-500 px-6 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-rose-500 hover:text-white transition shadow-sm border border-rose-100">
                    <i class="fas fa-trash-sweep mr-2"></i> Wipe History
                </button>
                <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-violet-600 transition shadow-2xl active:scale-95">
                    <i class="fas fa-file-pdf mr-2 text-rose-400"></i> Export PDF
                </button>
            </div>
        </div>

        <div class="grid lg:grid-cols-4 gap-8">
            <div class="lg:col-span-1 no-print">
                <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 sticky top-10">
                    <h3 class="text-xl font-black uppercase italic mb-6 tracking-tighter text-slate-800 border-b pb-4">New Request</h3>
                    <form method="POST" class="space-y-5">
                        <select name="supplier_id" id="supplierSelect" required class="w-full p-4 bg-slate-50 border-none rounded-xl font-bold text-xs outline-none focus:ring-2 focus:ring-violet-500/20 shadow-inner appearance-none cursor-pointer">
                            <option value="">-- Choose Supplier --</option>
                            <?php foreach($suppliers as $s): ?>
                                <option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="product_name" id="productSelect" required class="w-full p-4 bg-slate-50 border-none rounded-xl font-bold text-xs outline-none focus:ring-2 focus:ring-violet-500/20 shadow-inner appearance-none">
                            <option value="">-- First Select Supplier --</option>
                        </select>
                        <input type="number" name="quantity" required min="1" placeholder="Enter Units" class="w-full p-4 bg-slate-50 border-none rounded-xl font-black outline-none shadow-inner text-sm">
                        <textarea name="remark" rows="2" class="w-full p-4 bg-slate-50 border-none rounded-xl font-bold text-xs outline-none shadow-inner" placeholder="Optional notes..."></textarea>
                        <button type="submit" name="submit_request" class="w-full bg-slate-950 text-white p-6 rounded-[2rem] font-black uppercase tracking-widest text-[10px] hover:bg-violet-600 transition-all shadow-xl active:scale-95">Transmit Request</button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-3 space-y-6">
                <div class="bg-white p-5 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-wrap gap-3 items-center no-print">
                    <form action="" method="GET" class="flex flex-wrap gap-3 flex-1">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search product..." class="flex-1 p-3 bg-slate-50 border-none rounded-xl font-bold text-[10px] outline-none">
                        <select name="status_filter" class="p-3 bg-slate-50 border-none rounded-xl font-black text-[10px] uppercase text-slate-400">
                            <option value="">All Status</option>
                            <option value="PENDING" <?= $status_filter == 'PENDING' ? 'selected' : '' ?>>Pending</option>
                            <option value="SHIPPED" <?= $status_filter == 'SHIPPED' ? 'selected' : '' ?>>Shipped</option>
                        </select>
                        <button type="submit" class="bg-slate-900 text-white px-8 py-3 rounded-xl font-black uppercase text-[10px] tracking-widest hover:bg-violet-600">Filter</button>
                        <?php if($search || $status_filter): ?>
                            <a href="stock_requests.php" class="p-3 bg-rose-50 text-rose-500 rounded-xl font-black text-[10px] uppercase">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="bg-white rounded-[4rem] shadow-sm border border-slate-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-[9px] font-black uppercase text-slate-400 tracking-widest border-b">
                            <tr>
                                <th class="p-8">Timeline</th>
                                <th class="p-8">Asset & Partner</th>
                                <th class="p-8 text-center">Volume</th>
                                <th class="p-8 text-right">Status</th>
                                <th class="p-8 text-right no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach($requests as $r): ?>
                            <tr class="hover:bg-slate-50/50 transition-all group">
                                <td class="p-8">
                                    <span class="font-bold text-slate-700 text-xs block mb-1"><?= date('d M, Y', strtotime($r['requested_at'])) ?></span>
                                    <span class="text-[9px] text-slate-400 font-black italic"><?= date('h:i A', strtotime($r['requested_at'])) ?></span>
                                </td>
                                <td class="p-8">
                                    <span class="font-black text-slate-800 block text-lg italic uppercase tracking-tighter"><?= htmlspecialchars($r['product_name']) ?></span>
                                    <span class="text-[9px] font-bold text-violet-500 uppercase mt-2 inline-block">Partner: <?= htmlspecialchars($r['supplier_name'] ?? 'Direct Hub') ?></span>
                                </td>
                                <td class="p-8 text-center font-black text-2xl text-slate-900 italic"><?= $r['quantity'] ?></td>
                                <td class="p-8 text-right">
                                    <span class="px-5 py-2 rounded-full font-black uppercase text-[8px] tracking-[0.2em] border 
                                        <?= $r['status'] == 'PENDING' ? 'bg-amber-50 text-amber-500 border-amber-100' : 
                                           ($r['status'] == 'SHIPPED' ? 'bg-violet-50 text-violet-600 border-violet-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'); 
                                        ?>">
                                        <?= $r['status'] ?>
                                    </span>
                                </td>
                                <td class="p-8 text-right no-print">
                                    <button onclick="confirmDelete(<?= $r['request_id'] ?>, '<?= addslashes($r['product_name']) ?>')" class="text-rose-300 hover:text-rose-600 transition">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include_once '../includes/footer.php'; ?>
    </main>

    <form id="wipeForm" method="POST" style="display:none;"><input type="hidden" name="wipe_history" value="1"></form>

    <script>
        // Supplier Product Logic (Fetch API) - ယခင်အတိုင်း
        document.getElementById('supplierSelect').addEventListener('change', function() {
            const supplierId = this.value;
            const productSelect = document.getElementById('productSelect');
            productSelect.innerHTML = '<option value="">-- Loading... --</option>';
            if (!supplierId) return;
            fetch(`get_supplier_products.php?supplier_id=${supplierId}`)
                .then(r => r.json()).then(data => {
                    productSelect.innerHTML = '<option value="">-- Select Asset --</option>';
                    data.forEach(p => {
                        let opt = document.createElement('option'); opt.value = p.product_name; opt.textContent = p.product_name;
                        productSelect.appendChild(opt);
                    });
                });
        });

        // ⭐ SWEETALERT LOGIC
        function confirmDelete(id, name) {
            Swal.fire({
                title: 'DELETE RECORD?',
                text: `Remove procurement trace for ${name}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Delete'
            }).then((res) => { if (res.isConfirmed) window.location.href = `stock_requests.php?delete_id=${id}`; });
        }

        function confirmWipe() {
            Swal.fire({
                title: 'WIPE ALL HISTORY?',
                text: "This will permanently erase all procurement logs for this branch!",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Wipe All'
            }).then((res) => { if (res.isConfirmed) document.getElementById('wipeForm').submit(); });
        }

        // Success Notifications
        const params = new URLSearchParams(window.location.search);
        if(params.has('msg')) {
            const m = params.get('msg');
            Swal.fire({ 
                icon: 'success', 
                title: m === 'deleted' ? 'RECORD REMOVED' : (m === 'wiped' ? 'HISTORY WIPE' : 'SYNC COMPLETED'),
                confirmButtonColor: '#7c3aed' 
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        <?php if($success_flag): ?>
            Swal.fire({ icon: 'success', title: 'SYNC COMPLETED', text: 'Request transmitted.', confirmButtonColor: '#7c3aed' })
            .then(() => { window.location.href = 'stock_requests.php'; });
        <?php endif; ?>
    </script>
</body>
</html>