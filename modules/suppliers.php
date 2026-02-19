<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
authorize(['ADMIN']);
$active_page = 'suppliers';

// --- ၁။ DELETE SUPPLIER LOGIC ---
if (isset($_GET['delete_id'])) {
    $sid = $_GET['delete_id'];
    try {
        $pdo->beginTransaction();
        $stmtUser = $pdo->prepare("DELETE FROM users WHERE supplier_id = ?");
        $stmtUser->execute([$sid]);
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
        $stmt->execute([$sid]);
        $pdo->commit();
        header("Location: suppliers.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Delete Error: " . $e->getMessage());
    }
}

// --- ၂။ ADVANCED SEARCH LOGIC (PostgreSQL Trigram/FTS) ---
$search = $_GET['search'] ?? '';
$sql = "SELECT s.*, u.username FROM suppliers s LEFT JOIN users u ON s.supplier_id = u.supplier_id WHERE 1=1";
$params = [];

if (!empty($search)) {
    // Trigram similarity နှင့် ILIKE ပေါင်းစပ်ထားသော Search
    $sql .= " AND (s.name % ? OR s.contact_person % ? OR s.name ILIKE ? OR s.contact_person ILIKE ?)";
    $params[] = $search; 
    $params[] = $search;
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY s.supplier_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$suppliers = $stmt->fetchAll();

// --- ၃။ ADD/UPDATE LOGIC ---
if (isset($_POST['add_supplier']) || isset($_POST['update_supplier'])) {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact_person']);
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    try {
        $info = json_encode(['address' => $address, 'phone' => $phone, 'email' => $email, 'updated_at' => date('Y-m-d H:i:s')]);

        if (isset($_POST['update_supplier'])) {
            $sid = $_POST['supplier_id'];
            $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact_person = ?, info = ?::jsonb WHERE supplier_id = ?");
            $stmt->execute([$name, $contact, $info, $sid]);
            header("Location: suppliers.php?msg=updated");
        } else {
            $pdo->beginTransaction();
            // Supplier Insert
            $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, info) VALUES (?, ?, ?::jsonb) RETURNING supplier_id");
            $stmt->execute([$name, $contact, $info]);
            $supplier_id = $stmt->fetchColumn();

            // User Account Creation
            $username = strtolower(str_replace(' ', '_', $name)) . "_" . rand(100, 999);
            $hashed_password = password_hash('123456', PASSWORD_DEFAULT);
            $role_id = 3; // Role for SUPPLIER

            $stmtUser = $pdo->prepare("INSERT INTO users (username, password_hash, role_id, supplier_id, status) VALUES (?, ?, ?, ?, 'ACTIVE')");
            $stmtUser->execute([$username, $hashed_password, $role_id, $supplier_id]);

            $pdo->commit();
            header("Location: suppliers.php?msg=success&user=$username");
        }
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Sync Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Partner Network | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f7fa; }
        .main-content { margin-left: 320px; transition: all 0.4s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
        .partner-card { background: white; border: 1px solid rgba(28, 77, 141, 0.1); border-radius: 3rem; transition: all 0.4s ease; }
        .partner-card:hover { transform: translateY(-10px); box-shadow: 0 30px 60px -15px rgba(28, 77, 141, 0.15); border-color: #A1E3F9; }
        .animate-reveal { animation: reveal 0.6s ease-out forwards; opacity: 0; }
        @keyframes reveal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="antialiased text-slate-900 overflow-x-hidden">

    <?php include '../templates/sidebar_admin.php'; ?>

    <main class="main-content min-h-screen p-8 lg:p-12 flex flex-col">
        <?php include '../includes/header.php'; ?>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-8 animate-reveal">
            <div class="space-y-2">
                <span class="text-[10px] font-black uppercase tracking-[0.5em] text-[#1C4D8D]/60 italic block">Supply Chain Intelligence</span>
                <h2 class="text-6xl font-black text-[#1C4D8D] tracking-tighter uppercase italic leading-none">Partner <span class="text-sky-500">Network</span></h2>
            </div>
            <button onclick="openAddModal()" class="bg-[#1C4D8D] text-white px-10 py-5 rounded-2xl font-black shadow-xl hover:bg-blue-800 transition-all text-[10px] uppercase tracking-widest flex items-center gap-3">
                <i class="fas fa-handshake text-sky-300"></i> Onboard New Partner
            </button>
        </div>

        <div class="mb-12 animate-reveal" style="animation-delay: 0.1s;">
            <form action="" method="GET" class="relative max-w-2xl">
                <i class="fas fa-magnifying-glass absolute left-6 top-1/2 -translate-y-1/2 text-slate-300"></i>
                <input type="text" name="search" id="supplierSearch" list="supplierList" value="<?= htmlspecialchars($search) ?>" placeholder="Trace company name or manager alias..." 
                       class="w-full pl-16 pr-32 py-5 bg-white border-none rounded-[2rem] shadow-sm outline-none font-bold text-slate-600 focus:ring-8 focus:ring-[#1C4D8D]/5 transition-all">
                <datalist id="supplierList">
                    <?php foreach($suppliers as $s): ?><option value="<?= htmlspecialchars($s['name']) ?>"><?php endforeach; ?>
                </datalist>
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 bg-[#1C4D8D] text-white px-8 py-3 rounded-2xl font-black text-[9px] uppercase tracking-widest">Execute Find</button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-10 animate-reveal" style="animation-delay: 0.2s;">
            <?php foreach($suppliers as $s): 
                $info = json_decode($s['info'], true);
            ?>
            <div class="partner-card p-10 relative overflow-hidden group supplier-item">
                <button onclick="confirmDelete(<?= $s['supplier_id']; ?>, '<?= addslashes($s['name']); ?>')" 
                        class="absolute top-8 right-8 text-slate-200 hover:text-rose-500 transition-all z-20 p-2 hover:scale-125">
                    <i class="fas fa-trash-can text-lg"></i>
                </button>
                <div class="relative z-10">
                    <div class="mb-8">
                        <div class="w-16 h-16 bg-[#A1E3F9]/20 rounded-2xl flex items-center justify-center text-[#1C4D8D] mb-6 border border-[#A1E3F9]/30"><i class="fas fa-truck-ramp-box text-2xl"></i></div>
                        <h3 class="text-3xl font-black text-[#1C4D8D] uppercase italic tracking-tighter mb-3 truncate pr-10"><?= htmlspecialchars($s['name']); ?></h3>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Node Manager: <span class="text-emerald-600"><?= htmlspecialchars($s['contact_person'] ?? 'N/A'); ?></span></p>
                    </div>
                    <div class="space-y-4 mb-10">
                        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100"><i class="fas fa-phone text-sky-400 w-4 text-xs"></i><span class="text-xs font-bold text-slate-600"><?= htmlspecialchars($info['phone'] ?? 'N/A'); ?></span></div>
                        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100"><i class="fas fa-envelope text-sky-400 w-4 text-xs"></i><span class="text-xs font-bold text-slate-600"><?= htmlspecialchars($info['email'] ?? 'N/A'); ?></span></div>
                    </div>
                    <div class="pt-8 border-t border-dashed border-slate-100 flex justify-between items-center">
                        <span class="text-[11px] font-black text-[#1C4D8D] uppercase italic tracking-tighter">@<?= $s['username'] ?? 'ACCESS_DENIED'; ?></span>
                        <button onclick='openEditModal(<?= json_encode(["id" => $s['supplier_id'], "name" => $s['name'], "contact" => $s['contact_person'], "phone" => $info['phone'] ?? '', "email" => $info['email'] ?? '', "address" => $info['address'] ?? '']); ?>)' 
                                class="w-12 h-12 bg-slate-900 text-white rounded-2xl flex items-center justify-center hover:bg-[#1C4D8D] transition-all shadow-xl active:scale-90"><i class="fas fa-sliders-h"></i></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="supplierModal" class="hidden fixed inset-0 bg-[#1C4D8D]/80 backdrop-blur-xl z-[5000] flex items-center justify-center p-6" onclick="handleBackdropClick(event)">
        <div class="bg-white rounded-[4rem] w-full max-w-xl shadow-2xl overflow-hidden animate-reveal relative">
            <div class="bg-[#1C4D8D] p-12 text-white flex justify-between items-center relative">
                <div><h3 id="modalTitle" class="text-4xl font-black italic uppercase tracking-tighter">Partner Profile</h3><p class="text-[10px] font-black text-sky-300 uppercase tracking-[0.4em] mt-3">Distribution Channel Integration</p></div>
                <button onclick="closeModal()" class="w-14 h-14 rounded-full bg-white/5 flex items-center justify-center text-white hover:bg-rose-500 transition-all text-2xl group"><i class="fas fa-times group-hover:rotate-90 transition-transform"></i></button>
            </div>
            <form id="supplierForm" action="" method="POST" class="p-12 space-y-8 max-h-[70vh] overflow-y-auto relative z-10">
                <input type="hidden" name="supplier_id" id="f_id">
                <div class="space-y-4"><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">1. Corporate Entity Name</label><input type="text" name="name" id="f_name" required class="w-full p-6 bg-slate-50 border-none rounded-[2.5rem] font-bold text-slate-800 outline-none shadow-inner" placeholder="Company Name"></div>
                <div class="space-y-4"><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">2. Primary Manager</label><input type="text" name="contact_person" id="f_contact" required class="w-full p-6 bg-slate-50 border-none rounded-[2.5rem] font-bold text-slate-800 outline-none" placeholder="Manager Name"></div>
                <div class="grid grid-cols-2 gap-6">
                    <div><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">3. Comms (Phone)</label><input type="text" name="phone" id="f_phone" required class="w-full p-6 bg-slate-50 border-none rounded-[2.5rem] font-bold outline-none"></div>
                    <div><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">4. Digital (Email)</label><input type="email" name="email" id="f_email" required class="w-full p-6 bg-slate-50 border-none rounded-[2.5rem] font-bold outline-none"></div>
                </div>
                <div class="space-y-4"><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 italic">5. deployment Address</label><textarea name="address" id="f_address" required class="w-full p-8 bg-slate-50 border-none rounded-[3rem] font-bold text-slate-800 outline-none h-32 resize-none"></textarea></div>
                <button type="submit" id="f_btn" name="add_supplier" class="w-full bg-slate-950 text-white p-8 rounded-[3rem] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all active:scale-95 group"><i class="fas fa-link mr-3"></i> Initialize Partnership</button>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() { document.getElementById('modalTitle').innerText = "Onboard Partner"; document.getElementById('f_btn').name = "add_supplier"; document.getElementById('supplierForm').reset(); document.getElementById('supplierModal').classList.remove('hidden'); }
        function openEditModal(data) { document.getElementById('modalTitle').innerText = "Update Profile"; document.getElementById('f_btn').name = "update_supplier"; document.getElementById('f_id').value = data.id; document.getElementById('f_name').value = data.name; document.getElementById('f_contact').value = data.contact; document.getElementById('f_phone').value = data.phone; document.getElementById('f_email').value = data.email; document.getElementById('f_address').value = data.address; document.getElementById('supplierModal').classList.remove('hidden'); }
        function closeModal() { document.getElementById('supplierModal').classList.add('hidden'); }
        function handleBackdropClick(e) { if(e.target.id === 'supplierModal') closeModal(); }
        document.getElementById('supplierSearch').addEventListener('input', function(e) {
            const val = e.target.value.toLowerCase();
            document.querySelectorAll('.supplier-item').forEach(item => { item.style.display = item.innerText.toLowerCase().includes(val) ? 'block' : 'none'; });
        });
        function confirmDelete(id, name) { Swal.fire({ title: 'TERMINATE PARTNER?', text: `Remove ${name} from network?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'YES, REMOVE', customClass: { popup: 'rounded-[3rem] p-10' } }).then((res) => { if (res.isConfirmed) window.location.href = `suppliers.php?delete_id=${id}`; }); }
    </script>
</body>
</html>