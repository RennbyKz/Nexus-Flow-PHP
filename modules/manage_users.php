<?php
session_start();
include '../config/db.php';
include '../includes/functions.php';

checkLogin();
authorize(['ADMIN']);
$active_page = 'users';

// --- ၁။ PASSWORD RESET LOGIC ---
if (isset($_GET['reset_id'])) {
    $uid = $_GET['reset_id'];
    $new_pass = password_hash('password123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->execute([$new_pass, $uid]);
    header("Location: manage_users.php?msg=reset_success"); exit();
}

// --- ၂။ CSV EXPORT LOGIC ---
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Identity_Registry_'.date('Y-m-d').'.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['User ID', 'Username', 'Role', 'Branch Scope', 'Telegram ID', 'Status']);
    
    $export_sql = "SELECT u.user_id, u.username, r.role_name, COALESCE(b.branch_name, 'HQ') as branch, u.telegram_chat_id, u.status 
                   FROM users u 
                   LEFT JOIN roles r ON u.role_id = r.role_id 
                   LEFT JOIN branches b ON u.branch_id = b.branch_id";
    $stmt = $pdo->query($export_sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { fputcsv($output, $row); }
    fclose($output); exit();
}

// --- ၃။ STATUS TOGGLE ---
if (isset($_GET['toggle_id'])) {
    $uid = $_GET['toggle_id'];
    $current = $_GET['current'];
    $new_status = ($current == 'ACTIVE') ? 'DISABLED' : 'ACTIVE';
    $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?")->execute([$new_status, $uid]);
    header("Location: manage_users.php"); exit();
}

// --- ၄။ ADD/UPDATE USER ---
if (isset($_POST['save_user'])) {
    $id = $_POST['user_id'] ?? null;
    $user = trim($_POST['username']);
    $role = $_POST['role_id'];
    $branch = !empty($_POST['branch_id']) ? $_POST['branch_id'] : null;
    $telegram_id = trim($_POST['telegram_chat_id']);

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, role_id = ?, branch_id = ?, telegram_chat_id = ? WHERE user_id = ?");
            $stmt->execute([$user, $role, $branch, $telegram_id, $id]);
        } else {
            $pass = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id, branch_id, telegram_chat_id, status) VALUES (?, ?, ?, ?, ?, 'ACTIVE')");
            $stmt->execute([$user, $pass, $role, $branch, $telegram_id]);
        }
        header("Location: manage_users.php?msg=success"); exit();
    } catch (PDOException $e) { $error = "Entity Sync Failed: " . $e->getMessage(); }
}

// --- ၅။ DELETE USER ---
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$_GET['delete_id']]);
    header("Location: manage_users.php?msg=deleted"); exit();
}

// --- ၆။ ⭐ SEARCH & FILTER LOGIC ---
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role_filter'] ?? '';
$sql = "SELECT u.*, r.role_name, b.branch_name FROM users u 
        LEFT JOIN roles r ON u.role_id = r.role_id 
        LEFT JOIN branches b ON u.branch_id = b.branch_id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.username ILIKE ? OR u.telegram_chat_id ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($role_filter)) { 
    $sql .= " AND u.role_id = ?"; 
    $params[] = $role_filter; 
}

