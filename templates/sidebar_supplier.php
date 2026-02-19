<?php
// Active Page ကို သိရှိရန်အတွက် လက်ရှိ File Name ကို ယူပါသည်
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="fixed left-0 top-0 w-[280px] h-screen bg-slate-950 flex flex-col z-[100] shadow-[10px_0_40px_rgba(0,0,0,0.2)] overflow-y-auto no-print transition-all duration-300 border-r border-slate-800/50">
    
    <div class="p-10 mb-2">
        <div class="flex items-center gap-4 group cursor-pointer">
            <div class="w-12 h-12 bg-emerald-500 rounded-2xl flex items-center justify-center shadow-2xl shadow-emerald-500/20 group-hover:rotate-6 transition-transform duration-500">
                <i class="fas fa-leaf text-slate-950 text-xl"></i>
            </div>
            <div>
                <h1 class="text-white font-black italic tracking-tighter text-2xl leading-none uppercase">Supply<br><span class="text-emerald-500">Node</span></h1>
            </div>
        </div>
        </div>

    <nav class="flex-1 px-6 space-y-2">
        
        <p class="px-6 text-[8px] font-black text-slate-600 uppercase tracking-[0.4em] mb-4 mt-4">Main Terminal</p>

        <a href="../modules/supplier_dashboard.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-2xl transition-all duration-500 group 
           <?= ($current_page == 'supplier_dashboard.php') ? 'bg-emerald-500 text-slate-950 shadow-2xl shadow-emerald-500/20' : 'text-slate-400 hover:bg-slate-900 hover:text-emerald-400' ?>">
            <i class="fas fa-chart-pie text-sm <?= ($current_page == 'supplier_dashboard.php') ? 'text-slate-950' : 'group-hover:text-emerald-400' ?>"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.15em]">Global Overview</span>
        </a>

        <a href="../modules/supplier_orders.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-2xl transition-all duration-500 group 
           <?= ($current_page == 'supplier_orders.php') ? 'bg-emerald-500 text-slate-950 shadow-2xl shadow-emerald-500/20' : 'text-slate-400 hover:bg-slate-900 hover:text-emerald-400' ?>">
            <i class="fas fa-shopping-cart text-sm <?= ($current_page == 'supplier_orders.php') ? 'text-slate-950' : 'group-hover:text-emerald-400' ?>"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.15em]">Supply Requests</span>
        </a>

        <a href="../modules/supplier_stock.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-2xl transition-all duration-500 group 
           <?= ($current_page == 'supplier_stock.php') ? 'bg-emerald-500 text-slate-950 shadow-2xl shadow-emerald-500/20' : 'text-slate-400 hover:bg-slate-900 hover:text-emerald-400' ?>">
            <i class="fas fa-layer-group text-sm <?= ($current_page == 'supplier_stock.php') ? 'text-slate-950' : 'group-hover:text-emerald-400' ?>"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.15em]">Stock Monitor</span>
        </a>

        <div class="h-[1px] bg-slate-800/50 my-8 mx-6"></div>

        <p class="px-6 text-[8px] font-black text-slate-600 uppercase tracking-[0.4em] mb-4">Configuration</p>

        <a href="../modules/settings.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-2xl transition-all duration-500 group 
           <?= ($current_page == 'settings.php') ? 'bg-emerald-500 text-slate-950 shadow-2xl shadow-emerald-500/20' : 'text-slate-400 hover:bg-slate-900 hover:text-emerald-400' ?>">
            <i class="fas fa-cog text-sm <?= ($current_page == 'settings.php') ? 'text-slate-950' : 'group-hover:text-emerald-400' ?>"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.15em]">Settings</span>
        </a>

    </nav>

    <div class="p-6 mt-auto">
        <div class="bg-slate-900/50 rounded-3xl p-6 border border-slate-800 shadow-inner">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center border border-emerald-500/10">
                    <i class="fas fa-user text-emerald-500 text-sm"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-white font-black text-[11px] truncate uppercase italic tracking-tighter"><?= $_SESSION['username'] ?></p>
                    <p class="text-slate-500 text-[8px] font-bold uppercase tracking-widest mt-0.5">Active Partner</p>
                </div>
            </div>
            <a href="../logout.php" class="flex items-center justify-center gap-2 w-full bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white py-4 rounded-2xl text-[9px] font-black uppercase tracking-[0.2em] transition-all duration-500 shadow-lg">
                <i class="fas fa-power-off"></i> Terminate Node
            </a>
        </div>
    </div>
</div>