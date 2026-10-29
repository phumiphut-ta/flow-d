<!DOCTYPE html>
<html lang="th" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $document ? 'ติดตามเลขรับ ' . $document->doc_number : 'ติดตามสถานะหนังสือ' }} - Flow-D Tracking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        [x-cloak] { display: none !important; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col text-slate-800 antialiased bg-slate-50">
    <!-- Mobile Top Navigation Header -->
    <header class="bg-gradient-to-r from-blue-900 via-indigo-950 to-slate-900 text-white shadow-md sticky top-0 z-30 px-4 py-3.5">
        <div class="max-w-md mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <span class="text-3xl">📦</span>
                <div>
                    <h1 class="font-black text-lg tracking-tight leading-tight">Flow-D Tracking</h1>
                    <p class="text-xs text-blue-200">ระบบติดตามสถานะหนังสือ (LINE Companion)</p>
                </div>
            </div>
            @auth
            <a href="{{ route('dashboard') }}" class="text-sm font-bold text-blue-200 hover:text-white bg-white/10 px-3 py-1.5 rounded-lg">
                🖥️ หน้า PC
            </a>
            @else
            <a href="{{ route('login') }}" class="text-sm font-bold text-blue-200 hover:text-white bg-white/10 px-3 py-1.5 rounded-lg">
                🔐 เข้าสู่ระบบ
            </a>
            @endauth
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-md w-full mx-auto p-4 space-y-4">
        <!-- Search Card (Large Inputs) -->
        <div class="bg-white rounded-2xl shadow-sm border-2 border-slate-200 p-4">
            <form method="GET" action="{{ route('liff.tracking') }}" class="flex items-center space-x-2">
                <div class="relative flex-1">
                    <input type="text" name="doc_number" value="{{ $docNumber }}" required
                        placeholder="พิมพ์เลขรับ เช่น 123/69"
                        class="w-full pl-10 pr-3 py-3 rounded-xl border-2 border-blue-200 text-base font-mono font-black focus:border-blue-600 focus:outline-none placeholder-slate-400">
                    <span class="absolute left-3.5 top-3.5 text-slate-400 text-base">🔍</span>
                </div>
                <button type="submit" class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-base font-black shadow-sm transition shrink-0 cursor-pointer">
                    ค้นหา
                </button>
            </form>
        </div>

        @if($docNumber !== '' && ! $document)
        <!-- Not Found Alert -->
        <div class="bg-white rounded-2xl border-2 border-red-200 p-6 text-center shadow-xs">
            <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-3xl mx-auto mb-2 font-bold">
                ✕
            </div>
            <h3 class="font-extrabold text-slate-900 text-base">ไม่พบข้อมูลหนังสือเลขรับนี้</h3>
            <p class="text-sm text-slate-500 mt-1">กรุณาตรวจสอบเลขรับหนังสือ <strong>"{{ $docNumber }}"</strong> และลองใหม่อีกครั้ง</p>
        </div>
        @endif

        @if($document && $selectedRound)
        <!-- Document Result Card (Senior-Friendly Big Text) -->
        <div class="bg-white rounded-2xl shadow-md border-2 border-slate-200 overflow-hidden" x-data="{ currentRoundId: {{ $selectedRound->id }} }">
            <!-- Header: Doc Number & Date -->
            <div class="p-5 border-b border-slate-100 bg-gradient-to-b from-blue-50/70 to-white">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-sm text-slate-500 font-bold">สถานะหนังสือเลขรับ</span>
                    @if($document->current_status === 'completed')
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                            ✓ ลงระบบเสร็จสิ้น
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-900 border border-blue-300 animate-pulse">
                            🟢 กำลังเดินเรื่อง
                        </span>
                    @endif
                </div>

                <div class="text-3xl md:text-4xl font-black text-blue-950 font-mono tracking-tight">
                    {{ $document->doc_number }}
                </div>
                <div class="text-sm text-slate-700 mt-1.5 flex items-center space-x-1 font-semibold">
                    <span>📅 ลงวันที่ในหนังสือ:</span>
                    <strong class="text-slate-900 text-base">{{ $document->doc_date_readable }}</strong>
                    <span class="text-slate-500">({{ $document->doc_date_thai }})</span>
                </div>
            </div>

            <!-- Multi-round Tabs -->
            @if($document->movements->count() > 1)
            <div class="px-4 pt-3 pb-1 border-b border-slate-100 bg-slate-50">
                <div class="flex space-x-2 p-1.5 bg-slate-200 rounded-xl overflow-x-auto text-sm">
                    @foreach($document->movements as $m)
                    <a href="{{ route('liff.tracking', ['doc_number' => $document->doc_number, 'round' => $m->round_number]) }}"
                        class="flex-1 py-2 px-3 rounded-lg text-center font-black whitespace-nowrap transition {{ $selectedRound->id === $m->id ? 'bg-white text-blue-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        รอบที่ {{ $m->round_number }}
                        @if($m->is_returned)
                            <span class="text-slate-400">✓</span>
                        @else
                            <span class="text-emerald-600">🟢</span>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Selected Round Details -->
            <div class="p-5 space-y-5">
                <div class="bg-blue-50 border-2 border-blue-200 rounded-2xl p-4 text-sm">
                    <div class="text-xs text-blue-800 font-extrabold uppercase">เส้นทางรอบนี้:</div>
                    <div class="font-black text-blue-950 text-base mt-0.5">{{ $selectedRound->route->route_name }}</div>
                    <div class="text-xs text-slate-700 mt-2.5 pt-2 border-t border-blue-200/70 space-y-1 font-semibold">
                        <div class="flex items-center justify-between">
                            <span>📥 วันเวลารับเข้า: <strong class="text-slate-900">{{ $selectedRound->in_datetime_thai }}</strong></span>
                            @if(! $selectedRound->is_returned)
                                <span class="text-amber-900 font-black bg-amber-100 px-2 py-0.5 rounded border border-amber-300">ค้าง {{ $selectedRound->waiting_duration }}</span>
                            @endif
                        </div>
                        <div class="text-blue-950 font-bold flex items-center space-x-1">
                            <span>👤 ผู้รับหนังสือเข้า:</span>
                            <strong class="text-blue-900 text-sm font-extrabold">{{ $selectedRound->inUser?->name ?? 'ไม่ระบุ' }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Parcel Delivery Timeline -->
                <div>
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider mb-4 flex items-center space-x-2">
                        <span>🚚</span>
                        <span>ไทม์ไลน์สถานะหนังสือ</span>
                    </h3>

                    @php
                        $steps = $selectedRound->route->steps_json ?? [$selectedRound->route->route_name];
                        $logs = $selectedRound->stepLogs->keyBy('step_index');
                    @endphp

                    <div class="relative pl-8 space-y-6 before:absolute before:left-3.5 before:top-2 before:bottom-2 before:w-1 before:bg-slate-200">
                        @foreach($steps as $idx => $stepName)
                            @php
                                $log = $logs->get($idx);
                                $isCompleted = ($log && $log->status === 'completed') || ($selectedRound->is_returned && ($selectedRound->current_step_index >= $idx || $idx === count($steps) - 1));
                                $isCurrent = ($selectedRound->current_step_index === $idx && ! $selectedRound->is_returned);
                                $isPending = ! $isCompleted && ! $isCurrent;
                            @endphp

                            <div class="relative">
                                <!-- Dot -->
                                <div class="absolute -left-8 top-1 w-6 h-6 rounded-full border-4 border-white shadow-sm flex items-center justify-center
                                    {{ $isCompleted ? 'bg-blue-600 text-white' : ($isCurrent ? 'bg-emerald-500 text-white ring-4 ring-emerald-200' : 'bg-slate-300') }}">
                                    @if($isCompleted)
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"></path></svg>
                                    @elseif($isCurrent)
                                        <div class="w-2.5 h-2.5 rounded-full bg-white animate-ping"></div>
                                    @endif
                                </div>

                                <!-- Content -->
                                <div>
                                    <h4 class="font-black text-base {{ $isCurrent ? 'text-emerald-950' : ($isCompleted ? 'text-slate-900' : 'text-slate-400') }}">
                                        {{ $idx + 1 }}. {{ $stepName }}
                                    </h4>

                                    @if($isCurrent)
                                        <div class="mt-1.5 inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                            @if($idx === 0)
                                                📍 รับหนังสือเข้าแล้ว (รอดำเนินการส่งต่อ)
                                            @else
                                                📍 ตำแหน่งปัจจุบัน (ค้างอยู่ {{ $selectedRound->waiting_duration }})
                                            @endif
                                        </div>
                                    @endif

                                    @if($idx === 0)
                                        <!-- Step 1: บันทึกรับหนังสือเข้า (เสมอ) -->
                                        <div class="text-slate-600 text-xs mt-1.5 flex items-center space-x-1 font-semibold">
                                            <span>📅</span>
                                            <span>{{ $selectedRound->in_datetime_thai }}</span>
                                        </div>
                                        <div class="text-blue-950 font-bold text-xs mt-0.5">
                                            👤 ผู้รับหนังสือเข้า: <span class="text-blue-900 font-extrabold">{{ $selectedRound->inUser?->name ?? 'ไม่ระบุ' }}</span>
                                        </div>
                                    @else
                                        <!-- Subsequent Steps -->
                                        @if($log && $log->action_datetime)
                                            <div class="text-slate-600 text-xs mt-1.5 flex items-center space-x-1 font-semibold">
                                                <span>📅</span>
                                                <span>{{ $log->action_datetime_thai }}</span>
                                            </div>
                                            @if($log->actionUser)
                                            <div class="text-blue-950 font-bold text-xs mt-0.5">
                                                👤 ผู้ดำเนินการ: {{ $log->actionUser->name }}
                                            </div>
                                            @endif
                                        @elseif($isPending)
                                            <div class="text-slate-400 text-xs mt-1 font-medium">
                                                ⏳ รอดำเนินการ
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <!-- Final checkout event -->
                        @if($selectedRound->is_returned && $selectedRound->out_datetime)
                        <div class="relative">
                            <div class="absolute -left-8 top-1 w-6 h-6 rounded-full bg-emerald-600 text-white border-4 border-white shadow-sm flex items-center justify-center">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-black text-base text-emerald-950">
                                    ✓ ลงระบบเสร็จสิ้นในรอบนี้
                                </h4>
                                <div class="text-slate-600 text-xs mt-1 font-semibold">
                                    📅 {{ $selectedRound->out_datetime_thai }}
                                </div>
                                <div class="text-blue-950 font-bold text-xs mt-0.5">
                                    👤 ผู้ส่งออก: {{ $selectedRound->outUser?->name ?? 'ระบบ' }}
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="text-center text-xs text-slate-400 py-4 font-medium">
            Flow-D Tracking Companion • ให้บริการข้อมูลหนังสือราชการแบบเรียลไทม์
        </div>
    </main>
</body>
</html>
