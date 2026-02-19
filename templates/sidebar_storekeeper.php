<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-80 bg-slate-900 min-h-screen p-8 flex flex-col border-r border-slate-800 shadow-[20px_0_60px_rgba(0,0,0,0.4)] z-[100] fixed left-0 top-0 overflow-y-auto no-print">
    
    <div class="mb-12 px-2">
        <div class="flex items-center gap-4 mb-3 group cursor-pointer">
            <div class="w-12 h-12 bg-blue-600 rounded-[1.3rem] flex items-center justify-center shadow-[0_10px_25px_rgba(37,99,235,0.3)] group-hover:rotate-12 transition-all duration-500">
                <i class="fas fa-shipping-fast text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-white font-black italic tracking-tighter text-2xl leading-none uppercase">OM<span class="text-blue-500">NI</span></h1>
            </div>
        </div>
        <div class="flex items-center gap-2 ml-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <p class="text-emerald-500/80 text-[9px] font-black uppercase tracking-[0.4em]">Logistics Node</p>
        </div>
    </div>

    <nav class="flex-1 space-y-3">
        <p class="text-[9px] font-black text-slate-600 uppercase tracking-[0.3em] mb-6 ml-4 mt-8">Warehouse Operations</p>
        
        <a href="storekeeper_dashboard.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-[1.5rem] transition-all duration-500 group 
           <?php echo ($current_page == 'storekeeper_dashboard.php') ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-th-large text-sm <?php echo ($current_page == 'storekeeper_dashboard.php') ? 'text-white' : 'group-hover:text-blue-400'; ?>"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">Live Console</span>
        </a>

        <a href="stock_in.php" 
            class="flex items-center gap-4 px-6 py-4 rounded-[1.5rem] transition-all duration-300 group 
            <?php echo ($current_page == 'stock_in.php') ? 'bg-blue-600 text-white shadow-xl' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                <i class="fas fa-file-import text-sm <?php echo ($current_page == 'stock_in.php') ? 'text-white' : 'group-hover:text-emerald-400'; ?>"></i>
                <span class="font-black text-[10px] uppercase tracking-[0.2em]">Goods Inbound</span>
            </a>

            <a href="stock_out.php" 
            class="flex items-center gap-4 px-6 py-4 rounded-[1.5rem] transition-all duration-300 group 
            <?php echo ($current_page == 'stock_out.php') ? 'bg-blue-600 text-white shadow-xl' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
                <i class="fas fa-file-export text-sm <?php echo ($current_page == 'stock_out.php') ? 'text-white' : 'group-hover:text-amber-400'; ?>"></i>
                <span class="font-black text-[10px] uppercase tracking-[0.2em]">Goods Outbound</span>
            </a>

        <a href="my_inventory.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-[1.5rem] transition-all duration-500 group 
           <?php echo ($current_page == 'my_inventory.php') ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
            <i class="fas fa-boxes text-sm <?php echo ($current_page == 'my_inventory.php') ? 'text-white' : 'group-hover:text-blue-400'; ?>"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">My Inventory</span>
        </a>

        <div class="h-[1px] bg-slate-800/60 my-8 mx-4"></div>

        <p class="text-[9px] font-black text-slate-600 uppercase tracking-[0.3em] mb-6 ml-4">System Alerts</p>

        <a href="alerts.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-[1.5rem] transition-all duration-500 group 
           <?php echo ($current_page == 'alerts.php') ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white'; ?>">
            <div class="relative">
                <i class="fas fa-bell text-sm <?php echo ($current_page == 'alerts.php') ? 'text-white' : 'group-hover:text-red-400'; ?>"></i>
                <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse border-2 border-slate-900"></span>
            </div>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">Safety Alerts</span>
        </a>
    </nav>

    <div class="mt-auto pt-8 border-t border-slate-800">
        <div class="bg-slate-800/40 rounded-[2rem] p-6 border border-slate-700/50 mb-6 group hover:bg-slate-800 transition-all duration-500 shadow-inner">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:scale-110 transition-transform">
                    <i class="fas fa-user-shield text-lg"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-white font-black text-[11px] truncate uppercase tracking-tighter italic"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
                    <p class="text-emerald-500/60 text-[8px] font-black uppercase tracking-[0.2em] mt-0.5">Storekeeper Node</p>
                </div>
            </div>
            <a href="../logout.php" class="flex items-center justify-center gap-3 w-full bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white py-4 rounded-2xl font-black text-[9px] uppercase tracking-[0.3em] transition-all duration-500 shadow-xl">
                <i class="fas fa-power-off text-[10px]"></i> Secure Logout
            </a>
        </div>
    </div>
</aside>