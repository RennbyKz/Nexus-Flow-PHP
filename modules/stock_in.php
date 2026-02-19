<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['STOREKEEPER']); 
$active_page = 'stock_in';
$my_branch = $_SESSION['branch_id'];
$success_flag = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_inbound'])) {
    $pid = $_POST['product_id'];
    $in_qty = $_POST['quantity'];
    $pname = $_POST['product_name'];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE product_id = ? AND branch_id = ?");
        $stmt->execute([$in_qty, $pid, $my_branch]);
        logActivity($pdo, "Inbound: Received $in_qty units for @$pname");
        $pdo->commit();
        $success_flag = true;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Inbound Sync Error: " . $e->getMessage());
    }
}

$products = $pdo->prepare("SELECT * FROM products WHERE branch_id = ? ORDER BY product_name ASC");
$products->execute([$my_branch]);
$items = $products->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbound Register | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased">
    <?php include_once '../templates/sidebar_storekeeper.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300 min-h-screen flex flex-col">
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-10 text-center lg:text-left">
            <h2 class="text-4xl font-black text-slate-950 tracking-tighter uppercase italic leading-none text-center">Inbound Register</h2>
            <p class="text-emerald-500 text-[10px] font-black uppercase tracking-[0.4em] mt-3 text-center">Recording Local Asset Acquisition</p>
        </div>

        <div class="flex-1 flex items-center justify-center pb-20">
            <div class="w-full max-w-xl bg-white p-12 rounded-[4rem] shadow-sm border border-slate-100">
                <form method="POST" class="space-y-8">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-4 ml-2">Select Asset Node</label>
                        <select name="product_id" id="prodSelect" required class="w-full p-6 bg-slate-50 border-none rounded-3xl font-black text-slate-700 outline-none focus:ring-4 focus:ring-blue-500/10 appearance-none shadow-inner">
                            <option value="">-- Choose Arrival Asset --</option>
                            <?php foreach($items as $i): ?>
                                <option value="<?= $i['product_id'] ?>" data-name="<?= htmlspecialchars($i['product_name']) ?>"><?= htmlspecialchars($i['product_name']) ?> (Current: <?= $i['quantity'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="product_name" id="prodName">
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-4 ml-2">Incoming Volume (PCS)</label>
                        <input type="number" name="quantity" required min="1" placeholder="00" class="w-full p-8 bg-slate-50 border-none rounded-[2.5rem] font-black text-5xl text-blue-600 focus:ring-4 focus:ring-blue-500/10 transition outline-none shadow-inner text-center">
                    </div>

                    <button type="submit" name="register_inbound" class="w-full bg-slate-950 text-white p-8 rounded-[2.5rem] font-black uppercase tracking-[0.2em] hover:bg-emerald-500 transition-all shadow-2xl active:scale-95">
                        Authorize Entry
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('prodSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('prodName').value = selectedOption.getAttribute('data-name');
        });
        <?php if($success_flag): ?>
            Swal.fire({ icon: 'success', title: 'INBOUND SYNCED', text: 'Stock recorded in local node.', confirmButtonColor: '#10b981' });
        <?php endif; ?>
    </script>
</body>
</html>