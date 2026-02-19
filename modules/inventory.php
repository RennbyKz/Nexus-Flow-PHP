<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
$active_page = 'inventory';

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        // ၁။ မဖျက်ခင် ပစ္စည်းရဲ့ Details ကို အရင်ယူထားခြင်း
        $infoStmt = $pdo->prepare("
            SELECT p.product_name, b.branch_name, p.category 
            FROM products p 
            JOIN branches b ON p.branch_id = b.branch_id 
            WHERE p.product_id = ?
        ");
        $infoStmt->execute([$id]);
        $item = $infoStmt->fetch();

        if ($item) {
            // ၂။ Database မှာ Soft Delete (Archive) လုပ်ခြင်း
            $stmt = $pdo->prepare("UPDATE products SET is_deleted = TRUE WHERE product_id = ?");
            $stmt->execute([$id]);

            // ၃။ ⭐ Detailed Telegram Notification ပို့ခြင်း
            $msg = "⚠️ <b>ASSET ARCHIVED (SOFT DELETE)</b>\n"
                 . "--------------------------------\n"
                 . "📦 <b>Item:</b> " . $item['product_name'] . "\n"
                 . "🆔 <b>Node ID:</b> #" . $id . "\n"
                 . "📂 <b>Category:</b> " . ($item['category'] ?? 'N/A') . "\n"
                 . "📍 <b>Location:</b> " . $item['branch_name'] . "\n"
                 . "📉 <b>Status:</b> Moved to Archive Node\n"
                 . "👤 <b>Authorized By:</b> " . $_SESSION['username'] . "\n"
                 . "⏰ <b>Timestamp:</b> " . date('d-M-Y H:i A');
                 
            sendTelegramNotification($msg);
        }

        header("Location: inventory.php?msg=archived");
        exit();
    } catch (PDOException $e) { 
        $error = "Archive Process Failed: " . $e->getMessage(); 
    }
}

// --- ၂။ ADVANCED SEARCH LOGIC ---
$search = $_GET['search'] ?? '';
$branch_filter = $_GET['branch_filter'] ?? '';

$sql = "SELECT p.*, b.branch_name, s.name as supplier_name 
        FROM products p 
        JOIN branches b ON p.branch_id = b.branch_id 
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
        WHERE p.is_deleted = FALSE"; 
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.product_name ILIKE ? OR p.category ILIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}

if (!empty($branch_filter)) { $sql .= " AND p.branch_id = ?"; $params[] = $branch_filter; }

