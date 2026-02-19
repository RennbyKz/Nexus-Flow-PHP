<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../config/db.php';
include_once '../includes/functions.php';

checkLogin();
// Role ၅ ခုစလုံးကို ခွင့်ပြုထားသည်
authorize(['ADMIN', 'MANAGER', 'STOREKEEPER', 'AUDITOR', 'SUPPLIER']);

$active_page = 'settings';
$role = strtoupper($_SESSION['role']);
$msg = "";

// --- ⭐ SECURITY UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_security'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $uid = $_SESSION['user_id'];

    try {
        // User data ကို ဆွဲယူခြင်း
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();

        if ($user && password_verify($current, $user['password_hash'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$hashed, $uid]);
            
            // Audit Log မှတ်တမ်းတင်ခြင်း
            logActivity($pdo, "Security: Password synchronization completed by @$role-Node");
            $msg = "Success: Security credentials updated successfully.";
        } else {
            $msg = "Error: Authentication failed. Legacy key is incorrect.";
        }
    } catch (PDOException $e) {
        $msg = "Error: System synchronization failure.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | OMNI FLOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .animate-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50 flex font-sans text-slate-900 antialiased overflow-x-hidden">

    <?php 
        // Role အလိုက် Sidebar ကို Dynamic ခေါ်ယူခြင်း
        $sidebar_path = "../templates/sidebar_" . strtolower($role) . ".php";
        if(file_exists($sidebar_path)) include_once $sidebar_path;
    ?>
    
    <main class="flex-1 p-6 md:p-10 ml-0 lg:ml-[320px] transition-all duration-300 main-content min-h-screen">
        
        <?php include_once '../includes/header.php'; ?>

        <div class="mb-12 animate-in">
            <h2 class="text-5xl font-black text-slate-950 tracking-tighter uppercase italic leading-none">Node Settings</h2>
            <p class="text-blue-600 text-[10px] font-black uppercase tracking-[0.4em] mt-3 bg-blue-50 px-4 py-1.5 rounded-full inline-block border border-blue-100 italic">
                System Security & Profile Management
            </p>
        </div>

        <div class="grid lg:grid-cols-2 gap-10 animate-in" style="animation-delay: 0.1s;">
            <div class="bg-white p-12 rounded-[4rem] shadow-sm border border-slate-100">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-14 h-14 bg-slate-950 text-white rounded-[1.5rem] flex items-center justify-center shadow-xl">
                        <i class="fas fa-shield-halved text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black uppercase italic tracking-tighter text-slate-800">Security Node</h3>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Update Authentication Keys</p>
                    </div>
                </div>

                <?php if($msg): ?>
                    <div class="p-6 mb-10 rounded-3xl font-black text-[10px] uppercase tracking-widest border animate-pulse
                        <?php echo strpos($msg,'Success') !== false ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100'; ?>">
                        <i class="fas <?= strpos($msg,'Success') !== false ? 'fa-check-circle' : 'fa-triangle-exclamation' ?> mr-2"></i> 
                        <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-8">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-3 ml-2 italic">1. Legacy Key (Current Password)</label>
                        <input type="password" name="current_password" required placeholder="••••••••"
                               class="w-full p-6 bg-slate-50 border-none rounded-[1.8rem] outline-none focus:ring-4 focus:ring-blue-500/10 shadow-inner font-bold text-slate-700">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-3 ml-2 italic">2. New Node Key (New Password)</label>
                        <input type="password" name="new_password" required placeholder="••••••••"
                               class="w-full p-6 bg-slate-50 border-none rounded-[1.8rem] outline-none focus:ring-4 focus:ring-blue-500/10 shadow-inner font-bold text-slate-700">
                    </div>
                    <button type="submit" name="update_security" class="w-full bg-slate-950 text-white p-8 rounded-[2rem] font-black uppercase tracking-[0.2em] text-[10px] shadow-2xl hover:bg-blue-600 transition-all hover:-translate-y-1 active:scale-95">
                        Sync Security Node
                    </button>
                </form>
            </div>

            <div class="bg-slate-950 p-12 rounded-[4rem] shadow-2xl text-white relative overflow-hidden group border border-slate-800">
                <div class="absolute -right-20 -top-20 text-[18rem] text-white/5 italic font-black pointer-events-none group-hover:-translate-x-10 transition-transform duration-1000">ID</div>
                
                <div class="relative z-10 h-full flex flex-col">
                    <h3 class="text-2xl font-black uppercase italic tracking-tighter mb-12 flex items-center gap-3">
                        <span class="w-2 h-8 bg-blue-500 rounded-full"></span> User Identity
                    </h3>
                    
                    <div class="space-y-10 flex-1">
                        <div class="flex flex-col">
                            <span class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-2 italic">System Entity Alias</span>
                            <span class="text-4xl font-black uppercase italic tracking-tighter text-white">@<?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </div>
                        
                        <div class="flex flex-col">
                            <span class="text-[9px] font-black text-emerald-400 uppercase tracking-widest mb-2 italic">Authorization Clearance</span>
                            <span class="text-2xl font-black uppercase italic tracking-tight text-white/80"><?php echo $role; ?> Node</span>
                        </div>

                        <div class="flex flex-col">
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">Operational Location</span>
                            <span class="text-2xl font-black uppercase italic tracking-tight text-slate-300">
                                <?php 
                                    if ($role === 'SUPPLIER') {
                                        echo $_SESSION['supplier_name'] ?? 'Authorized Vendor';
                                    } else {
                                        echo $_SESSION['branch_name'] ?? 'Global Logistics Hub';
                                    }
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="mt-auto pt-10 border-t border-white/10 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            </span>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-500">Node Online</span>
                        </div>
                        <span class="text-[8px] font-bold text-slate-600 uppercase">Version 2.0.26-Stable</span>
                    </div>
                </div>
            </div>
        </div>

        <?php include_once '../includes/footer.php'; ?>
    </main>
</body>
</html>