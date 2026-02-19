<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-80 bg-slate-900 min-h-screen p-8 flex flex-col border-r border-slate-800 shadow-[20px_0_60px_rgba(0,0,0,0.4)] z-[100] fixed left-0 top-0 overflow-y-auto no-print">
    
    <div class="mb-12 px-2">
        <div class="flex items-center gap-4 mb-3 group cursor-pointer">
            <div class="w-12 h-12 bg-violet-600 rounded-[1.3rem] flex items-center justify-center shadow-[0_10px_25px_rgba(139,92,246,0.3)] group-hover:scale-110 transition-all duration-500">
                <i class="fas fa-briefcase text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-white font-black italic tracking-tighter text-2xl leading-none uppercase">OM<span class="text-violet-500">NI</span></h1>
            </div>
        </div>
        <div class="flex items-center gap-2 ml-1">
            <span class="w-2 h-2 rounded-full bg-violet-500 animate-pulse"></span>
            <p class="text-violet-400 text-[9px] font-black uppercase tracking-[0.4em]">Managerial Node</p>
        </div>
    </div>

    <nav class="flex-1 space-y-3">
        <p class="text-[9px] font-black text-slate-600 uppercase tracking-[0.3em] mb-6 ml-4 mt-8">Operations Hub</p>
        
        <a href="manager_dashboard.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-[1.5rem] transition-all duration-500 group 
           <?php echo ($current_page == 'manager_dashboard.php') ? 'bg-violet-600 text-white shadow-xl shadow-violet-600/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-chart-pie text-sm <?php echo ($current_page == 'manager_dashboard.php') ? 'text-white' : 'group-hover:text-violet-400'; ?>"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">Branch Overview</span>
        </a>

        <a href="stock_requests.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-[1.5rem] transition-all duration-500 group 
           <?php echo ($current_page == 'stock_requests.php') ? 'bg-violet-600 text-white shadow-xl shadow-violet-600/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-file-export text-sm <?php echo ($current_page == 'stock_requests.php') ? 'text-white' : 'group-hover:text-violet-400'; ?>"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">Procurement</span>
        </a>

        <a href="manager_inventory.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-[1.5rem] transition-all duration-500 group 
           <?php echo ($current_page == 'manager_inventory.php') ? 'bg-violet-600 text-white shadow-xl shadow-violet-600/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-boxes text-sm <?php echo ($current_page == 'manager_inventory.php') ? 'text-white' : 'group-hover:text-violet-400'; ?>"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">Local Inventory</span>
        </a>

        <div class="h-[1px] bg-slate-800/60 my-8 mx-4"></div>

        <p class="text-[9px] font-black text-slate-600 uppercase tracking-[0.3em] mb-6 ml-4">Personnel & Logs</p>

        <a href="branch_staff.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-[1.5rem] transition-all duration-500 group 
           <?php echo ($current_page == 'branch_staff.php') ? 'bg-violet-600 text-white shadow-xl' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-users-gear text-sm"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">Staff Directory</span>
        </a>

        <a href="transfer_logs.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-[1.5rem] transition-all duration-500 group 
           <?php echo ($current_page == 'transfer_logs.php') ? 'bg-violet-600 text-white shadow-xl' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-exchange-alt text-sm"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">Transfer Logs</span>
        </a>
    </nav>

    <div class="mt-auto pt-8 border-t border-slate-800">
        <div class="bg-slate-800/40 rounded-[2rem] p-6 border border-slate-700/50 mb-6 group hover:bg-slate-800 transition-all duration-500 shadow-inner">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-10 h-10 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400 group-hover:rotate-6 transition-transform">
                    <i class="fas fa-user-tie text-lg"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-white font-black text-[11px] truncate uppercase tracking-tighter italic"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    <p class="text-violet-500/60 text-[8px] font-black uppercase tracking-[0.2em] mt-0.5">Verified Manager</p>
                </div>
            </div>
            <a href="../logout.php" class="flex items-center justify-center gap-3 w-full bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white py-4 rounded-2xl font-black text-[9px] uppercase tracking-[0.3em] transition-all duration-500 shadow-xl">
                <i class="fas fa-power-off text-[10px]"></i> Terminate Node
            </a>
        </div>
    </div>
</aside>