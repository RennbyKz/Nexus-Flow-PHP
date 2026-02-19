<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-80 bg-slate-950 min-h-screen p-8 flex flex-col border-r border-cyan-900/30 shadow-[10px_0_50px_rgba(6,182,212,0.05)] z-[100] fixed left-0 top-0 overflow-y-auto no-print transition-all duration-300">
    
    <div class="mb-12 px-2">
        <div class="flex items-center gap-4 mb-3 group cursor-pointer">
            <div class="w-12 h-12 bg-cyan-500 rounded-2xl flex items-center justify-center shadow-[0_0_25px_rgba(6,182,212,0.4)] group-hover:scale-105 transition-transform duration-500">
                <i class="fas fa-fingerprint text-slate-950 text-xl"></i>
            </div>
            <div>
                <h1 class="text-white font-black italic tracking-tighter text-2xl leading-none uppercase">OM<span class="text-cyan-500">NI</span></h1>
            </div>
        </div>
        <div class="flex items-center gap-2 ml-1">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 shadow-[0_0_8px_rgba(6,182,212,1)]"></span>
            <p class="text-cyan-500/80 text-[8px] font-black uppercase tracking-[0.4em]">Audit Infrastructure</p>
        </div>
    </div>

    <nav class="flex-1 space-y-2">
        <p class="text-[8px] font-black text-slate-600 uppercase tracking-[0.4em] mb-6 ml-4 mt-8 opacity-50">Monitoring Engine</p>
        
        <a href="auditor_dashboard.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-2xl transition-all duration-500 group 
           <?php echo ($current_page == 'auditor_dashboard.php') ? 'bg-cyan-500 text-slate-950 shadow-[0_15px_30px_rgba(6,182,212,0.2)]' : 'text-slate-500 hover:bg-slate-900 hover:text-cyan-400'; ?>">
            <i class="fas fa-gauge-high text-sm <?php echo ($current_page == 'auditor_dashboard.php') ? 'text-slate-950' : 'group-hover:text-cyan-400'; ?> transition-colors"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">Live Overview</span>
        </a>

        <a href="auditor_logs.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-2xl transition-all duration-500 group 
           <?php echo ($current_page == 'auditor_logs.php') ? 'bg-cyan-500 text-slate-950 shadow-[0_15px_30px_rgba(6,182,212,0.2)]' : 'text-slate-500 hover:bg-slate-900 hover:text-cyan-400'; ?>">
            <i class="fas fa-database text-sm <?php echo ($current_page == 'auditor_logs.php') ? 'text-slate-950' : 'group-hover:text-cyan-400'; ?> transition-colors"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">System Logs</span>
        </a>

        <div class="h-[1px] bg-slate-900 my-10 mx-4"></div>

        <p class="text-[8px] font-black text-slate-600 uppercase tracking-[0.4em] mb-6 ml-4 opacity-50">Security Verification</p>

        <a href="access_review.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-2xl transition-all duration-500 group 
           <?php echo ($current_page == 'access_review.php') ? 'bg-cyan-500 text-slate-950' : 'text-slate-500 hover:bg-slate-900 hover:text-cyan-400'; ?>">
            <i class="fas fa-user-shield text-sm transition-colors"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">Access Review</span>
        </a>

        <a href="integrity_reports.php" 
           class="flex items-center gap-4 px-6 py-4 rounded-2xl transition-all duration-500 group 
           <?php echo ($current_page == 'integrity_reports.php') ? 'bg-cyan-500 text-slate-950' : 'text-slate-500 hover:bg-slate-900 hover:text-cyan-400'; ?>">
            <i class="fas fa-file-contract text-sm transition-colors"></i>
            <span class="font-black text-[10px] uppercase tracking-[0.2em]">Compliance PDF</span>
        </a>
    </nav>

    <div class="mt-auto pt-8 border-t border-slate-900">
        <div class="bg-slate-900/80 rounded-3xl p-6 border border-cyan-900/20 mb-6 group hover:border-cyan-500/30 transition-all duration-500">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-10 h-10 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-400 border border-cyan-500/20">
                    <i class="fas fa-shield-halved text-lg"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-white font-black text-[11px] truncate uppercase tracking-tighter italic"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    <p class="text-cyan-500/50 text-[8px] font-black uppercase tracking-[0.2em] mt-0.5">Verified Trace Auditor</p>
                </div>
            </div>
            <a href="../logout.php" class="flex items-center justify-center gap-3 w-full bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white py-4 rounded-2xl font-black text-[9px] uppercase tracking-[0.3em] transition-all duration-500 shadow-xl active:scale-95">
                <i class="fas fa-power-off"></i> Terminate Node
            </a>
        </div>
    </div>
</aside>