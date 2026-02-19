<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';
checkLogin();
$active_page = 'stock_request';

// Request ပို့ခြင်း Logic
if (isset($_POST['send_request'])) {
    $stmt = $pdo->prepare("INSERT INTO stock_requests (branch_id, product_id, quantity, status) VALUES (?, ?, ?, 'PENDING')");
    $stmt->execute([$_SESSION['branch_id'], $_POST['p_id'], $_POST['qty']]);
    logActivity($pdo, "Requested " . $_POST['qty'] . " units of Product ID: " . $_POST['p_id']);
    $msg = "Success: Request sent to HQ!";
}

$products = $pdo->query("SELECT * FROM products ORDER BY product_name ASC")->fetchAll();
$my_requests = $pdo->prepare("SELECT r.*, p.product_name FROM stock_requests r JOIN products p ON r.product_id = p.product_id WHERE r.branch_id = ? ORDER BY r.request_id DESC");
$my_requests->execute([$_SESSION['branch_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex overflow-x-hidden">
    <?php include '../templates/sidebar_admin.php'; ?>
    <main class="main-wrapper min-h-screen">
        <?php include '../templates/header.php'; ?>

        <div class="px-4 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm h-fit">
                <h3 class="text-xl font-black italic mb-6 uppercase tracking-tighter text-blue-600">Request Stock from HQ</h3>
                <form method="POST" class="space-y-5">
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Select Asset</label>
                        <select name="p_id" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold shadow-inner outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach($products as $p): ?>
                                <option value="<?php echo $p['product_id']; ?>"><?php echo $p['product_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Needed Quantity</label>
                        <input type="number" name="qty" min="1" required class="w-full p-4 bg-gray-50 border-none rounded-2xl font-black text-2xl shadow-inner outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" name="send_request" class="w-full bg-slate-900 text-white p-5 rounded-2xl font-black uppercase tracking-[0.2em] shadow-xl hover:bg-blue-600 transition">
                        Submit Request
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-[2.5rem] shadow-sm overflow-hidden border border-gray-50">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-widest border-b">
                        <tr>
                            <th class="p-6">Date</th>
                            <th class="p-6">Product</th>
                            <th class="p-6 text-center">Qty</th>
                            <th class="p-6 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($my_requests as $r): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-6 text-xs font-bold text-slate-400"><?php echo date('d M, Y', strtotime($r['request_date'])); ?></td>
                            <td class="p-6 font-black text-slate-700"><?php echo htmlspecialchars($r['product_name']); ?></td>
                            <td class="p-6 text-center font-black"><?php echo $r['quantity']; ?></td>
                            <td class="p-6 text-right">
                                <span class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest 
                                    <?php echo $r['status'] == 'PENDING' ? 'bg-yellow-50 text-yellow-600' : ($r['status'] == 'APPROVED' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'); ?>">
                                    <?php echo $r['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include '../templates/footer.php'; ?>
    </main>
</body>
</html>