<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
authorize(['STOREKEEPER']); 
$active_page = 'stock_out';
$my_branch = $_SESSION['branch_id'];
$success_flag = false;
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_outbound'])) {
    $pid = $_POST['product_id'];
    $out_qty = $_POST['quantity'];
    $pname = $_POST['product_name'];

    try {
        $check = $pdo->prepare("SELECT quantity FROM products WHERE product_id = ? AND branch_id = ?");
        $check->execute([$pid, $my_branch]);
        $current = $check->fetchColumn();

        if ($current >= $out_qty) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ? AND branch_id = ?");
            $stmt->execute([$out_qty, $pid, $my_branch]);
            logActivity($pdo, "Outbound: Dispatched $out_qty units of @$pname");
            $pdo->commit();
            $success_flag = true;
        } else {
            $error_msg = "Insufficient Stock: Only $current units available.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Critical Sync Failure.";
    }
}

$products = $pdo->prepare("SELECT * FROM products WHERE branch_id = ? AND quantity > 0 ORDER BY product_name ASC");
$products->execute([$my_branch]);
$items = $products->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Outbound Register | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased">
    <?php include_once '../templates/sidebar_storekeeper.php'; ?>

    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300 min-h-screen flex flex-col">
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-10 text-center lg:text-left">
            <h2 class="text-4xl font-black text-slate-950 tracking-tighter uppercase italic leading-none text-center">Outbound Register</h2>
            <p class="text-amber-500 text-[10px] font-black uppercase tracking-[0.4em] mt-3 text-center">Recording Local Asset Dispatch</p>
        </div>

        <div class="flex-1 flex items-center justify-center pb-20">
            <div class="w-full max-w-xl bg-white p-12 rounded-[4rem] shadow-sm border border-slate-100">
                <form method="POST" class="space-y-8">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-4 ml-2">Select Asset Node</label>
                        <select name="product_id" id="prodSelectOut" required class="w-full p-6 bg-slate-50 border-none rounded-3xl font-black text-slate-700 outline-none focus:ring-4 focus:ring-amber-500/10 appearance-none shadow-inner">
                            <option value="">-- Choose Dispatch Asset --</option>
                            <?php foreach($items as $i): ?>
                                <option value="<?= $i['product_id'] ?>" data-name="<?= htmlspecialchars($i['product_name']) ?>"><?= htmlspecialchars($i['product_name']) ?> (Available: <?= $i['quantity'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="product_name" id="prodNameOut">
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-4 ml-2">Dispatch Volume (PCS)</label>
                        <input type="number" name="quantity" required min="1" placeholder="00" class="w-full p-8 bg-slate-50 border-none rounded-[2.5rem] font-black text-5xl text-amber-600 focus:ring-4 focus:ring-blue-500/10 transition outline-none shadow-inner text-center">
                    </div>

                    <button type="submit" name="register_outbound" class="w-full bg-slate-950 text-white p-8 rounded-[2.5rem] font-black uppercase tracking-[0.2em] hover:bg-amber-500 transition-all shadow-2xl active:scale-95">
                        Authorize Dispatch
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('prodSelectOut').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('prodNameOut').value = selectedOption.getAttribute('data-name');
        });
        <?php if($success_flag): ?>
            Swal.fire({ icon: 'success', title: 'DISPATCH AUTHORIZED', text: 'Stock removal synced.', confirmButtonColor: '#f59e0b' });
        <?php endif; ?>
        <?php if($error_msg): ?>
            Swal.fire({ icon: 'error', title: 'DISPATCH FAILED', text: '<?= $error_msg ?>', confirmButtonColor: '#ef4444' });
        <?php endif; ?>
    </script>
</body>
</html>