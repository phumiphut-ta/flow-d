<!DOCTYPE html>
<html lang="th" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Flow-D (ระบบติดตามหนังสือเข้า-ออก)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4">
    <div class="w-full max-w-lg" x-data="{ submitting: false }">
        <!-- Logo and App Title -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gradient-to-tr from-blue-600 to-emerald-500 shadow-xl shadow-blue-500/20 mb-3 text-4xl">
                🖥️
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight">Flow-D</h1>
            <p class="text-slate-300 text-base mt-1 font-semibold">ระบบบริหารเส้นทางและติดตามหนังสือเข้า-ออก</p>
        </div>

        <!-- Login Card (Senior-Friendly Big Text & Inputs) -->
        <div class="bg-slate-800 border-2 border-slate-700 rounded-3xl shadow-2xl p-8">
            <h2 class="text-xl font-black text-white mb-5 flex items-center space-x-2">
                <span>🔐</span>
                <span>ลงชื่อเข้าสู่ระบบ</span>
            </h2>

            @if($errors->any())
            <div class="mb-5 bg-red-950/80 border-2 border-red-700 text-red-200 text-sm p-4 rounded-xl font-bold">
                @foreach($errors->all() as $err)
                    <p>• {{ $err }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" @submit="if(submitting) { $event.preventDefault(); return false; } submitting = true;">
                @csrf

                <!-- Username (Large Input) -->
                <div class="mb-5">
                    <label for="username" class="block text-sm font-bold text-slate-200 uppercase tracking-wider mb-1.5">
                        ชื่อผู้ใช้งาน (Username)
                    </label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                        class="w-full px-4 py-3 rounded-xl bg-slate-900 border-2 border-slate-600 text-white placeholder-slate-500 text-base font-bold focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50"
                        placeholder="เช่น admin หรือ staff">
                </div>

                <!-- Password (Large Input) -->
                <div class="mb-5">
                    <label for="password" class="block text-sm font-bold text-slate-200 uppercase tracking-wider mb-1.5">
                        รหัสผ่าน (Password)
                    </label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-3 rounded-xl bg-slate-900 border-2 border-slate-600 text-white placeholder-slate-500 text-base font-bold focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/50"
                        placeholder="••••••••">
                </div>

                <!-- Remember Me (Big Checkbox) -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center text-sm font-bold text-slate-200 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" class="w-5 h-5 rounded bg-slate-900 border-2 border-slate-600 text-blue-500 focus:ring-0 mr-2.5">
                        <span>จดจำการเข้าสู่ระบบ (Remember Me)</span>
                    </label>
                </div>

                <!-- Large Submit Button -->
                <button type="submit" :disabled="submitting"
                    class="w-full py-3.5 px-5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-lg transition shadow-lg shadow-blue-600/30 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                    <span x-show="!submitting">เข้าสู่ระบบ</span>
                    <span x-show="submitting" x-cloak class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2.5 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        ⏳ กำลังเข้าสู่ระบบ...
                    </span>
                </button>
            </form>

            <!-- Public LIFF Companion Link -->
            <div class="mt-6 pt-5 border-t border-slate-700 text-center">
                <a href="{{ route('liff.tracking') }}" class="text-sm font-bold text-emerald-400 hover:text-emerald-300 inline-flex items-center space-x-1">
                    <span>📱 หน้าจอติดตามสถานะ &rarr;</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
