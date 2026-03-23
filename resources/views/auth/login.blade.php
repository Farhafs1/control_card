<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Budget Master</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .serif { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="bg-[#F8F9F5] flex items-center justify-center min-h-screen relative overflow-hidden">
    
    <div class="absolute top-0 left-0 w-full h-1 bg-emerald-800"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-100 rounded-full blur-3xl opacity-50"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-slate-200 rounded-full blur-3xl opacity-50"></div>

    <div class="w-full max-w-[440px] z-10 p-6">
        <div class="bg-white rounded-[3rem] p-12 shadow-[0_40px_100px_-20px_rgba(0,0,0,0.05)] border border-white relative">
            
            <div class="flex justify-center mb-10">
                <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center border border-emerald-100">
                    <svg class="w-10 h-10 text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A10.003 10.003 0 0012 3v1m0 0V3m0 1c3.517 0 6.799 1.009 9.571 2.753m-2.04 3.44l-.09-.054A10.003 10.003 0 0021 12h-1m1 0h1m-1 0a10.003 10.003 0 01-2.753 6.819l-.054.09M12 21v-1m0 1v1m0-1c-3.517 0-6.799-1.009-9.571-2.753m2.04-3.44l.09.054A10.003 10.003 0 013 12h1m-1 0H3m1 0a10.003 10.003 0 012.753-6.819l.054-.09" />
                    </svg>
                </div>
            </div>

            <div class="mb-10 text-center">
                <h2 class="serif text-3xl text-slate-900 tracking-tight mb-2">Budget Master</h2>
                <div class="flex items-center justify-center gap-2">
                    <span class="h-[1px] w-4 bg-emerald-800 opacity-30"></span>
                    <p class="text-emerald-800 text-[10px] font-black uppercase tracking-[0.2em]">Katsina State Ministry of Finance</p>
                    <span class="h-[1px] w-4 bg-emerald-800 opacity-30"></span>
                </div>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf
                
                @if ($errors->any())
                    <div class="bg-red-50 text-red-600 text-[10px] font-bold p-4 rounded-2xl mb-4 uppercase tracking-wider text-center border border-red-100">
                        Invalid credentials provided
                    </div>
                @endif

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 block">Staff Portal Access</label>
                    <div class="relative">
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="Email Address" 
                               class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-800/5 focus:border-emerald-800 transition-all placeholder:text-slate-300">
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="relative">
                        <input type="password" name="password" required placeholder="Security Code" 
                               class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-800/5 focus:border-emerald-800 transition-all placeholder:text-slate-300">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-emerald-900 hover:bg-emerald-800 text-emerald-50 font-black py-4 rounded-2xl uppercase text-[11px] tracking-[0.2em] transition-all shadow-xl shadow-emerald-900/20 active:scale-[0.98]">
                        Secure Sign In
                    </button>
                </div>
            </form>

            <div class="mt-12 text-center">
                <p class="text-slate-300 text-[9px] font-bold uppercase tracking-widest">
                    Government Information System &copy; 2026
                </p>
            </div>
        </div>
    </div>
</body>
</html>