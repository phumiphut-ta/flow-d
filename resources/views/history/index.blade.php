@extends('layouts.app', ['title' => 'สมุดคุมประวัติหนังสือ'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header & Filter Toolbar (Senior-Friendly Big Text) -->
    <div class="bg-white p-5 rounded-2xl border-2 border-slate-200 shadow-sm shrink-0">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
            <div>
                <h1 class="text-xl font-black text-slate-900 flex items-center space-x-2">
                    <span>📖</span>
                    <span>สมุดคุมประวัติการรับ-ส่งหนังสือ (Ledger History)</span>
                </h1>
                <p class="text-sm font-semibold text-slate-500 mt-0.5">บันทึกประวัติการเดินเรื่องหนังสือทุกรอบ ทุกขั้นตอน สามารถตรวจสอบย้อนหลังได้</p>
            </div>
            <div class="text-sm font-bold text-slate-700 bg-slate-100 px-4 py-2 rounded-xl border border-slate-300">
                พบทั้งหมด <strong class="text-blue-700 text-base">{{ number_format($movements->total()) }}</strong> รายการ
            </div>
        </div>

        <form method="GET" action="{{ route('history.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 text-sm">
            <!-- Search Doc Number -->
            <div class="col-span-1 sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">ค้นหาเลขรับ</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="เช่น 123/69..."
                    class="w-full h-11 px-3.5 rounded-xl border-2 border-slate-300 text-sm font-bold focus:border-blue-500">
            </div>

            <!-- Route Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">เส้นทาง</label>
                <select name="route_id" class="w-full h-11 px-3.5 rounded-xl border-2 border-slate-300 text-sm font-bold bg-white focus:border-blue-500">
                    <option value="">-- ทุกเส้นทาง --</option>
                    @foreach($routes as $rt)
                    <option value="{{ $rt->id }}" {{ request('route_id') == $rt->id ? 'selected' : '' }}>
                        {{ $rt->route_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">สถานะ</label>
                <select name="status" class="w-full h-11 px-3.5 rounded-xl border-2 border-slate-300 text-sm font-bold bg-white focus:border-blue-500">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>⏳ อยู่ระหว่างทาง</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>✅ เสร็จสิ้นแล้ว</option>
                </select>
            </div>

            <!-- Date Range From -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ตั้งแต่วันที่</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full h-11 px-3.5 rounded-xl border-2 border-slate-300 text-sm font-bold focus:border-blue-500">
            </div>

            <!-- Actions -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full h-11 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black text-sm transition shadow-sm cursor-pointer flex items-center justify-center">
                    ค้นหา
                </button>
                @if(request()->anyFilled(['search', 'route_id', 'status', 'date_from', 'date_to']))
                <a href="{{ route('history.index') }}" class="h-11 px-3.5 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl font-bold text-sm text-center flex items-center justify-center">
                    ล้าง
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="flex-1 bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden">
        <div class="flex-1 overflow-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-100 text-slate-800 uppercase font-black text-xs md:text-sm sticky top-0 z-10 border-b-2 border-slate-200">
                    <tr>
                        <th class="py-3.5 px-3">เลขรับ</th>
                        <th class="py-3.5 px-3">วันที่หนังสือ</th>
                        <th class="py-3.5 px-3">เส้นทางเดินเรื่อง</th>
                        <th class="py-3.5 px-3 text-center">รอบที่</th>
                        <th class="py-3.5 px-3">รับเข้าเมื่อ (พ.ศ.)</th>
                        <th class="py-3.5 px-3">ผู้รับเข้า</th>
                        <th class="py-3.5 px-3">ส่งออกเมื่อ (พ.ศ.)</th>
                        <th class="py-3.5 px-3">ผู้ส่งออก</th>
                        <th class="py-3.5 px-3">สถานะ</th>
                        <th class="py-3.5 px-3 text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-800">
                    @forelse($movements as $m)
                    <tr class="hover:bg-blue-50/70 transition {{ $loop->even ? 'bg-slate-50/50' : 'bg-white' }}">
                        <!-- Doc Number -->
                        <td class="py-3.5 px-3 font-mono font-black text-base md:text-lg">
                            <a href="{{ route('history.show', $m->document_id) }}" class="text-blue-700 hover:text-blue-900 underline-offset-2 hover:underline">
                                {{ $m->document->doc_number }}
                            </a>
                        </td>

                        <!-- Doc Date -->
                        <td class="py-3.5 px-3 whitespace-nowrap text-sm font-semibold text-slate-700">
                            {{ $m->document->doc_date_thai }}
                        </td>

                        <!-- Route -->
                        <td class="py-3.5 px-3">
                            <span class="font-bold text-sm text-slate-900 block truncate max-w-[160px]" title="{{ $m->route->route_name }}">
                                {{ $m->route->route_name }}
                            </span>
                        </td>

                        <!-- Round Number -->
                        <td class="py-3.5 px-3 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black {{ $m->round_number > 1 ? 'bg-purple-100 text-purple-800 border border-purple-300' : 'bg-slate-200 text-slate-700' }}">
                                {{ $m->round_number }}
                            </span>
                        </td>

                        <!-- In Datetime -->
                        <td class="py-3.5 px-3 whitespace-nowrap text-sm text-slate-700 font-semibold">
                            {{ $m->in_datetime_thai }}
                        </td>

                        <!-- In User -->
                        <td class="py-3.5 px-3 whitespace-nowrap text-sm font-bold text-slate-900">
                            {{ $m->inUser?->name ?? '-' }}
                        </td>

                        <!-- Out Datetime -->
                        <td class="py-3.5 px-3 whitespace-nowrap text-sm text-slate-700 font-semibold">
                            {{ $m->out_datetime_thai }}
                        </td>

                        <!-- Out User -->
                        <td class="py-3.5 px-3 whitespace-nowrap text-sm font-bold text-slate-900">
                            {{ $m->outUser?->name ?? '-' }}
                        </td>

                        <!-- Status Badge -->
                        <td class="py-3.5 px-3 whitespace-nowrap">
                            @if($m->is_returned)
                                <span class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                    ✓ ลงระบบเสร็จสิ้น
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300">
                                    ⏳ {{ $m->current_step_name ?: 'อยู่ระหว่างทาง' }}
                                </span>
                            @endif
                        </td>

                        <!-- Action / Admin Recovery -->
                        <td class="py-3.5 px-3 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('history.show', $m->document_id) }}"
                                    class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs md:text-sm font-bold transition">
                                    ดูประวัติ
                                </a>

                                <!-- Admin Same-Day Recovery: Re-open checked out item -->
                                @if(auth()->check() && auth()->user()->isAdmin() && $m->is_returned && $m->canAdminUndo())
                                <button type="button"
                                    @click="openRecovery('{{ route('admin.recovery.reopen', $m) }}', 'ยกเลิกการส่งออกหนังสือเลขรับ {{ $m->document->doc_number }} (รอบ {{ $m->round_number }})')"
                                    class="px-3 py-1.5 rounded-lg bg-purple-100 hover:bg-purple-200 text-purple-900 font-black text-xs md:text-sm border-2 border-purple-300 transition cursor-pointer"
                                    title="Admin: ยกเลิกการส่งออกและเปิดเป็นงานค้างต่อ (เฉพาะรายการวันนี้)">
                                    ↩ กู้คืน
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-16 text-center text-slate-400">
                            <div class="text-3xl mb-2">🔍</div>
                            <div class="text-base font-bold text-slate-600">ไม่พบรายการหนังสือตามเงื่อนไขที่ค้นหา</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
        <div class="p-3 border-t border-slate-200 bg-slate-50 shrink-0">
            {{ $movements->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
