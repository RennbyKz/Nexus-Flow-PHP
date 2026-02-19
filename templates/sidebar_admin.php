<?php
if (!function_exists('sidebar_admin_link')) {
    function sidebar_admin_link($url, $icon, $label, $color, $page_key, $active_page) {
        $activeClass = ($active_page === $page_key) ? 'active' : '';
        return "
        <a href='$url' class='nav-item $activeClass'>
            <div class='icon-square'>
                <i class='fa $icon' style='color:$color'></i>
            </div>
            <span class='nav-label'>$label</span>
        </a>";
    }
}
?>

<style>
/* ===================== SIDEBAR BASE ===================== */
.aside-container {
    width: 280px;
    height: calc(100vh - 40px);
    position: fixed;
    left: 20px;
    top: 20px;
    background: #1C4D8D;
    border-radius: 2.5rem;
    padding: 24px 12px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 50px rgba(0,0,0,0.35);
    z-index: 1000;
}


/* ===================== FLOW ICON BACKGROUND ===================== */
.aside-container::before,
.aside-container::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.flow-icon {
    position: absolute;
    color: rgba(255,255,255,0.07);
    animation: floatY 18s linear infinite;
}

.flow-icon.truck { font-size: 5rem; left: -20px; top: 15%; }
.flow-icon.route { font-size: 4rem; right: -10px; top: 40%; animation-delay: 3s; }
.flow-icon.box   { font-size: 4.5rem; left: 40%; bottom: 10%; animation-delay: 6s; }

@keyframes floatY {
    0% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-25px) rotate(6deg); }
    100% { transform: translateY(0) rotate(0deg); }
}

@media (max-width: 1024px) {
    .aside-container {
        left: -320px;   /* hide sidebar */
        transition: all 0.3s ease;
    }

    .aside-container.show {
        left: 20px;
    }
}
/* ===================== BRAND ===================== */
.brand-section {
    position: relative;
    z-index: 2;
}

.brand-section span {
    color: rgba(255,255,255,.55);
}

.brand-section h3 {
    background: linear-gradient(to right,#A1E3F9,#FFFFFF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* ===================== NAV ITEMS ===================== */
.nav-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 0.9rem 1.25rem;
    margin-bottom: 6px;
    color: #E5E7EB;
    text-decoration: none !important;
    border-radius: 1.6rem;
    transition: all .35s ease;
    position: relative;
    z-index: 2;
}

.icon-square {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(6px);
}

.nav-label {
    font-size: .82rem;
    font-weight: 900;
    letter-spacing: .08em;
}

/* ACTIVE */
.nav-item.active {
    background: linear-gradient(120deg,#3B82F6,#2563EB);
    box-shadow: 0 15px 25px rgba(37,99,235,.45);
    color: #fff;
}

.nav-item.active .icon-square {
    background: rgba(255,255,255,.25);
}

/* HOVER */
.nav-item:hover:not(.active) {
    background: rgba(255,255,255,.08);
    transform: translateX(6px);
    color: #A1E3F9;
}

/* ===================== FOOTER ===================== */
.aside-footer {
    position: relative;
    z-index: 2;
    background: rgba(255,255,255,.08);
    backdrop-filter: blur(10px);
    border-radius: 2rem;
}

.main-wrapper {
    margin-left: 320px;
    padding: 20px;
    width: 100%;
}

/* 🔥 Tablet & Mobile */
@media (max-width: 1024px) {
    .main-wrapper {
        margin-left: 0;
        padding: 16px;
    }
}
/* Hide scrollbar but allow scroll */
.scrollbar-hide::-webkit-scrollbar {
    width: 4px;
}
.scrollbar-hide::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 10px;
}
.scrollbar-hide::-webkit-scrollbar-track {
    background: transparent;
}
</style>

<!-- FLOW ICONS -->
<i class="fa fa-truck-fast flow-icon truck"></i>
<i class="fa fa-route flow-icon route"></i>
<i class="fa fa-boxes-stacked flow-icon box"></i>

<aside class="aside-container">

    <!-- BRAND -->
    <div class="brand-section text-center flex flex-col items-center pt-4 mb-10">
        <span class="text-[9px] font-black uppercase tracking-[0.45em] mb-2">Logistics ERP</span>
        <h3 class="font-black text-xl tracking-tight">SMART SYSTEM</h3>
        <div class="w-12 h-[3px] bg-gradient-to-r from-sky-300 to-blue-500 mt-4 rounded-full"></div>
    </div>

    <!-- NAV -->
      <nav class="flex-1 space-y-1 px-2 overflow-y-auto scrollbar-hide">
    <?php
    echo sidebar_admin_link('admin_dashboard.php','fa-th-large','DASHBOARD','#A1E3F9','dashboard',$active_page);
    echo sidebar_admin_link('inventory.php','fa-boxes-stacked','INVENTORY','#FDE68A','inventory',$active_page);
    echo sidebar_admin_link('branch_control.php','fa-network-wired','BRANCHES','#BFDBFE','branches',$active_page);
    echo sidebar_admin_link('suppliers.php','fa-truck-ramp-box','SUPPLIERS','#FDBA74','suppliers',$active_page);
    echo sidebar_admin_link('procurement_report.php','fa-truck-loading','PROCUREMENT','#FCA5A5','procurement',$active_page);
    echo sidebar_admin_link('manage_requests.php','fa-clipboard-check','REQUESTS','#FCA5A5','requests',$active_page);
    echo sidebar_admin_link('stock_transfer.php','fa-right-left','TRANSFERS','#C7D2FE','transfer',$active_page);
    echo sidebar_admin_link('order_stock.php','fa-cart-plus','ORDERS','#6EE7B7','orders',$active_page);
    echo sidebar_admin_link('shipment_tracking.php','fa-shipping-fast','TRACKING','#34D399','shipments',$active_page);
    echo sidebar_admin_link('manage_users.php','fa-user-shield','USER ROLES','#FBCFE8','users',$active_page);
    echo sidebar_admin_link('audit_logs.php','fa-clipboard-list','AUDIT LOGS','#86EFAC','audit_logs',$active_page);
    ?>
</nav>

<div class="aside-footer mx-2 p-3 bg-white/5 rounded-3xl flex items-center justify-between mt-6 border border-white/5">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-400/20 flex items-center justify-center text-emerald-300">
            <i class="fa fa-user-gear"></i>
        </div>
        <div class="flex flex-col">
            <span class="text-[10px] font-black text-white uppercase leading-none mb-1">
                <?php echo $_SESSION['username']; ?>
            </span>
            <span class="text-[8px] text-emerald-300 font-bold uppercase tracking-widest">
                Administrator
            </span>
        </div>
    </div>
    <a href="../logout.php" class="w-8 h-8 rounded-lg bg-red-500/20 flex items-center justify-center text-red-400 hover:bg-red-500 hover:text-white transition-all">
        <i class="fa fa-power-off text-xs"></i>
    </a>
</div>
</aside>
<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.aside-container');
        sidebar.classList.toggle('show');
    }


    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.aside-container');
        const mobileBtn = document.querySelector('[onclick="toggleSidebar()"]');
        
        if (sidebar.classList.contains('show') && 
            !sidebar.contains(event.target) && 
            !mobileBtn.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    });
</script>