$sql .= " ORDER BY p.product_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$branches = $pdo->query("SELECT * FROM branches WHERE status = 'ONLINE'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Inventory | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f7fa; }
        .main-content { margin-left: 320px; transition: all 0.4s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
        .inventory-card { background: white; border-radius: 3rem; overflow: hidden; box-shadow: 0 20px 50px -15px rgba(28, 77, 141, 0.05); border: 1px solid rgba(28, 77, 141, 0.08); }
        .low-stock-breach { border: 2px solid #ef4444; animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        .animate-reveal { animation: reveal 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; }
        @keyframes reveal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="antialiased text-slate-900">

    <div class="no-print">
        <?php include '../templates/sidebar_admin.php'; ?>
    </div>

    <main class="main-content min-h-screen p-8 lg:p-12 flex flex-col">
        <div class="no-print sticky top-0 z-[999]"><?php include '../includes/header.php'; ?></div>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-8 animate-reveal no-print">
            <div class="space-y-2">
                <span class="text-[10px] font-black uppercase tracking-[0.5em] text-[#1C4D8D]/60 italic block">Resource Management Node</span>
                <h2 class="text-6xl font-black text-[#1C4D8D] tracking-tighter uppercase italic leading-none">Global <span class="text-sky-500">Inventory</span></h2>
            </div>
            <div class="flex flex-wrap gap-4 no-print">
                <a href="inventory_archive.php" class="bg-slate-800 text-white px-8 py-5 rounded-2xl font-black shadow-lg hover:bg-slate-900 transition-all text-[10px] uppercase tracking-widest flex items-center gap-3">
                    <i class="fas fa-box-archive text-amber-400"></i> View Archive
                </a>
                <button onclick="openAddModal()" class="bg-[#1C4D8D] text-white px-10 py-5 rounded-2xl font-black shadow-xl hover:bg-blue-800 transition-all text-[10px] uppercase tracking-widest flex items-center gap-3">
                    <i class="fas fa-plus-circle text-sky-300"></i> Register Asset
                </button>
            </div>
        </div>

        <div class="mb-10 no-print animate-reveal" style="animation-delay: 0.1s;">
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-wrap gap-4 items-center">
                <form action="" method="GET" class="flex flex-wrap gap-4 flex-1">
                    <div class="relative flex-1 min-w-[250px]">
                        <i class="fas fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="text" name="search" id="liveSearch" value="<?= htmlspecialchars($search) ?>" placeholder="Trace SKU or Description..." class="w-full pl-12 pr-4 py-5 bg-slate-50 border-none rounded-2xl font-bold text-sm outline-none shadow-inner">
                    </div>
                    <select name="branch_filter" class="p-5 bg-slate-50 border-none rounded-2xl font-black text-[10px] uppercase tracking-widest text-[#1C4D8D] outline-none cursor-pointer">
                        <option value="">All Operational Nodes</option>
                        <?php foreach($branches as $b): ?>
                            <option value="<?= $b['branch_id'] ?>" <?= $branch_filter == $b['branch_id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['branch_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-slate-900 text-white px-10 py-5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-[#1C4D8D] transition-all">Apply Filter</button>
                </form>
            </div>
        </div>

        <div class="inventory-card animate-reveal" style="animation-delay: 0.2s;">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[#1C4D8D] text-[10px] font-black uppercase tracking-[0.25em] border-b border-slate-100">
                        <tr>
                            <th class="p-10">Asset Specifications</th>
                            <th class="p-10 text-center">Batch Vol</th>
                            <th class="p-10 text-right">Valuation</th>
                            <th class="p-10 text-right no-print">Handshake</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach($products as $p): 
                            $meta = json_decode($p['metadata'] ?? '{}', true);
                            $photo = !empty($p['image_url']) ? $p['image_url'] : 'assets/uploads/products/default.png';
                        ?>
                        <tr class="hover:bg-sky-50/50 transition-all duration-300 inventory-row">
                            <td class="p-10">
                                <div class="flex items-center gap-6">
                                    <img src="../<?= $photo ?>" class="w-16 h-16 rounded-2xl object-cover shadow-md border border-slate-100">
                                    <div>
                                        <span class="text-xl font-black text-[#1C4D8D] block italic tracking-tighter uppercase leading-none mb-2"><?= htmlspecialchars($p['product_name']); ?></span>
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest border-l-2 border-sky-400 pl-2 ml-1"><?= htmlspecialchars($p['branch_name']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-10 text-center">
                                <span class="text-3xl font-black italic tracking-tighter text-slate-900"><?= $p['quantity']; ?></span>
                            </td>
                            <td class="p-10 text-right font-black text-[#1C4D8D] text-2xl italic tracking-tighter"><?= number_format($p['price']); ?> MMK</td>
                            
                            <td class="p-10 no-print">
                                <div class="flex justify-end items-center gap-3">
                                    <button onclick='openEditModal(<?= json_encode($p); ?>)' 
                                            class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center hover:bg-[#1C4D8D] hover:text-white transition-all active:scale-90 shadow-sm" title="Edit Parameters">
                                        <i class="fas fa-sliders-h"></i>
                                    </button>
                                    <button onclick="confirmDelete(<?= $p['product_id']; ?>, '<?= addslashes($p['product_name']); ?>')" 
                                            class="w-12 h-12 bg-rose-50 text-rose-400 rounded-2xl flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all active:scale-90 shadow-sm" title="Archive Asset">
                                        <i class="fas fa-box-archive"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($products)): ?>
                            <tr><td colspan="4" class="p-40 text-center text-slate-300 font-black italic uppercase tracking-[0.4em]">Inventory Nodes are currently offline</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="assetModal" class="hidden no-print fixed inset-0 bg-[#1C4D8D]/80 backdrop-blur-xl z-[9999] flex items-center justify-center p-6" onclick="handleBackdropClick(event)">
        <div class="bg-white rounded-[4rem] w-full max-w-3xl shadow-2xl overflow-hidden animate-reveal relative">
            <div class="bg-[#1C4D8D] p-12 text-white flex justify-between items-center">
                <div><h3 id="modalTitle" class="text-4xl font-black uppercase tracking-tighter italic">Asset Registry</h3><p class="text-[10px] font-black text-sky-300 uppercase tracking-[0.4em] mt-3">Node Data Synchronization</p></div>
                <button onclick="closeModal()" class="w-14 h-14 rounded-full bg-white/5 flex items-center justify-center hover:bg-rose-500 transition-all text-2xl group"><i class="fas fa-times group-hover:rotate-90 transition-transform"></i></button>
            </div>
            <form id="assetForm" action="save_product.php" method="POST" enctype="multipart/form-data" class="p-12 grid grid-cols-2 gap-8 max-h-[70vh] overflow-y-auto relative z-10">
                <input type="hidden" name="product_id" id="form_product_id">
                <input type="hidden" name="metadata" id="form_metadata_json">
                <div class="col-span-2 space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">Technical Descriptor</label>
                    <input type="text" name="product_name" id="form_product_name" required class="w-full p-6 bg-slate-50 border-none rounded-[2.5rem] font-bold outline-none shadow-inner text-lg focus:ring-8 focus:ring-[#1C4D8D]/5 transition-all">
                </div>
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">Terminal Node</label>
                    <select name="branch_id" id="form_branch_id" required class="w-full p-6 bg-slate-50 border-none rounded-[2.5rem] font-bold outline-none cursor-pointer">
                        <?php foreach($branches as $b): ?>
                            <option value="<?= $b['branch_id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">Category</label>
                    <input type="text" name="category" id="form_category" class="w-full p-6 bg-slate-50 border-none rounded-[2.5rem] font-bold outline-none">
                </div>
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">Manufacturer Brand</label>
                    <input type="text" id="brand_field" class="w-full p-6 bg-slate-50 border-none rounded-[2.5rem] font-bold outline-none">
                </div>
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">Batch Vol</label>
                    <input type="number" name="quantity" id="form_quantity" required class="w-full p-6 bg-slate-50 border-none rounded-[2.5rem] font-black text-slate-800 text-3xl outline-none text-center">
                </div>
                <div class="col-span-2 space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">Unit Market Valuation (MMK)</label>
                    <input type="number" name="price" id="form_price" required class="w-full p-8 bg-slate-50 border-none rounded-[3rem] font-black text-slate-800 text-5xl outline-none text-center shadow-inner tracking-tighter">
                </div>
                <button type="submit" onclick="syncMeta()" name="save_product" class="col-span-2 bg-slate-950 text-white p-10 rounded-[3rem] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all active:scale-95 group">
                    <i class="fas fa-sync group-hover:rotate-180 transition-transform duration-700 mr-2"></i> Synchronize Registry
                </button>
            </form>
        </div>
    </div>

    <script>
        // Modal & Data Logic
        function openAddModal() { document.getElementById('modalTitle').innerText = "Register Asset Node"; document.getElementById('assetForm').reset(); document.getElementById('form_product_id').value = ""; document.getElementById('assetModal').classList.remove('hidden'); }
        function openEditModal(d) {
            document.getElementById('modalTitle').innerText = "Modify Registry";
            document.getElementById('form_product_id').value = d.product_id;
            document.getElementById('form_product_name').value = d.product_name;
            document.getElementById('form_quantity').value = d.quantity;
            document.getElementById('form_price').value = d.price;
            document.getElementById('form_branch_id').value = d.branch_id;
            document.getElementById('form_category').value = d.category || '';
            const meta = JSON.parse(d.metadata || '{}');
            document.getElementById('brand_field').value = meta.brand || '';
            document.getElementById('assetModal').classList.remove('hidden');
        }
        function closeModal() { document.getElementById('assetModal').classList.add('hidden'); }
        function handleBackdropClick(e) { if(e.target.id === 'assetModal') closeModal(); }
        document.addEventListener('keydown', (e) => { if(e.key === 'Escape') closeModal(); });
        function syncMeta() { const meta = { brand: document.getElementById('brand_field').value, threshold: 5 }; document.getElementById('form_metadata_json').value = JSON.stringify(meta); }

        // Confirm Action
        function confirmDelete(id, name) {
            Swal.fire({
                title: '<span class="italic font-black uppercase tracking-tighter">ARCHIVE ASSET?</span>',
                text: `Security authorization required to move "${name}" to archive node.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#1C4D8D',
                confirmButtonText: 'YES, ARCHIVE',
                customClass: { popup: 'rounded-[3rem] p-10' }
            }).then((res) => { if (res.isConfirmed) window.location.href = `inventory.php?delete_id=${id}`; });
        }
    </script>
</body>
</html>