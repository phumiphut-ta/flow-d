@extends('layouts.app', ['title' => 'รายงานสรุปการรับ-ส่งหนังสือ'])

@section('content')
<style>
    @media print {
        header, .no-print, nav, footer, #quick_shortcuts_bar {
            display: none !important;
        }
        body, main {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 11pt !important;
            color: #000 !important;
            height: auto !important;
            overflow: visible !important;
        }
        .print-container {
            max-width: 100% !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .print-card {
            border: 1px solid #333 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        .print-header {
            display: block !important;
        }
        table {
            border-collapse: collapse !important;
            width: 100% !important;
            font-size: 9.5pt !important;
        }
        th, td {
            border: 1px solid #444 !important;
            padding: 5px 6px !important;
        }
        th {
            background-color: #f0f0f0 !important;
            color: #000 !important;
        }
        .page-break {
            page-break-before: always;
        }
    }
    @page {
        size: A4 landscape;
        margin: 12mm 10mm;
    }
</style>

<div class="max-w-7xl mx-auto py-3 space-y-4 print-container">

    <!-- Print Header (Visible in A4 Print / PDF Export) -->
    <div class="hidden print-header text-center border-b-2 border-slate-900 pb-3 mb-4">
        <h1 class="text-xl font-black text-black">รายงานสรุปการรับ-ส่งและสถานะหนังสือราชการ</h1>
        <p class="text-xs text-slate-700 mt-1 font-bold">
            ช่วงเวลาที่รายงาน: <span class="underline">{{ $periodLabel }}</span>
            @if($selectedRouteId)
                • เส้นทาง: <span class="underline">{{ $routes->firstWhere('id', $selectedRouteId)?->route_name }}</span>
            @endif
        </p>
        <p class="text-[11px] text-slate-500 mt-0.5">
            พิมพ์รายงาน ณ วันที่ {{ \App\Support\ThaiDate::formatDateTime(now()) }} • ผู้พิมพ์: {{ auth()->user()->name }}
        </p>
    </div>

    <!-- On-screen Header & Controls (Hidden in Print) -->
    <div class="no-print bg-white p-4 rounded-2xl border-2 border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-xl font-black text-slate-900 flex items-center space-x-2">
                <span>📊</span>
                <span>รายงานสรุปการรับ-ส่งหนังสือ (Periodic Summary Report)</span>
            </h1>
            <p class="text-xs font-semibold text-slate-500 mt-0.5">
                สรุปยอดสถิติการรับเข้า ส่งออก และงานค้างสะสม พร้อมจัดพิมพ์ลงกระดาษ A4 หรือบันทึกเป็น PDF
            </p>
        </div>

        <!-- Print Action Button -->
        <button type="button" onclick="window.print()"
            class="h-11 px-5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-sm shadow-md transition flex items-center justify-center space-x-2 cursor-pointer shrink-0">
            <span>🖨️</span>
            <span>พิมพ์รายงาน / บันทึก PDF (A4)</span>
        </button>
    </div>

    <!-- Filter Bar (Hidden in Print) -->
    <div class="no-print bg-white p-4 rounded-2xl border-2 border-slate-200 shadow-sm space-y-3">
        <!-- Quick Period Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-1">เลือกช่วงเวลา:</span>
            <a href="{{ route('reports.index', ['period' => 'today', 'route_id' => $selectedRouteId]) }}"
                class="h-9 px-4 rounded-lg font-extrabold text-xs flex items-center transition {{ $period === 'today' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                📅 วันนี้
            </a>
            <a href="{{ route('reports.index', ['period' => 'this_week', 'route_id' => $selectedRouteId]) }}"
                class="h-9 px-4 rounded-lg font-extrabold text-xs flex items-center transition {{ $period === 'this_week' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                🗓️ สัปดาห์นี้
            </a>
            <a href="{{ route('reports.index', ['period' => 'this_month', 'route_id' => $selectedRouteId]) }}"
                class="h-9 px-4 rounded-lg font-extrabold text-xs flex items-center transition {{ $period === 'this_month' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                📆 เดือนนี้
            </a>
            <a href="{{ route('reports.index', ['period' => 'this_year', 'route_id' => $selectedRouteId]) }}"
                class="h-9 px-4 rounded-lg font-extrabold text-xs flex items-center transition {{ $period === 'this_year' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                📈 ทั้งปี พ.ศ. {{ now()->year + 543 }}
            </a>
        </div>

        <!-- Custom Date Range & Route Filter Form -->
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3 pt-2 border-t border-slate-100">
            <input type="hidden" name="period" value="custom">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ตั้งแต่วันที่</label>
                <input type="date" name="date_from" value="{{ request('date_from', $startDate->format('Y-m-d')) }}"
                    class="h-11 px-3.5 rounded-xl border-2 border-slate-300 text-sm font-bold text-slate-900 focus:border-blue-500 focus:ring-0">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ถึงวันที่</label>
                <input type="date" name="date_to" value="{{ request('date_to', $endDate->format('Y-m-d')) }}"
                    class="h-11 px-3.5 rounded-xl border-2 border-slate-300 text-sm font-bold text-slate-900 focus:border-blue-500 focus:ring-0">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">กรองเส้นทางเดินเอกสาร</label>
                <select name="route_id"
                    class="h-11 px-3.5 rounded-xl border-2 border-slate-300 text-sm font-bold bg-white text-slate-800 focus:border-blue-500 focus:ring-0 cursor-pointer">
                    <option value="">-- ทุกเส้นทาง --</option>
                    @foreach($routes as $rt)
                    <option value="{{ $rt->id }}" {{ $selectedRouteId == $rt->id ? 'selected' : '' }}>
                        {{ $rt->route_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="h-11 px-5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-extrabold shadow-xs transition flex items-center justify-center cursor-pointer">
                🔍 กรองข้อมูล
            </button>

            @if(request()->anyFilled(['period', 'date_from', 'date_to', 'route_id']))
            <a href="{{ route('reports.index') }}" class="h-11 px-3.5 text-sm font-bold text-slate-500 hover:text-slate-800 underline flex items-center">
                ล้างตัวกรอง
            </a>
            @endif
        </form>
    </div>

    <!-- Period Info Banner -->
    <div class="no-print bg-blue-50/70 border-2 border-blue-200 rounded-xl px-4 py-2.5 text-xs md:text-sm text-blue-950 font-bold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span>📌</span>
            <span>รายงานข้อมูล: <span class="text-blue-900 underline">{{ $periodLabel }}</span></span>
            @if($selectedRouteId)
                <span class="text-blue-700">• เฉพาะเส้นทาง: {{ $routes->firstWhere('id', $selectedRouteId)?->route_name }}</span>
            @endif
        </div>
        <div class="text-slate-600 font-medium text-xs">
            หนังสือค้างสะสมทั้งระบบ ณ ปัจจุบัน: <strong class="text-amber-950 font-black">{{ number_format($totalActiveSystem) }}</strong> เล่ม
        </div>
    </div>

    <!-- Summary Metrics Cards (Senior-Friendly Big Numbers) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 print:grid-cols-4">
        <!-- Total In -->
        <div class="bg-white p-4 rounded-2xl border-2 border-blue-300 shadow-sm print-card">
            <span class="text-xs md:text-sm font-bold text-blue-800 uppercase tracking-wider block">📥 รับเข้าในงวดนี้</span>
            <div class="flex items-baseline space-x-1.5 mt-1">
                <span class="text-3xl md:text-4xl font-black text-slate-900">{{ number_format($totalIntake) }}</span>
                <span class="text-xs md:text-sm font-semibold text-slate-500">เล่ม</span>
            </div>
        </div>

        <!-- Total Completed -->
        <div class="bg-white p-4 rounded-2xl border-2 border-emerald-300 shadow-sm print-card">
            <span class="text-xs md:text-sm font-bold text-emerald-800 uppercase tracking-wider block">✅ ส่งออกเสร็จสิ้น</span>
            <div class="flex items-baseline space-x-1.5 mt-1">
                <span class="text-3xl md:text-4xl font-black text-slate-900">{{ number_format($totalCompleted) }}</span>
                <span class="text-xs md:text-sm font-semibold text-slate-500">เล่ม</span>
            </div>
        </div>

        <!-- Pending in period -->
        <div class="bg-white p-4 rounded-2xl border-2 border-amber-300 shadow-sm print-card">
            <span class="text-xs md:text-sm font-bold text-amber-800 uppercase tracking-wider block">⏳ ค้างอยู่ระหว่างทาง</span>
            <div class="flex items-baseline space-x-1.5 mt-1">
                <span class="text-3xl md:text-4xl font-black text-slate-900">{{ number_format($totalPending) }}</span>
                <span class="text-xs md:text-sm font-semibold text-slate-500">เล่ม</span>
            </div>
        </div>

        <!-- Completion Rate -->
        <div class="bg-white p-4 rounded-2xl border-2 border-purple-300 shadow-sm print-card">
            <span class="text-xs md:text-sm font-bold text-purple-800 uppercase tracking-wider block">📈 อัตราดำเนินการ</span>
            <div class="flex items-baseline space-x-1 mt-1">
                <span class="text-3xl md:text-4xl font-black text-slate-900">{{ $completionRate }}</span>
                <span class="text-xl font-black text-purple-700">%</span>
            </div>
        </div>
    </div>

    <!-- Route Breakdown Table -->
    <div class="bg-white rounded-2xl border-2 border-slate-200 shadow-sm overflow-hidden print-card">
        <div class="bg-slate-100 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                <span>🛣️</span>
                <span>สรุปยอดจำแนกตามเส้นทางเดินเอกสาร (Route Breakdown)</span>
            </h2>
            <span class="text-xs font-semibold text-slate-500 no-print">{{ count($routeBreakdown) }} เส้นทาง</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs md:text-sm">
                <thead class="bg-slate-50 text-slate-700 uppercase font-black border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">ชื่อเส้นทางเดินเอกสาร</th>
                        <th class="py-3 px-4 text-center">ประเภท</th>
                        <th class="py-3 px-4 text-right">📥 รับเข้า (เล่ม)</th>
                        <th class="py-3 px-4 text-right">✅ ส่งออกเสร็จสิ้น (เล่ม)</th>
                        <th class="py-3 px-4 text-right">⏳ ค้างระหว่างทาง (เล่ม)</th>
                        <th class="py-3 px-4 text-right">ร้อยละเสร็จสิ้น</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800">
                    @forelse($routeBreakdown as $idx => $r)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-2.5 px-4 text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-2.5 px-4 font-bold text-slate-900">
                            {{ $r['name'] }}
                        </td>
                        <td class="py-2.5 px-4 text-center">
                            @if($r['is_pipeline'])
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                    หลายขั้นตอน ({{ $r['steps_count'] }} ขั้น)
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                                    จุดเดียว
                                </span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">
                            {{ number_format($r['in_count']) }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-emerald-800">
                            {{ number_format($r['out_count']) }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-amber-900">
                            {{ number_format($r['pending_count']) }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-mono font-extrabold text-blue-900">
                            {{ $r['completion_rate'] }}%
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            ไม่พบข้อมูลเส้นทางเดินเอกสาร
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-100/90 font-black border-t-2 border-slate-300 text-slate-900">
                    <tr>
                        <td colspan="3" class="py-3 px-4 text-right uppercase">รวมทั้งสิ้น:</td>
                        <td class="py-3 px-4 text-right font-mono">{{ number_format($totalIntake) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-800">{{ number_format($totalCompleted) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-amber-900">{{ number_format($totalPending) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-blue-900">{{ $completionRate }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Detailed Ledger for this Period -->
    <div class="bg-white rounded-2xl border-2 border-slate-200 shadow-sm overflow-hidden print-card">
        <div class="bg-slate-100 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center space-x-2">
                <span>📋</span>
                <span>สมุดรายการหนังสือที่รับเข้าในงวดนี้ (Document Ledger - {{ count($movements) }} เล่ม)</span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50 text-slate-700 uppercase font-black border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-3 w-10 text-center">#</th>
                        <th class="py-2.5 px-3 font-mono">เลขรับ</th>
                        <th class="py-2.5 px-3">วันที่ในหนังสือ</th>
                        <th class="py-2.5 px-3">เส้นทาง</th>
                        <th class="py-2.5 px-3">วันเวลารับเข้า & ผู้รับ</th>
                        <th class="py-2.5 px-3">สถานะปัจจุบัน</th>
                        <th class="py-2.5 px-3">วันเวลาส่งออก & ผู้ส่งออก</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800">
                    @forelse($movements as $idx => $m)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-2 px-3 text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-2 px-3 font-mono font-black text-slate-900 text-sm">
                            {{ $m->document->doc_number }}
                            @if($m->round_number > 1)
                                <span class="text-[10px] bg-slate-200 px-1 py-0.2 rounded font-mono text-slate-700">R{{ $m->round_number }}</span>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-slate-600 font-medium whitespace-nowrap">
                            {{ $m->document->doc_date_readable }}
                        </td>
                        <td class="py-2 px-3 font-semibold text-slate-800">
                            {{ $m->route->route_name }}
                        </td>
                        <td class="py-2 px-3 whitespace-nowrap">
                            <span class="font-bold text-slate-900 block leading-tight">{{ $m->in_datetime_thai }}</span>
                            <span class="text-[11px] text-slate-500">{{ $m->inUser?->name ?? '-' }}</span>
                        </td>
                        <td class="py-2 px-3 whitespace-nowrap">
                            @if($m->is_returned)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                    ✓ ลงระบบเสร็จสิ้น
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-900 border border-blue-300">
                                    📍 {{ $m->current_step_name }}
                                </span>
                            @endif
                        </td>
                        <td class="py-2 px-3 whitespace-nowrap">
                            @if($m->out_datetime)
                                <span class="font-bold text-emerald-950 block leading-tight">{{ $m->out_datetime_thai }}</span>
                                <span class="text-[11px] text-slate-500">{{ $m->outUser?->name ?? 'ระบบ' }}</span>
                            @else
                                <span class="text-amber-800 font-semibold text-xs">⏳ ค้าง {{ $m->waiting_duration }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            ไม่พบรายการหนังสือในงวดเวลาที่เลือก
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Print Footer Signatures (Visible only in Print / PDF Export) -->
    <div class="hidden print-header mt-8 pt-6 border-t border-slate-400 page-break-inside-avoid">
        <div class="grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <p class="mb-14">ลงชื่อ .............................................................. ผู้รายงาน</p>
                <p>( {{ auth()->user()->name }} )</p>
                <p class="text-slate-600 mt-1">ตำแหน่ง เจ้าหน้าที่สารบรรณ</p>
            </div>
            <div>
                <p class="mb-14">ลงชื่อ .............................................................. ผู้รับรอง</p>
                <p>( .............................................................. )</p>
                <p class="text-slate-600 mt-1">ตำแหน่ง หัวหน้างานสารบรรณ/ผู้อำนวยการ</p>
            </div>
        </div>
    </div>

</div>
@endsection

