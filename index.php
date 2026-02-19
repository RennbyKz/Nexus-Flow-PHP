<?php
session_start();
// အကယ်၍ Login ဝင်ပြီးသားဆိုရင် Dashboard ကို တိုက်ရိုက်ပို့မည်
if (isset($_SESSION['user_id'])) {
    header("Location: modules/" . strtolower($_SESSION['role']) . "_dashboard.php");
    exit();
}

$page_title = 'OMNI INVENTORY LOGIN';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OmniInventory | Secure Access</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body class="min-h-screen bg-[#f8fafc] flex flex-col relative overflow-hidden font-sans">

    <?php include 'includes/header.php'; ?>

    <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-100 rounded-full blur-[120px] opacity-50"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-100 rounded-full blur-[120px] opacity-50"></div>
        
        <i class="fa-solid fa-truck-fast absolute top-[20%] right-[15%] text-slate-200 text-6xl animate-float opacity-40"></i>
        <i class="fa-solid fa-box-open absolute bottom-[25%] left-[10%] text-slate-200 text-7xl animate-float opacity-40" style="animation-delay: 2s;"></i>
        <i class="fa-solid fa-warehouse absolute top-[40%] left-[5%] text-slate-200 text-8xl animate-float opacity-40" style="animation-delay: 4s;"></i>
    </div>

    <div class="relative z-10 flex-1 flex items-center justify-center px-6">
        
        <div class="w-full max-w-5xl glass-card rounded-[3rem] shadow-[0_32px_64px_-12px_rgba(0,0,0,0.1)] overflow-hidden grid grid-cols-1 lg:grid-cols-2 min-h-[600px]">
            
            <div class="bg-slate-900 p-12 lg:p-16 flex flex-col justify-between text-white relative overflow-hidden">
                <div class="absolute inset-0 opacity-10 pointer-events-none">
                    <div class="absolute inset-0" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 30px 30px;"></div>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-10">
                       <div class="relative group">
                            <img src="/SmartLogistics/assets/img/logo.png" alt="NexusFlow Logo" 
                                class="w-14 h-14 object-contain filter drop-shadow-[0_0_10px_rgba(37,99,235,0.25)] group-hover:scale-110 transition-transform duration-500">
                            
                            <div class="absolute inset-0 bg-blue-500 rounded-full blur-[30px] opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>
                        </div>
                        <div>
                            <h2 class="text-3xl font-black italic tracking-tighter leading-none">NexusFlow</h2>
                            <p class="text-[10px] font-black uppercase tracking-[0.4em] text-blue-400 mt-1">Inventory</p>
                        </div>
                    </div>

                    <h1 class="text-4xl font-black leading-[1.1] mb-6 tracking-tight">
                        Next-Gen <br> Logistics Control.
                    </h1>
                    
                    <p class="text-slate-400 text-lg leading-relaxed font-medium max-w-xs">
                        Monitor stock levels, track shipments, and manage multi-branch operations in real-time.
                    </p>

                    <div class="mt-12 space-y-4">
                        <div class="flex items-center gap-3 text-slate-300">
                            <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-[10px]"><i class="fas fa-check"></i></div>
                            <span class="text-xs font-bold uppercase tracking-widest">Multi-Branch Sync</span>
                        </div>
                        <div class="flex items-center gap-3 text-slate-300">
                            <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-[10px]"><i class="fas fa-check"></i></div>
                            <span class="text-xs font-bold uppercase tracking-widest">Supplier Portal</span>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 pt-10 border-t border-slate-800">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500">
                        System Version 2.4.0 • Secured by OMNI
                    </p>
                </div>
            </div>

            <div class="p-12 lg:p-20 flex flex-col justify-center bg-white">
                <div class="mb-10">
                    <h2 class="text-3xl font-black text-slate-800 italic uppercase tracking-tight">Authorized Access</h2>
                    <p class="text-slate-400 text-sm mt-2 font-bold uppercase tracking-widest">Please enter your credentials</p>
                </div>

                <form action="auth.php" method="POST" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-2">Login Identity</label>
                        <div class="relative">
                            <i class="fa-solid fa-user-shield absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 transition-colors"></i>
                            <input 
                                type="text" 
                                name="username" 
                                required 
                                placeholder="Username" 
                                class="w-full pl-14 pr-6 py-5 bg-slate-50 border border-slate-100 rounded-[1.5rem] outline-none focus:ring-4 focus:ring-blue-500/5 focus:border-blue-500 focus:bg-white transition-all duration-300 font-bold text-slate-700"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-2">Secure Password</label>
                        <div class="relative">
                            <i class="fa-solid fa-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 transition-colors"></i>
                            <input 
                                type="password" 
                                name="password" 
                                required 
                                placeholder="••••••••" 
                                class="w-full pl-14 pr-6 py-5 bg-slate-50 border border-slate-100 rounded-[1.5rem] outline-none focus:ring-4 focus:ring-blue-500/5 focus:border-blue-500 focus:bg-white transition-all duration-300 font-bold text-slate-700"
                            >
                        </div>
                    </div>

                    <div class="flex justify-between items-center px-2">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-slate-600 transition">Keep me signed in</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-slate-900 text-white py-6 rounded-[2rem] font-black text-sm uppercase tracking-[0.3em] hover:bg-blue-600 hover:-translate-y-1 transition-all duration-300 shadow-xl shadow-slate-200 active:scale-95">
                        <i class="fa-solid fa-right-to-bracket mr-2"></i> Initializing Session
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out'): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: '<span class="italic font-black uppercase tracking-tighter">Session Terminated</span>',
            text: 'You have been securely logged out of the system.',
            background: '#ffffff',
            confirmButtonColor: '#0f172a',
            customClass: {
                popup: 'rounded-[3rem] p-10',
                title: 'text-2xl font-black italic'
            }
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    </script>
    <?php endif; ?>

</body>
</html>