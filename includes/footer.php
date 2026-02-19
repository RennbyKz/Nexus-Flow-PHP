<footer class="mt-20 pb-10 px-6 no-print">
    <div class="bg-white/70 backdrop-blur-xl rounded-[3rem] px-10 py-8 border border-white/30 shadow-sm
                flex flex-col md:flex-row justify-between items-center gap-8">
        
        <div class="flex items-center gap-4">
             <div class="relative group">
                <img src="/SmartLogistics/assets/img/logo.png" alt="NexusFlow Logo" 
                     class="w-14 h-14 object-contain filter drop-shadow-[0_0_10px_rgba(37,99,235,0.25)] group-hover:scale-110 transition-transform duration-500">
                
                <div class="absolute inset-0 bg-blue-500 rounded-full blur-[30px] opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>
            </div>
            <div>
                <p class="text-xs font-black text-slate-800 uppercase tracking-tighter italic">Stock Management System</p>
                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest leading-none mt-1">
                    © <?= date('Y') ?> • Secured Terminal
                </p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-[8px] font-black text-slate-300 uppercase tracking-widest mb-1">Access Point</p>
            <div class="flex items-center gap-2">
                <i class="fas fa-map-marker-alt text-blue-500 text-[10px]"></i>
                <span class="text-[10px] font-black text-slate-600 uppercase italic">
                    <?= $_SESSION['branch_name'] ?? 'Central Gateway' ?>
                </span>
            </div>
        </div>
    </div>
</footer>