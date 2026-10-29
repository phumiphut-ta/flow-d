<!DOCTYPE html>
<html lang="th" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Flow-D' }} - ระบบบริหารเส้นทางและติดตามหนังสือ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        [x-cloak] { display: none !important; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col text-slate-800 antialiased" x-data="{
    recoveryModal: false,
    recoveryActionUrl: '',
    recoveryTitle: '',
    recoveryReason: '',
    recoverySubmitting: false,
    openRecovery(url, title) {
        this.recoveryActionUrl = url;
        this.recoveryTitle = title;
        this.recoveryReason = '';
        this.recoverySubmitting = false;
        this.recoveryModal = true;
        this.$nextTick(() => {
            const input = document.getElementById('recovery_reason_input');
            if (input) { input.focus(); }
        });
    }
}" @keydown.window="
    if ($event.altKey && ($event.key === 'n' || $event.key === 'N')) {
        $event.preventDefault();
        const intakeInput = document.getElementById('intake_doc_number');
        if (intakeInput) { intakeInput.focus(); intakeInput.select(); }
    } else if ($event.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
        $event.preventDefault();
        const searchInput = document.getElementById('quick_search_input');
        if (searchInput) { searchInput.focus(); searchInput.select(); }
    }
">
    <!-- Top Bar: Header & Navigation -->
    <header class="bg-slate-900 text-white border-b border-slate-800 shadow-md shrink-0">
        <div class="px-5 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-8">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 text-white hover:text-blue-400 transition">
                    <span class="text-3xl">🖥️</span>
                    <div>
                        <span class="font-extrabold text-2xl tracking-tight bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-transparent">Flow-D</span>
                        <span class="text-xs text-slate-300 block -mt-0.5 font-medium">ระบบรับ-ส่งและสมุดคุมหนังสือ</span>
                    </div>
                </a>

                <!-- Navigation Tabs with Larger Text & Padding -->
                <nav class="hidden md:flex items-center space-x-2">
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg text-base font-bold transition flex items-center space-x-1.5 {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span>📋</span>
                        <span>งานระหว่างทาง</span>
                    </a>
                    <a href="{{ route('history.index') }}" class="px-4 py-2 rounded-lg text-base font-bold transition flex items-center space-x-1.5 {{ request()->routeIs('history.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span>📖</span>
                        <span>สมุดคุม & ประวัติ</span>
                    </a>
                    <a href="{{ route('reports.index') }}" class="px-4 py-2 rounded-lg text-base font-bold transition flex items-center space-x-1.5 {{ request()->routeIs('reports.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span>📊</span>
                        <span>รายงาน</span>
                    </a>
                    <a href="{{ route('liff.tracking') }}" target="_blank" class="px-4 py-2 rounded-lg text-base font-bold text-emerald-300 hover:bg-slate-800 hover:text-emerald-200 transition flex items-center space-x-1.5">
                        <span>📱</span>
                        <span>ค้นหาสถานะหนังสือ</span>
                    </a>

                    @if(auth()->check() && auth()->user()->isAdmin())
                    <!-- Admin Dropdown Menu -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" type="button" class="px-4 py-2 rounded-lg text-base font-bold transition flex items-center space-x-1.5 {{ request()->routeIs('admin.*') ? 'bg-purple-700 text-white shadow-sm' : 'text-purple-300 hover:bg-slate-800 hover:text-white' }}">
                            <span>⚙️ ผู้ดูแลระบบ</span>
                            <svg class="w-5 h-5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute left-0 mt-1 w-56 bg-white rounded-lg shadow-xl py-1.5 border border-slate-200 z-50 text-slate-800">
                            <a href="{{ route('admin.routes.index') }}" class="block px-4 py-2.5 text-base hover:bg-slate-100 {{ request()->routeIs('admin.routes.*') ? 'font-bold text-blue-600 bg-blue-50' : '' }}">
                                🛣️ จัดการเส้นทางเดินเอกสาร
                            </a>
                            <a href="{{ route('admin.users.index') }}" class="block px-4 py-2.5 text-base hover:bg-slate-100 {{ request()->routeIs('admin.users.*') ? 'font-bold text-blue-600 bg-blue-50' : '' }}">
                                👥 จัดการผู้ใช้งาน
                            </a>
                            <a href="{{ route('admin.logs.index') }}" class="block px-4 py-2.5 text-base hover:bg-slate-100 {{ request()->routeIs('admin.logs.*') ? 'font-bold text-blue-600 bg-blue-50' : '' }}">
                                📜 Activity & Recovery Logs
                            </a>
                        </div>
                    </div>
                    @endif
                </nav>
            </div>

            <!-- Right info: Device ID, Buddhist Date, User & Shortcuts -->
            <div class="flex items-center space-x-4 text-sm">
                <!-- Registered Device Indicator -->
                @php
                    $device = request()->attributes->get('registered_device');
                @endphp
                @if($device)
                <div class="hidden lg:flex items-center bg-slate-800 border border-slate-700 px-3 py-1.5 rounded-lg text-slate-300" title="Device UUID: {{ $device->device_token }} (IP: {{ request()->ip() }})">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 mr-2 animate-pulse"></span>
                    <span>เครื่อง: <strong class="text-white font-bold">{{ $device->device_name ?: substr($device->device_token, 0, 8) }}</strong></span>
                </div>
                @endif

                <!-- Thai Buddhist Era Date -->
                <div class="hidden sm:block text-slate-200 bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700 font-semibold">
                    📅 {{ \App\Support\ThaiDate::formatThaiReadable(now()) }}
                </div>

                <!-- User profile / logout -->
                @auth
                <div class="flex items-center space-x-3 border-l border-slate-700 pl-4">
                    <div class="text-right leading-tight">
                        <span class="font-bold text-white text-base block">{{ auth()->user()->name }}</span>
                        <span class="text-xs uppercase font-extrabold {{ auth()->user()->isAdmin() ? 'text-purple-400' : 'text-slate-400' }}">
                            {{ auth()->user()->isAdmin() ? '★ Admin' : 'Staff' }}
                        </span>
                    </div>
                    <a href="{{ route('profile.show') }}" class="text-slate-300 hover:text-white p-2 rounded-lg hover:bg-slate-800 text-lg" title="เปลี่ยนรหัสผ่าน">
                        🔑
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-red-400 hover:text-red-300 p-2 rounded-lg hover:bg-slate-800 text-lg cursor-pointer" title="ออกจากระบบ" onclick="return confirm('ยืนยันออกจากระบบ?')">
                            🚪
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </div>

        <!-- Quick Keyboard Shortcuts Helper Bar -->
        <div class="bg-slate-950 px-5 py-1.5 text-xs text-slate-300 flex items-center justify-between border-t border-slate-800">
            <div class="flex items-center space-x-5">
                <span class="font-bold text-slate-400">คีย์ลัด PC Desktop:</span>
                <span class="inline-flex items-center font-medium"><kbd class="bg-slate-800 text-amber-300 px-2 py-0.5 rounded border border-slate-700 font-mono text-xs font-bold mr-1.5">Alt + N</kbd> รับหนังสือด่วน</span>
                <span class="inline-flex items-center font-medium"><kbd class="bg-slate-800 text-amber-300 px-2 py-0.5 rounded border border-slate-700 font-mono text-xs font-bold mr-1.5">/</kbd> ค้นหาเลขรับ</span>
                <span class="inline-flex items-center font-medium"><kbd class="bg-slate-800 text-amber-300 px-2 py-0.5 rounded border border-slate-700 font-mono text-xs font-bold mr-1.5">Enter</kbd> บันทึกฟอร์มรับ</span>
            </div>
        </div>
    </header>

    <!-- Global Alerts -->
    <div class="px-4 pt-3 shrink-0">
        @if(session('success'))
        <div class="mb-2 bg-emerald-50 border-l-4 border-emerald-500 p-3 rounded-r text-sm text-emerald-800 flex items-center justify-between shadow-xs" x-data="{ show: true }" x-show="show">
            <div class="flex items-center space-x-2">
                <span class="text-emerald-600 text-base">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-2 bg-red-50 border-l-4 border-red-500 p-3 rounded-r text-sm text-red-800 flex items-center justify-between shadow-xs" x-data="{ show: true }" x-show="show">
            <div class="flex items-center space-x-2">
                <span class="text-red-600 text-base">⚠</span>
                <span>{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-red-500 hover:text-red-700 font-bold">&times;</button>
        </div>
        @endif

        @if(isset($errors) && $errors->any())
        <div class="mb-2 bg-amber-50 border-l-4 border-amber-500 p-3 rounded-r text-sm text-amber-800 flex items-center justify-between shadow-xs" x-data="{ show: true }" x-show="show">
            <div>
                <span class="font-bold">กรุณาตรวจสอบข้อผิดพลาด:</span>
                <ul class="list-disc list-inside mt-1 text-xs">
                    @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            <button @click="show = false" class="text-amber-500 hover:text-amber-700 font-bold">&times;</button>
        </div>
        @endif
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-auto p-4">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Admin Recovery Modal (Same-day Single Undo Dialog with Mandatory Reason) -->
    <div x-show="recoveryModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop (Dark overlay behind dialog) -->
        <div x-show="recoveryModal" x-transition.opacity class="fixed inset-0 bg-slate-950/70 transition-opacity" @click="if(!recoverySubmitting) recoveryModal = false"></div>

        <!-- Centering Wrapper -->
        <div class="fixed inset-0 z-10 overflow-y-auto flex min-h-full items-center justify-center p-4 text-center sm:p-0 pointer-events-none">
            <!-- Modal Card (Clear, Crisp, Fully Clickable) -->
            <div x-show="recoveryModal" x-transition.scale.origin-center
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border-2 border-purple-400 pointer-events-auto z-20">
                <form :action="recoveryActionUrl" method="POST" @submit="if(recoverySubmitting || recoveryReason.trim() === '') { $event.preventDefault(); return false; } recoverySubmitting = true;">
                    @csrf
                    <div class="bg-gradient-to-r from-purple-900 to-indigo-900 px-6 py-4 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <span class="text-2xl">🛡️</span>
                            <h3 class="text-lg font-black tracking-tight" x-text="recoveryTitle">กู้คืนสถานะหนังสือ (Admin Recovery)</h3>
                        </div>
                        <span class="text-xs bg-purple-700/90 text-purple-100 px-2.5 py-1 rounded-md font-bold font-mono border border-purple-600">เฉพาะวันนี้</span>
                    </div>

                    <div class="bg-white p-6 space-y-4">
                        <div class="p-4 bg-amber-50 border-2 border-amber-200 rounded-xl text-sm text-amber-950">
                            <strong class="text-base font-black block mb-1">⚠️ เงื่อนไขความปลอดภัย (Strict Policy):</strong>
                            <ul class="list-disc list-inside space-y-1 text-xs md:text-sm text-amber-900 font-medium">
                                <li>กู้คืนได้เฉพาะ <strong>1 ขั้นตอนล่าสุด</strong> เท่านั้น</li>
                                <li>ต้องเป็นรายการที่ทำ <strong>ภายในวันเดียวกัน (วันนี้)</strong></li>
                                <li>บันทึกประวัติและชื่อผู้กู้คืนลง Audit Log เสมอ</li>
                            </ul>
                        </div>

                        <div>
                            <label for="recovery_reason_input" class="block text-base font-extrabold text-slate-900 mb-1.5">
                                ระบุเหตุผลในการกู้คืน <span class="text-red-600 font-black">* (จำเป็น)</span>
                            </label>
                            <textarea id="recovery_reason_input" name="reason" rows="3" required x-model="recoveryReason"
                                class="mt-1 block w-full rounded-xl border-2 border-slate-300 shadow-sm focus:border-purple-600 focus:ring-2 focus:ring-purple-200 text-base p-3.5 text-slate-900 font-medium bg-white"
                                placeholder="เช่น เจ้าหน้าที่หยิบแฟ้มผิดเล่มมากดส่งออกหนังสือ, ต้องการดึงกลับมาแก้ไขขั้นตอน"></textarea>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-3">
                        <button type="submit" :disabled="recoverySubmitting || recoveryReason.trim() === ''"
                            class="w-full inline-flex justify-center rounded-xl shadow-md px-6 py-3 bg-purple-700 text-base font-extrabold text-white hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition">
                            <span x-show="!recoverySubmitting">✓ ยืนยันการกู้คืนสถานะ</span>
                            <span x-show="recoverySubmitting" x-cloak class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                ⏳ กำลังกู้คืน...
                            </span>
                        </button>
                        <button type="button" @click="recoveryModal = false" :disabled="recoverySubmitting"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border-2 border-slate-300 shadow-xs px-5 py-3 bg-white text-base font-bold text-slate-700 hover:bg-slate-100 sm:mt-0 sm:w-auto cursor-pointer transition">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