$sql .= " ORDER BY u.user_id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$roles = $pdo->query("SELECT * FROM roles ORDER BY role_id ASC")->fetchAll();
$branches = $pdo->query("SELECT * FROM branches WHERE status = 'ONLINE'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Identity Hub | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f3f7fa; }
        .main-content { margin-left: 320px; transition: all 0.4s ease; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; } }
        .executive-card { background: white; border: 1px solid rgba(28, 77, 141, 0.1); border-radius: 3rem; overflow: hidden; box-shadow: 0 20px 50px -15px rgba(28, 77, 141, 0.05); }
        .animate-reveal { animation: reveal 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; opacity: 0; }
        @keyframes reveal { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="antialiased text-slate-900">

    <?php include '../templates/sidebar_admin.php'; ?>

    <main class="main-content min-h-screen p-8 lg:p-12 flex flex-col">
        <?php include '../includes/header.php'; ?>

        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-8 animate-reveal">
            <div class="space-y-2">
                <span class="text-[10px] font-black  tracking-[0.5em] text-[#1C4D8D]/60 italic block">Network Security Node</span>
                <h2 class="text-6xl font-black text-[#1C4D8D] tracking-tighter  italic leading-none">Identity <span class="text-sky-500">Hub</span></h2>
            </div>
            <div class="flex gap-4 no-print">
                <a href="?export=1" class="bg-white text-[#1C4D8D] px-8 py-5 rounded-2xl font-black  text-[10px] tracking-widest shadow-sm border border-slate-200 hover:bg-slate-50 transition flex items-center gap-3">
                    <i class="fas fa-file-csv text-emerald-500"></i> Export Ledger
                </a>
                <button onclick="openAddModal()" class="bg-[#1C4D8D] text-white px-10 py-5 rounded-2xl font-black shadow-xl hover:bg-blue-800 transition-all text-[10px]  tracking-widest flex items-center gap-3">
                    <i class="fas fa-user-plus text-sky-300"></i> Register Operator
                </button>
            </div>
        </div>

        <div class="mb-10 no-print animate-reveal" style="animation-delay: 0.1s;">
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-wrap gap-4 items-center">
                <form action="" method="GET" class="flex flex-wrap gap-4 flex-1">
                    <div class="relative flex-1 min-w-[250px]">
                        <i class="fas fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Trace system alias or Telegram ID..." class="w-full pl-12 pr-4 py-5 bg-slate-50 border-none rounded-2xl font-bold text-sm outline-none focus:ring-4 focus:ring-[#1C4D8D]/5 transition-all">
                    </div>
                    <select name="role_filter" class="p-5 bg-slate-50 border-none rounded-2xl font-black text-[10px]  tracking-widest text-[#1C4D8D] outline-none">
                        <option value="">All Security Clearance</option>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['role_id'] ?>" <?= $role_filter == $r['role_id'] ? 'selected' : '' ?>><?= strtoupper($r['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-slate-900 text-white px-10 py-5 rounded-2xl font-black text-[10px]  tracking-widest hover:bg-[#1C4D8D]">Execute Find</button>
                    <?php if(!empty($search) || !empty($role_filter)): ?>
                        <a href="manage_users.php" class="bg-rose-50 text-rose-500 px-6 py-5 rounded-2xl font-black text-[10px]  tracking-widest hover:bg-rose-500 hover:text-white transition-all">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="executive-card animate-reveal" style="animation-delay: 0.2s;">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-[#1C4D8D] text-[10px] font-black  tracking-[0.25em] border-b border-slate-100">
                        <tr>
                            <th class="p-10">Entity Identity</th>
                            <th class="p-10 text-center">Clearance</th>
                            <th class="p-10">Telegram ID</th>
                            <th class="p-10">Security Status</th>
                            <th class="p-10 text-right">Decisions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if(empty($users)): ?>
                            <tr><td colspan="5" class="p-20 text-center font-bold text-slate-400 italic">No operators found matching the criteria.</td></tr>
                        <?php endif; ?>
                        <?php foreach($users as $u): ?>
                        <tr class="user-row transition-all duration-300 hover:bg-sky-50/30">
                            <td class="p-10">
                                <div class="flex items-center gap-6">
                                    <div class="w-16 h-16 bg-[#A1E3F9]/20 rounded-2xl flex items-center justify-center text-[#1C4D8D]"><i class="fas fa-user-shield text-2xl"></i></div>
                                    <div>
                                        <span class="text-2xl font-black text-[#1C4D8D] block italic tracking-tighter  leading-none mb-2">@<?= htmlspecialchars($u['username']); ?></span>
                                        <span class="text-[9px] font-black text-slate-300  tracking-widest italic">Node: <?= $u['branch_name'] ?? 'Root HQ'; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-10 text-center">
                                <span class="px-5 py-2 rounded-xl text-[9px] font-black  border <?= ($u['role_name'] == 'ADMIN') ? 'bg-indigo-50 text-indigo-600 border-indigo-100' : 'bg-slate-50 text-slate-500 border-slate-100'; ?>"><?= $u['role_name']; ?></span>
                            </td>
                            <td class="p-10 font-black text-[#1C4D8D] text-sm">
                                <div class="flex items-center gap-2">
                                    <i class="fab fa-telegram text-sky-500"></i>
                                    <?= $u['telegram_chat_id'] ?: '<span class="text-slate-300 italic font-normal">Not Linked</span>'; ?>
                                </div>
                            </td>
                            <td class="p-10">
                                <a href="?toggle_id=<?= $u['user_id']; ?>&current=<?= $u['status']; ?>" class="flex items-center gap-4">
                                    <div class="w-12 h-6 <?= ($u['status'] == 'ACTIVE') ? 'bg-emerald-500' : 'bg-slate-200'; ?> rounded-full relative transition-all">
                                        <div class="w-4 h-4 bg-white rounded-full absolute top-1 <?= ($u['status'] == 'ACTIVE') ? 'right-1' : 'left-1'; ?> transition-all"></div>
                                    </div>
                                    <span class="text-[9px] font-black  <?= ($u['status'] == 'ACTIVE') ? 'text-emerald-600' : 'text-slate-300'; ?> italic"><?= $u['status']; ?></span>
                                </a>
                            </td>
                            <td class="p-10 text-right space-x-2">
                                <button onclick="confirmReset(<?= $u['user_id'] ?>, '<?= $u['username'] ?>')" class="w-11 h-11 bg-amber-50 text-amber-500 rounded-xl hover:bg-amber-500 transition-all"><i class="fas fa-key text-xs"></i></button>
                                <button onclick='openEditModal(<?= json_encode($u); ?>)' class="w-11 h-11 bg-blue-50 text-blue-500 rounded-xl hover:bg-blue-600 hover:text-white transition-all"><i class="fas fa-sliders-h text-xs"></i></button>
                                <button onclick="confirmDelete(<?= $u['user_id'] ?>, '<?= $u['username'] ?>')" class="w-11 h-11 bg-rose-50 text-rose-300 rounded-xl hover:bg-rose-500 hover:text-white transition-all"><i class="fas fa-trash-can text-xs"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="userModal" class="hidden fixed inset-0 bg-[#1C4D8D]/80 backdrop-blur-xl z-[5000] flex items-center justify-center p-6" onclick="handleBackdropClick(event)">
        <div class="bg-white rounded-[4rem] w-full max-w-md shadow-2xl overflow-hidden animate-reveal relative">
            <div class="bg-slate-950 p-12 text-white flex justify-between items-center">
                <div><h3 id="modalTitle" class="text-3xl font-black italic  tracking-tighter">Account Config</h3></div>
                <button onclick="closeModal()" class="text-xl hover:text-rose-500 transition-all"><i class="fas fa-times"></i></button>
            </div>
            <form id="userForm" method="POST" class="p-12 space-y-6">
                <input type="hidden" name="user_id" id="form_id">
                <div class="space-y-2"><label class="text-[10px] font-black text-slate-400  tracking-widest ml-4">1. Username</label><input type="text" name="username" id="form_name" required class="w-full p-6 bg-slate-50 border-none rounded-[2rem] font-bold"></div>
                <div class="space-y-2"><label class="text-[10px] font-black text-slate-400  tracking-widest ml-4">2. Clearance</label><select name="role_id" id="form_role" required class="w-full p-6 bg-slate-50 border-none rounded-[2rem] font-bold"><?php foreach($roles as $r): ?><option value="<?= $r['role_id']; ?>"><?= strtoupper($r['role_name']); ?></option><?php endforeach; ?></select></div>
                <div class="space-y-2"><label class="text-[10px] font-black text-slate-400  tracking-widest ml-4">3. Telegram Chat ID</label><input type="text" name="telegram_chat_id" id="form_telegram" class="w-full p-6 bg-slate-50 border-none rounded-[2rem] font-bold" placeholder="Ex: 1162211533"></div>
                <div class="space-y-2"><label class="text-[10px] font-black text-slate-400  tracking-widest ml-4">4. Node Scope</label><select name="branch_id" id="form_branch" class="w-full p-6 bg-slate-50 border-none rounded-[2rem] font-bold"><option value="">GLOBAL ROOT</option><?php foreach($branches as $b): ?><option value="<?= $b['branch_id']; ?>"><?= strtoupper($b['branch_name']); ?></option><?php endforeach; ?></select></div>
                <button type="submit" name="save_user" class="w-full bg-slate-950 text-white p-8 rounded-[2.5rem] font-black  text-[10px] tracking-widest hover:bg-[#1C4D8D] transition-all"><i class="fas fa-fingerprint mr-4"></i> Synchronize Entity</button>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() { document.getElementById('modalTitle').innerText = "Register Operator"; document.getElementById('form_id').value = ""; document.getElementById('userForm').reset(); document.getElementById('userModal').classList.remove('hidden'); }
        function openEditModal(data) { document.getElementById('modalTitle').innerText = "Update Config"; document.getElementById('form_id').value = data.user_id; document.getElementById('form_name').value = data.username; document.getElementById('form_role').value = data.role_id; document.getElementById('form_branch').value = data.branch_id || ""; document.getElementById('form_telegram').value = data.telegram_chat_id || ""; document.getElementById('userModal').classList.remove('hidden'); }
        function closeModal() { document.getElementById('userModal').classList.add('hidden'); }
        function handleBackdropClick(e) { if(e.target.id === 'userModal') closeModal(); }
        function confirmReset(id, name) { Swal.fire({ title: 'RESET KEY?', text: `Reset @${name} password?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#f59e0b', confirmButtonText: 'YES' }).then((res) => { if (res.isConfirmed) window.location.href = `manage_users.php?reset_id=${id}`; }); }
        function confirmDelete(id, name) { Swal.fire({ title: 'TERMINATE?', text: `Purge @${name}?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'YES' }).then((res) => { if (res.isConfirmed) window.location.href = `manage_users.php?delete_id=${id}`; }); }
    </script>
</body>
</html>