<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | BCS Katsina</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-katsina-green { background-color: #064e3b; }
        .text-katsina-gold { color: #eab308; }
        .border-katsina-gold { border-color: #eab308; }
    </style>
</head>
<body class="bg-gray-50 antialiased">

    <div class="flex min-h-screen">
        <div class="flex w-full flex-col justify-center px-8 py-8 lg:w-1/2 lg:px-24 bg-white shadow-2xl z-10">
            <div class="mx-auto w-full max-w-md">
                
                <div class="mb-10 text-center lg:text-left">
                    <h1 class="text-4xl font-black tracking-tighter text-emerald-900 uppercase leading-none">
                        Budget Control <span class="text-yellow-600">System</span>
                    </h1>
                    <p class="mt-3 text-sm text-gray-500 font-medium tracking-wide uppercase opacity-75">
                        Katsina State Government
                    </p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 rounded-lg bg-red-50 p-4 border-l-4 border-red-600 shadow-sm">
                        <p class="text-sm text-red-700 font-bold mb-1">Access Denied:</p>
                        <ul class="text-xs text-red-600 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-black text-emerald-900 mb-2 uppercase tracking-widest">Email Address/ID</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="officer@budget.com"
                            class="w-full px-4 py-4 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all shadow-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-emerald-900 mb-2 uppercase tracking-widest">Password</label>
                        <input type="password" name="password" required
                            placeholder="••••••••"
                            class="w-full px-4 py-4 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all shadow-sm">
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <label class="flex items-center text-sm font-medium text-gray-600 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 mr-2"> 
                            Keep me signed in
                        </label>
                        <a href="#" class="text-sm font-bold text-emerald-700 hover:text-emerald-900 transition-colors">Forgot Password?</a>
                    </div>

                    <button type="submit" 
                        class="w-full bg-emerald-800 hover:bg-emerald-900 text-white font-black py-4 px-4 rounded-xl shadow-xl transform active:scale-[0.98] transition-all uppercase tracking-widest">
                        Enter Dashboard
                    </button>
                </form>

                <div class="mt-16 pt-8 border-t border-gray-100 text-center">
                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em]">
                        Ministry of Budget and Economic Planning <br>
                        <span class="text-emerald-800 opacity-50">Katsina State, Nigeria</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="hidden lg:flex w-1/2 bg-katsina-green items-center justify-center relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/pinstriped-suit.png')]"></div>
            
            <div class="relative z-10 text-center text-white px-10">
                <div class="mb-12 group flex items-center justify-center">
                    
                    {{-- THE DESIGN CHANGE: The Round Container with White Outline --}}
                    <div class="h-60 w-60 rounded-full border-4 border-white shadow-[0_35px_100px_rgba(0,0,0,0.6)] flex items-center justify-center overflow-hidden bg-white/5 group-hover:bg-white/10 transition-colors transform group-hover:scale-105 transition-transform duration-700">
                        {{-- The Crest image inside the circle --}}
                        <img src="{{ asset('assets/images/katsina-crest.png') }}" 
                             alt="KATSINA CREST" 
                             class="h-72 w-auto mx-auto object-contain">
                    </div>
                </div>
                
                <h2 class="text-6xl font-black tracking-tighter uppercase leading-none text-white">
                    KATSINA <span class="text-katsina-gold">STATE</span>
                </h2>
                <p class="mt-6 text-2xl font-light tracking-[0.5em] text-emerald-100 uppercase italic opacity-70">
                    Home of Hospitality
                </p>
                <div class="mt-10 h-1.5 w-32 bg-katsina-gold mx-auto rounded-full"></div>
            </div>
            
            <div class="absolute bottom-0 h-4 w-full bg-katsina-gold shadow-[0_-10px_30px_rgba(0,0,0,0.3)]"></div>
        </div>
    </div>

</body>
</html>