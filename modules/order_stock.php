<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
authorize(['ADMIN', 'MANAGER']);
$active_page = 'orders';

// --- ⭐ ၁။ CSV EXPORT LOGIC ---
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Pending_Orders_'.date('Y-m-d').'.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Product Name', 'Quantity', 'Branch', 'Price', 'Category', 'Status', 'Date']);
    $export_query = $pdo->query("
        SELECT r.request_id, r.product_name, r.quantity, b.branch_name, r.price, r.category, r.status, r.requested_at 
        FROM stock_requests r 
        JOIN branches b ON r.branch_id = b.branch_id 
        WHERE r.status = 'PENDING'
        ORDER BY r.requested_at DESC
    ");
    while ($row = $export_query->fetch(PDO::FETCH_ASSOC)) { fputcsv($output, $row); }
    fclose($output);
    exit();
}

// --- ၂။ ORDER PROCESSING LOGIC ---
if (isset($_POST['place_order'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $branch_id = $_POST['branch_id'];
    try {
        $p_stmt = $pdo->prepare("SELECT product_name, supplier_id, price, category FROM products WHERE product_id = ?");
        $p_stmt->execute([$product_id]);
        $product = $p_stmt->fetch();
        if ($product) {
            $sql = "INSERT INTO stock_requests (product_name, quantity, supplier_id, branch_id, price, category, status, requested_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())";
            $pdo->prepare($sql)->execute([
                $product['product_name'], $quantity, $product['supplier_id'], $branch_id, $product['price'], $product['category']
            ]);
            header("Location: order_stock.php?msg=success");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: order_stock.php?msg=error&details=" . urlencode($e->getMessage()));
        exit();
    }
}

$products = $pdo->query("SELECT product_id, product_name, price FROM products ORDER BY product_name ASC")->fetchAll();
$branches = $pdo->query("SELECT branch_id, branch_name FROM branches WHERE status='ONLINE'")->fetchAll();
$recent_orders = $pdo->query("
    SELECT r.*, b.branch_name 
    FROM stock_requests r 
    JOIN branches b ON r.branch_id = b.branch_id 
    WHERE r.status = 'PENDING'
    ORDER BY r.requested_at DESC LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Procurement | OMNI FLOW</title>
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
            box-shadow: 0 20px 50px -15px rgba(28, 77, 141, 0.05);
        }

        .animate-reveal { animation: reveal 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; }
        @keyframes reveal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .input-focus:focus { border-color: #A1E3F9; box-shadow: 0 0 0 8px rgba(161, 227, 249, 0.1); }
    </style>
</head>
<body class="antialiased text-slate-900">

    <?php include '../templates/sidebar_admin.php'; ?>

    <main class="main-content min-h-screen p-8 lg:p-12 relative flex flex-col">
        <?php include '../includes/header.php'; ?>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-8 animate-reveal">
            <div class="space-y-2">
                <span class="text-[10px] font-black uppercase tracking-[0.5em] text-[#1C4D8D]/60 italic block">Acquisition Protocol</span>
                <h2 class="text-6xl font-black text-[#1C4D8D] tracking-tighter uppercase italic leading-none">Stock <span class="text-sky-500">Procurement</span></h2>
            </div>
            <a href="?export=1" class="bg-white text-[#1C4D8D] px-8 py-5 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-sm border border-slate-200 hover:bg-slate-50 transition flex items-center gap-3">
                <i class="fas fa-file-csv text-emerald-500"></i> Export Ledger
            </a>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-12 flex-1">
            <div class="xl:col-span-2 space-y-10 animate-reveal" style="animation-delay: 0.1s;">
                <div class="executive-card p-12 relative overflow-hidden">
                    <i class="fas fa-satellite-dish absolute -right-10 -bottom-10 text-[18rem] text-slate-50 italic font-black pointer-events-none"></i>
                    
                    <form action="" method="POST" class="space-y-10 relative z-10">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-4 italic">1. Select Target Asset</label>
                                <div class="relative">
                                    <select name="product_id" id="p_select" required 
                                            class="w-full p-6 bg-slate-50 border-2 border-transparent rounded-[2.5rem] font-black text-slate-700 outline-none input-focus appearance-none cursor-pointer transition-all shadow-inner">
                                        <option value="">-- Choose Arrival SKU --</option>
                                        <?php foreach($products as $p): ?>
                                            <option value="<?= $p['product_id'] ?>" data-price="<?= $p['price'] ?>"><?= htmlspecialchars($p['product_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-8 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-4 italic">2. Acquisition Volume</label>
                                <div class="relative">
                                    <input type="number" name="quantity" min="1" required placeholder="00"
                                           class="w-full p-6 bg-slate-50 border-none rounded-[2.5rem] font-black text-slate-900 text-3xl outline-none input-focus transition-all text-center shadow-inner tracking-tighter">
                                    <span class="absolute right-8 top-1/2 -translate-y-1/2 font-black text-slate-200 text-[10px] uppercase">Units</span>
                                </div>
                            </div>
                        </div>

                        

                        <div class="space-y-4">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-4 italic">3. Destination Deployment Node</label>
                            <div class="relative">
                                <select name="branch_id" required 
                                        class="w-full p-6 bg-slate-50 border-2 border-transparent rounded-[2.5rem] font-black text-[#1C4D8D] outline-none input-focus appearance-none cursor-pointer shadow-inner">
                                    <?php foreach($branches as $b): ?>
                                        <option value="<?= $b['branch_id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-location-crosshairs absolute right-8 top-1/2 -translate-y-1/2 text-sky-200"></i>
                            </div>
                        </div>

                        <div class="pt-6">
                            <button type="submit" name="place_order" class="w-full bg-slate-950 text-white p-10 rounded-[3rem] font-black uppercase tracking-[0.4em] shadow-2xl hover:bg-[#1C4D8D] transition-all duration-700 hover:-translate-y-2 flex items-center justify-center gap-6 group active:scale-95">
                                <i class="fas fa-paper-plane group-hover:translate-x-6 group-hover:-translate-y-6 transition-transform duration-700 text-sky-400"></i>
                                <span>Transmit Purchase Order</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-10 animate-reveal" style="animation-delay: 0.2s;">
                <div id="priceCard" class="bg-[#1C4D8D] text-white p-10 rounded-[3.5rem] shadow-2xl relative overflow-hidden hidden group">
                    <i class="fas fa-money-bill-trend-up absolute -right-4 -bottom-4 text-7xl text-white/10 italic"></i>
                    <span class="text-[9px] font-black uppercase tracking-[0.3em] text-sky-300 italic block mb-6">Unit Market Valuation</span>
                    <h4 class="text-5xl font-black italic tracking-tighter leading-none"><span id="unitPrice">0</span></h4>
                    <p class="text-[9px] font-black text-white/40 mt-6 uppercase tracking-widest">Consolidated MMK / Unit</p>
                </div>

                <div class="bg-white p-10 rounded-[3.5rem] shadow-sm border border-slate-100 flex flex-col h-full">
                    <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 italic mb-8 flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Latest Transmissions
                    </h4>
                    <div class="space-y-8 flex-1">
                        <?php foreach($recent_orders as $ro): ?>
                            <div class="relative pl-8 border-l-2 border-sky-100 pb-2 group/item">
                                <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-white border-4 border-[#1C4D8D] group-hover/item:scale-125 transition-transform"></div>
                                <p class="text-[11px] font-black text-[#1C4D8D] uppercase italic tracking-tighter mb-2"><?= htmlspecialchars($ro['product_name']) ?></p>
                                <div class="flex items-center justify-between">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase"><?= $ro['quantity'] ?> Units • <?= htmlspecialchars($ro['branch_name']) ?></span>
                                    <span class="bg-amber-50 text-amber-600 px-3 py-1 rounded-lg text-[7px] font-black uppercase border border-amber-100"><?= htmlspecialchars($ro['status']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($recent_orders)): ?>
                            <p class="text-center text-slate-300 italic font-bold text-xs py-10">No pending signals.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-auto pt-10"><?php include '../includes/footer.php'; ?></div>
    </main>

    <script>
        // Real-time Price Oracle
        document.getElementById('p_select').addEventListener('change', function() {
            const price = this.options[this.selectedIndex].getAttribute('data-price');
            const card = document.getElementById('priceCard');
            const priceDisplay = document.getElementById('unitPrice');
            if(price) { 
                card.classList.remove('hidden'); 
                priceDisplay.innerText = new Intl.NumberFormat().format(price); 
            } else { 
                card.classList.add('hidden'); 
            }
        });

        // Protocol Notifications
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'success') {
            Swal.fire({ 
                icon: 'success', 
                title: '<span class="italic font-black uppercase tracking-tighter">TRANSMITTED</span>', 
                text: 'Purchase Order successfully synced to supplier node.', 
                confirmButtonColor: '#1C4D8D', 
                customClass: { popup: 'rounded-[3rem] p-10' } 
            });
        }
        window.history.replaceState({}, document.title, window.location.pathname);
    </script>
</body>
</html>