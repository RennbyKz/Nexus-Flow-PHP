<?php
// current page detect
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isIndexPage = ($currentPage === 'index');
?>

<header class="sticky top-0 z-[999] w-full px-6 py-4 no-print transition-all duration-300">
    <div class="flex justify-between items-center bg-white/80 backdrop-blur-xl rounded-[2rem] px-8 py-4 shadow-md border border-white/40">
        
        <button onclick="toggleSidebar()" class="lg:hidden p-3 rounded-xl bg-white shadow-sm border border-slate-100 hover:bg-slate-50 transition active:scale-90">
            <i class="fa fa-bars text-slate-600"></i>
        </button>

        <div class="flex items-center gap-6">
           <div class="flex items-center gap-6">
    <div class="hidden sm:flex items-center gap-4 border-r border-slate-100 pr-6">
        <div class="flex items-center gap-3">
            <div class="relative group">
                <img src="/SmartLogistics/assets/img/logo.png" alt="NexusFlow Logo" 
                     class="w-14 h-14 object-contain filter drop-shadow-[0_0_10px_rgba(37,99,235,0.25)] group-hover:scale-110 transition-transform duration-500">
                
                <div class="absolute inset-0 bg-blue-500 rounded-full blur-[30px] opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>
            </div>
            
            <div class="flex flex-col">
                <h1 class="text-xl font-black text-slate-900 tracking-tighter uppercase italic leading-none">
                    NexusFlow <span class="text-blue-600">INVENTORY</span>
                </h1>
                <span class="text-[7px] font-black text-slate-400 uppercase tracking-[0.4em] mt-1 italic leading-none">
                    INTEGRATED BOT INTELLIGENCE
                </span>
            </div>
        </div>
    </div>
</div>

            <div class="hidden md:flex items-center gap-5">
                <div class="flex items-center gap-2.5 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-100">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                    <p id="headerClock" class="text-[10px] font-black uppercase tracking-widest text-emerald-700 font-mono"></p>
                </div>
                <div class="flex items-center gap-2 text-slate-400">
                    <i class="far fa-calendar-alt text-[9px]"></i>
                    <p class="text-[9px] font-black uppercase tracking-widest leading-none">
                        <?= date('D, d M Y') ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if (!$isIndexPage): ?>
        <div class="flex items-center gap-2">
            
            <a href="notifications.php" class="relative group no-print">
                <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:text-blue-600 group-hover:bg-blue-50 transition-all duration-500 border border-transparent group-hover:border-blue-100">
                    <i class="fas fa-bell text-xs"></i>
                </div>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white shadow-sm animate-bounce"></span>
            </a>

            <a href="settings.php" class="group no-print">
                <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:text-slate-900 group-hover:bg-slate-100 transition-all duration-500 border border-transparent group-hover:border-slate-200">
                    <i class="fas fa-cog text-xs group-hover:rotate-90 transition-transform duration-700"></i>
                </div>
            </a>

            <div class="flex items-center gap-3 pl-4 border-l border-slate-100 ml-2">
                <div class="hidden lg:block text-right">
                    <p class="text-[9px] font-black text-slate-800 uppercase leading-none mb-1 tracking-tight">
                        <?= $_SESSION['username'] ?? 'Operator' ?>
                    </p>
                    <span class="text-[6px] font-black text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded uppercase tracking-widest border border-blue-100/50 italic">
                        <?= $_SESSION['role'] ?? 'Node Access' ?>
                    </span>
                </div>
                
                <div class="relative group cursor-pointer">
                    <img src="https://ui-avatars.com/api/?name=<?= $_SESSION['username'] ?? 'U' ?>&background=1C4D8D&color=fff&bold=true" 
                         class="w-10 h-10 rounded-xl shadow-md border-2 border-white group-hover:rotate-6 transition-transform duration-500">
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full shadow-sm"></div>
                </div>

                <a href="../logout.php" class="w-9 h-9 flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-rose-50 rounded-xl transition-all duration-300" title="Logout">
                    <i class="fas fa-power-off text-[10px]"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</header>

<script>
    function updateHeaderClock() {
        const now = new Date();
        const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
        const timeStr = now.toLocaleTimeString('en-US', options);
        const clockElement = document.getElementById('headerClock');
        if(clockElement) clockElement.innerText = timeStr;
    }
    setInterval(updateHeaderClock, 1000);
    updateHeaderClock();
</script>