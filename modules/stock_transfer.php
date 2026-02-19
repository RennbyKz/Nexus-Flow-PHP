<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
authorize(['ADMIN']);
$active_page = 'transfer';

if (isset($_POST['execute_transfer'])) {
    if (transferStock($pdo, $_POST['from_b'], $_POST['to_b'], $_POST['p_id'], $_POST['qty'])) {
        header("Location: stock_transfer.php?msg=success");
        exit();
    } else {
        $error = $_SESSION['transfer_error'] ?? "Node Handshake Failure.";
        unset($_SESSION['transfer_error']);
        header("Location: stock_transfer.php?msg=error&details=" . urlencode($error));
        exit();
    }
}

$branches = $pdo->query("SELECT * FROM branches WHERE status='ONLINE'")->fetchAll();
$products = $pdo->query("SELECT p.*, b.branch_name FROM products p JOIN branches b ON p.branch_id=b.branch_id ORDER BY p.product_name ASC")->fetchAll();
$history = $pdo->query("SELECT * FROM audit_logs WHERE action ILIKE '%transfer%' ORDER BY timestamp DESC LIMIT 5")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logistics Transfer | OMNI FLOW</title>
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

        <div class="mb-12 animate-reveal">
            <div class="space-y-2">
                <span class="text-[10px] font-black uppercase tracking-[0.5em] text-[#1C4D8D]/60 italic block">Redistribution Protocol</span>
                <h2 class="text-6xl font-black text-[#1C4D8D] tracking-tighter uppercase italic leading-none">Stock <span class="text-sky-500">Transfer</span></h2>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-12 flex-1">
            <div class="xl:col-span-2 space-y-10 animate-reveal" style="animation-delay: 0.1s;">
                <div class="executive-card p-12 relative overflow-hidden">
                    <i class="fas fa-route absolute -right-10 -bottom-10 text-[18rem] text-slate-50 italic font-black pointer-events-none"></i>
                    
                    <form method="POST" id="transferForm" class="space-y-10 relative z-10">
                        <div class="space-y-4">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-4 italic">1. Select Strategic Asset</label>
                            <div class="relative">
                                <select name="p_id" id="productSelect" onchange="autoMapSource()" required 
                                        class="w-full p-6 bg-slate-50 border-2 border-transparent rounded-[2.5rem] font-black text-slate-700 outline-none input-focus appearance-none cursor-pointer transition-all shadow-inner">
                                    <option value="">-- Choose Asset Node to Reallocate --</option>
                                    <?php foreach($products as $p): ?>
                                        <option value="<?= $p['product_id'] ?>" data-branch="<?= $p['branch_id'] ?>" data-qty="<?= $p['quantity'] ?>">
                                            <?= htmlspecialchars($p['product_name']) ?> (Available: <?= $p['quantity'] ?> units @ <?= $p['branch_name'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-8 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-rose-400 uppercase tracking-[0.3em] ml-4 italic">2. Source Node (Detected)</label>
                                <div class="relative">
                                    <select name="from_b" id="sourceBranch" required class="w-full p-6 bg-rose-50/50 border-2 border-rose-100 rounded-[2.5rem] font-black text-rose-700 outline-none pointer-events-none shadow-inner appearance-none">
                                        <?php foreach($branches as $b): ?>
                                            <option value="<?= $b['branch_id'] ?>"><?= $b['branch_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-lock absolute right-8 top-1/2 -translate-y-1/2 text-rose-200"></i>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-emerald-400 uppercase tracking-[0.3em] ml-4 italic">3. Target Destination</label>
                                <div class="relative">
                                    <select name="to_b" id="targetBranch" required class="w-full p-6 bg-emerald-50/50 border-2 border-emerald-100 rounded-[2.5rem] font-black text-emerald-700 outline-none input-focus appearance-none cursor-pointer shadow-inner">
                                        <option value="">-- Select Target Node --</option>
                                        <?php foreach($branches as $b): ?>
                                            <option value="<?= $b['branch_id'] ?>"><?= $b['branch_name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-location-dot absolute right-8 top-1/2 -translate-y-1/2 text-emerald-200"></i>
                                </div>
                            </div>
                        </div>

                        

                        <div class="space-y-4">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-4 italic">4. Movement Volume (Units)</label>
                            <div class="relative">
                                <input type="number" name="qty" id="transferQty" min="1" required placeholder="00"
                                       class="w-full p-10 bg-slate-50 border-none rounded-[3rem] font-black text-7xl text-[#1C4D8D] outline-none input-focus transition-all text-center tracking-tighter shadow-inner">
                                <span class="absolute right-12 top-1/2 -translate-y-1/2 font-black text-slate-200 text-xs uppercase tracking-widest">PCS</span>
                            </div>
                        </div>

                        <div class="pt-6">
                            <button type="submit" name="execute_transfer" class="w-full bg-slate-950 text-white p-10 rounded-[3rem] font-black uppercase tracking-[0.4em] hover:bg-[#1C4D8D] transition-all duration-700 shadow-2xl flex items-center justify-center gap-6 active:scale-95 group">
                                <i class="fas fa-truck-fast group-hover:translate-x-4 transition-transform duration-700 text-sky-400"></i>
                                <span>Execute Node Transfer</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-10 animate-reveal" style="animation-delay: 0.2s;">
                <div class="bg-[#1C4D8D] text-white p-10 rounded-[3.5rem] shadow-2xl relative overflow-hidden group">
                    <i class="fas fa-shield-halved absolute -right-4 -bottom-4 text-7xl text-white/10 italic"></i>
                    <h4 class="text-[10px] font-black uppercase tracking-[0.3em] mb-8 text-sky-300 italic">Transfer Guidelines</h4>
                    <ul class="space-y-6">
                        <li class="flex gap-4 items-start">
                            <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center shrink-0 text-[10px]"><i class="fas fa-check"></i></div>
                            <p class="text-xs font-bold text-white/80 leading-relaxed">Source node is auto-synced based on asset registry.</p>
                        </li>
                        <li class="flex gap-4 items-start">
                            <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center shrink-0 text-[10px]"><i class="fas fa-check"></i></div>
                            <p class="text-xs font-bold text-white/80 leading-relaxed">Volume check is enforced via RMI middleware.</p>
                        </li>
                        <li class="flex gap-4 items-start border-t border-white/10 pt-6">
                            <p class="text-[9px] font-black uppercase tracking-widest text-emerald-400 italic">Protocol: Transactional Integrity Guaranteed</p>
                        </li>
                    </ul>
                </div>

                <div class="bg-white p-10 rounded-[3.5rem] shadow-sm border border-slate-100 flex flex-col h-full">
                    <h4 class="text-[10px] font-black uppercase tracking-[0.3em] mb-8 text-slate-400 italic">Recent Node Movements</h4>
                    <div class="space-y-8 flex-1">
                        <?php foreach($history as $h): ?>
                            <div class="relative pl-8 border-l-2 border-sky-100 pb-2">
                                <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-white border-4 border-[#1C4D8D]"></div>
                                <p class="text-[10px] font-black text-[#1C4D8D] uppercase italic tracking-tighter mb-2"><?= htmlspecialchars($h['action']) ?></p>
                                <div class="flex items-center gap-2">
                                    <i class="far fa-clock text-[10px] text-slate-300"></i>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest"><?= date('d M Y | H:i', strtotime($h['timestamp'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($history)): ?>
                            <p class="text-center text-slate-300 italic font-bold text-xs py-10">No recent movement logs.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-auto pt-10"><?php include '../includes/footer.php'; ?></div>
    </main>

    <script>
        // SweetAlert Protocol Notifications
        const params = new URLSearchParams(window.location.search);
        if(params.get('msg') === 'success') {
            Swal.fire({ 
                icon: 'success', 
                title: '<span class="italic font-black uppercase tracking-tighter">TRANSFER SYNCED</span>', 
                text: 'Asset redistribution completed across nodes.', 
                confirmButtonColor: '#1C4D8D', 
                customClass: { popup: 'rounded-[3rem] p-10' } 
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if(params.get('msg') === 'error') {
            Swal.fire({ 
                icon: 'error', 
                title: '<span class="italic font-black uppercase tracking-tighter">TRANSFER REJECTED</span>', 
                text: decodeURIComponent(params.get('details')), 
                confirmButtonColor: '#ef4444', 
                customClass: { popup: 'rounded-[3rem] p-10' } 
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        function autoMapSource() {
            const productSelect = document.getElementById('productSelect');
            const sourceBranch = document.getElementById('sourceBranch');
            const targetBranch = document.getElementById('targetBranch');
            const qtyInput = document.getElementById('transferQty');
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            
            if(!selectedOption.value) return;

            const branchId = selectedOption.getAttribute('data-branch');
            const availableQty = selectedOption.getAttribute('data-qty');

            sourceBranch.value = branchId;
            qtyInput.max = availableQty; 
            qtyInput.placeholder = `Max: ${availableQty}`;
            
            // Disable source in destination options
            Array.from(targetBranch.options).forEach(option => {
                option.disabled = (option.value === branchId);
            });
        }
    </script>
</body>
</html>