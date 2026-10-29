@extends('layouts.app', ['title' => 'สมุดรับ-งานค้าง (Dual-Pane)'])

@section('content')
<div class="h-full flex flex-col space-y-3" x-data="{
    selectedIds: [],
    selectAll: false,
    intakeSubmitting: false,
    batchSubmitting: false,
    actionSubmittingId: null,

    // Edit Doc Number modal state
    editDocModal: false,
    editDocMovementId: null,
    oldDocNumber: '',
    newDocNumber: '',
    editDocReason: '',
    editDocSubmitting: false,

    // Void Intake modal state
    voidModal: false,
    voidMovementId: null,
    voidDocNumber: '',
    voidReason: '',
    voidSubmitting: false,

    toggleSelectAll() {
        if (this.selectAll) {
            this.selectedIds = [];
            this.selectAll = false;
        } else {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            this.selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
            this.selectAll = true;
        }
    },

    submitAction(formId, movementId) {
        if (this.actionSubmittingId !== null) return false;
        this.actionSubmittingId = movementId;
        const form = document.getElementById(formId);
        if (form) form.submit();
    },

    openEditDocNumber(movementId, currentNumber) {
        this.editDocMovementId = movementId;
        this.oldDocNumber = currentNumber;
        this.newDocNumber = currentNumber;
        this.editDocReason = '';
        this.editDocSubmitting = false;
        this.editDocModal = true;
        this.$nextTick(() => {
            const input = document.getElementById('new_doc_number_input');
            if (input) { input.focus(); input.select(); }
        });
    },

    openVoidIntake(movementId, docNumber) {
        this.voidMovementId = movementId;
        this.voidDocNumber = docNumber;
        this.voidReason = '';
        this.voidSubmitting = false;
        this.voidModal = true;
        this.$nextTick(() => {
            const input = document.getElementById('void_reason_input');
            if (input) { input.focus(); }
        });
    }
}">
    <!-- Quick Statistics Bar (Senior-Friendly Big Numbers & Text) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 shrink-0">
        <a href="{{ route('dashboard', ['tab' => 'active']) }}" class="bg-white border-2 {{ $activeTab === 'active' ? 'border-amber-500 ring-2 ring-amber-300' : 'border-amber-300 hover:border-amber-400' }} rounded-xl p-4 shadow-sm flex items-center justify-between transition cursor-pointer">
            <div>
                <span class="text-sm font-bold text-amber-800 uppercase tracking-wider block">งานค้างอยู่ระหว่างทาง</span>
                <div class="flex items-baseline space-x-1.5 mt-0.5">
                    <span class="text-3xl font-black text-slate-900">{{ number_format($totalActive) }}</span>
                    <span class="text-sm font-semibold text-slate-500">เรื่อง</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-800 text-2xl font-bold">
                ⏳
            </div>
        </a>

        <div class="bg-white border-2 border-blue-300 rounded-xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-sm font-bold text-blue-800 uppercase tracking-wider block">รับเข้าวันนี้ ({{ \App\Support\ThaiDate::format(now()) }})</span>
                <div class="flex items-baseline space-x-1.5 mt-0.5">
                    <span class="text-3xl font-black text-slate-900">{{ number_format($todayIntake) }}</span>
                    <span class="text-sm font-semibold text-slate-500">เรื่อง</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-800 text-2xl font-bold">
                📥
            </div>
        </div>

        <a href="{{ route('dashboard', ['tab' => 'completed']) }}" class="bg-white border-2 {{ $activeTab === 'completed' ? 'border-emerald-500 ring-2 ring-emerald-300' : 'border-emerald-300 hover:border-emerald-400' }} rounded-xl p-4 shadow-sm flex items-center justify-between transition cursor-pointer">
            <div>
                <span class="text-sm font-bold text-emerald-800 uppercase tracking-wider block">ส่งออกหนังสือเสร็จสิ้นวันนี้</span>
                <div class="flex items-baseline space-x-1.5 mt-0.5">
                    <span class="text-3xl font-black text-slate-900">{{ number_format($todayCompleted) }}</span>
                    <span class="text-sm font-semibold text-slate-500">เรื่อง</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-800 text-2xl font-bold">
                ✅
            </div>
        </a>
    </div>

    <!-- Dual-Pane Area: Left (30%) Quick Intake, Right (70%) Worklist -->
    <div class="flex-1 flex flex-col lg:flex-row gap-4 min-h-0">

        <!-- LEFT PANE (30%): Fast Intake Form with Large Inputs -->
        <div class="w-full lg:w-[32%] shrink-0 bg-white border-2 border-blue-200 rounded-2xl shadow-sm flex flex-col overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 px-5 py-3.5 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <span class="text-2xl">📥</span>
                    <h2 class="font-extrabold text-base">รับหนังสือเข้าด่วน (Intake)</h2>
                </div>
                <kbd class="bg-blue-800 text-xs text-amber-300 font-bold px-2 py-0.5 rounded font-mono border border-blue-700">Alt + N</kbd>
            </div>

            <div class="p-5 flex-1 overflow-y-auto space-y-4">
                <form id="intake_form" method="POST" action="{{ route('dashboard.intake') }}"
                    @submit="if(intakeSubmitting) { $event.preventDefault(); return false; } intakeSubmitting = true;">
                    @csrf

                    <!-- Doc Number (Extra Large, High Contrast) -->
                    <div class="mb-4">
                        <label for="intake_doc_number" class="block text-sm font-extrabold text-slate-800 mb-1.5">
                            เลขรับหนังสือ <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="intake_doc_number" name="doc_number" value="{{ old('doc_number') }}" required autofocus
                            autocomplete="off"
                            class="w-full px-4 py-3 rounded-xl border-2 border-blue-500 bg-blue-50/20 text-slate-950 font-mono text-xl font-black focus:border-blue-700 focus:bg-white focus:ring-2 focus:ring-blue-300 placeholder-slate-400 transition"
                            placeholder="เช่น 123/69">
                        <span class="text-xs font-semibold text-slate-500 mt-1 block">คีย์ลัด: พิมพ์เสร็จแล้วกด <kbd class="bg-slate-200 px-1.5 py-0.5 rounded border border-slate-300 font-bold text-slate-800">Enter</kbd> เพื่อรับเข้าทันที</span>
                    </div>

                    <!-- Doc Date (Large) -->
                    <div class="mb-4">
                        <label for="intake_doc_date" class="block text-sm font-extrabold text-slate-800 mb-1.5">
                            วันที่ลงในหนังสือ <span class="text-red-600">*</span>
                        </label>
                        <input type="date" id="intake_doc_date" name="doc_date" value="{{ old('doc_date', date('Y-m-d')) }}" required
                            class="w-full h-11 px-3.5 rounded-xl border-2 border-slate-300 text-base font-bold text-slate-900 focus:border-blue-600 focus:ring-0">
                        <span class="text-xs text-slate-500 mt-1 block font-medium">ระบบจะแสดงเป็น พ.ศ. ({{ \App\Support\ThaiDate::format(now()) }}) อัตโนมัติ</span>
                    </div>

                    <!-- Route Selection (Large) -->
                    <div class="mb-5">
                        <label for="intake_route_id" class="block text-sm font-extrabold text-slate-800 mb-1.5">
                            เส้นทางเดินเอกสาร (Route) <span class="text-red-600">*</span>
                        </label>
                        <select id="intake_route_id" name="route_id" required
                            class="w-full h-11 px-3.5 rounded-xl border-2 border-slate-300 text-sm md:text-base font-bold text-slate-900 focus:border-blue-600 focus:ring-0 bg-white">
                            @foreach($activeRoutes as $rt)
                            <option value="{{ $rt->id }}" {{ old('route_id') == $rt->id ? 'selected' : '' }}>
                                {{ $rt->route_name }} ({{ $rt->isPipeline() ? count($rt->steps_json).' ขั้นตอน' : 'จุดเดียว' }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Large Senior-Friendly Submit Button with Anti-Double Click -->
                    <button type="submit" :disabled="intakeSubmitting"
                        class="w-full py-3.5 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-base md:text-lg transition shadow-md shadow-blue-500/20 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                        <span x-show="!intakeSubmitting" class="flex items-center space-x-2">
                            <span>📥 บันทึกรับหนังสือเข้า</span>
                            <span class="text-xs font-normal opacity-90">(Enter)</span>
                        </span>
                        <span x-show="intakeSubmitting" x-cloak class="flex items-center text-base">
                            <svg class="animate-spin -ml-1 mr-2.5 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            ⏳ กำลังบันทึก...
                        </span>
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT PANE (70%): High-Density Worklist with Large Text & Chunky Buttons -->
        <div class="w-full lg:w-[68%] flex-1 bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden">
            <!-- Mode Tabs: Active Worklist vs Today's Completed (Senior-Friendly Big Tabs) -->
            <div class="flex items-center border-b-2 border-slate-200 bg-slate-100 px-4 pt-2.5 gap-2 shrink-0">
                <a href="{{ route('dashboard', array_filter(['tab' => 'active', 'route_id' => $currentRouteFilter, 'search' => $currentSearch])) }}"
                    class="px-5 py-2.5 rounded-t-xl font-black text-sm md:text-base flex items-center space-x-2 transition border-t-2 border-x-2 {{ $activeTab === 'active' ? 'bg-white border-blue-500 text-blue-900 shadow-sm -mb-[2px]' : 'bg-slate-200/80 border-transparent text-slate-600 hover:bg-slate-200 hover:text-slate-900' }}">
                    <span>⏳ งานค้างอยู่ระหว่างทาง</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black {{ $activeTab === 'active' ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-slate-300 text-slate-700' }}">{{ number_format($totalActive) }}</span>
                </a>

                <a href="{{ route('dashboard', array_filter(['tab' => 'completed', 'route_id' => $currentRouteFilter, 'search' => $currentSearch])) }}"
                    class="px-5 py-2.5 rounded-t-xl font-black text-sm md:text-base flex items-center space-x-2 transition border-t-2 border-x-2 {{ $activeTab === 'completed' ? 'bg-white border-emerald-500 text-emerald-900 shadow-sm -mb-[2px]' : 'bg-slate-200/80 border-transparent text-slate-600 hover:bg-slate-200 hover:text-slate-900' }}">
                    <span>✅ ส่งออกหนังสือเสร็จสิ้นวันนี้</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black {{ $activeTab === 'completed' ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-slate-300 text-slate-700' }}">{{ number_format($todayCompleted) }}</span>
                </a>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="p-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">

                    <!-- Route Filter -->
                    <select name="route_id" onchange="this.form.submit()"
                        class="h-11 px-3.5 rounded-xl border-2 border-slate-300 text-sm font-bold bg-white text-slate-800 focus:border-blue-500 focus:ring-0 cursor-pointer">
                        <option value="">-- กรองทุกเส้นทาง --</option>
                        @foreach($activeRoutes as $rt)
                        <option value="{{ $rt->id }}" {{ $currentRouteFilter == $rt->id ? 'selected' : '' }}>
                            {{ $rt->route_name }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Quick Search Box (HotKey: /) -->
                    <div class="relative flex-1 min-w-[200px] max-w-sm">
                        <input type="text" id="quick_search_input" name="search" value="{{ $currentSearch }}"
                            placeholder="ค้นหาเลขรับ... (กด / เพื่อค้นหา)"
                            class="w-full h-11 pl-10 pr-3.5 rounded-xl border-2 border-slate-300 text-sm font-semibold focus:border-blue-500 focus:ring-0 text-slate-900">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none">🔍</span>
                    </div>

                    <button type="submit" class="h-11 px-5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-sm font-extrabold shadow-xs transition flex items-center justify-center cursor-pointer">
                        ค้นหา
                    </button>

                    @if($currentRouteFilter || $currentSearch)
                    <a href="{{ route('dashboard', ['tab' => $activeTab]) }}" class="h-11 px-3 text-sm font-bold text-slate-500 hover:text-slate-800 underline flex items-center">
                        ล้างตัวกรอง
                    </a>
                    @endif
                </form>

                <div class="text-sm font-semibold text-slate-600">
                    @if($activeTab === 'completed')
                        แสดง <strong>{{ $movements->total() }}</strong> รายการที่ส่งออกเสร็จสิ้นวันนี้
                    @else
                        แสดง <strong>{{ $movements->total() }}</strong> รายการที่กำลังเดินเรื่อง
                    @endif
                </div>
            </div>

            @if($activeTab === 'active')
            <!-- Batch Action Toolbar (Visible when rows selected) -->
            <div x-show="selectedIds.length > 0" x-cloak
                class="px-5 py-2.5 bg-gradient-to-r from-blue-100 to-indigo-100 border-b border-blue-300 flex items-center justify-between transition shrink-0">
                <div class="flex items-center space-x-2 text-sm font-bold text-blue-950">
                    <span>✓ เลือกแล้ว: <span x-text="selectedIds.length" class="text-blue-800 font-black text-base"></span> รายการ</span>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Batch Check-out -->
                    <form method="POST" action="{{ route('dashboard.batch.checkout') }}"
                        @submit="if(batchSubmitting) { $event.preventDefault(); return false; } batchSubmitting = true;">
                        @csrf
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="movement_ids[]" :value="id">
                        </template>
                        <button type="submit" :disabled="batchSubmitting"
                            class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-extrabold shadow-sm transition flex items-center space-x-1.5 disabled:opacity-50 cursor-pointer">
                            <span>📤 ส่งออกหนังสือทั้งชุด</span>
                        </button>
                    </form>

                    <!-- Batch Advance -->
                    <form method="POST" action="{{ route('dashboard.batch.advance') }}"
                        @submit="if(batchSubmitting) { $event.preventDefault(); return false; } batchSubmitting = true;">
                        @csrf
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="movement_ids[]" :value="id">
                        </template>
                        <button type="submit" :disabled="batchSubmitting"
                            class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-extrabold shadow-sm transition flex items-center space-x-1.5 disabled:opacity-50 cursor-pointer">
                            <span>⏩ ส่งต่อขั้นตอนทั้งชุด</span>
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Large Senior-Friendly Data Grid (Sticky Header) -->
            <div class="flex-1 overflow-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-100 text-slate-800 uppercase font-black text-xs md:text-sm sticky top-0 z-10 border-b-2 border-slate-200 shadow-xs">
                        @if($activeTab === 'completed')
                        <tr>
                            <th class="py-3.5 px-3">เลขรับ</th>
                            <th class="py-3.5 px-3">วันที่หนังสือ</th>
                            <th class="py-3.5 px-3">เส้นทาง (Route)</th>
                            <th class="py-3.5 px-3">รับเข้าเมื่อ</th>
                            <th class="py-3.5 px-3">ส่งออกเมื่อ</th>
                            <th class="py-3.5 px-3">ผู้ส่งออก</th>
                            <th class="py-3.5 px-3">สถานะ</th>
                            <th class="py-3.5 px-3 text-right">การจัดการ</th>
                        </tr>
                        @else
                        <tr>
                            <th class="py-3.5 px-3 w-10 text-center">
                                <input type="checkbox" @click="toggleSelectAll()" :checked="selectAll"
                                    class="w-5 h-5 rounded border-2 border-slate-400 text-blue-600 focus:ring-0 cursor-pointer">
                            </th>
                            <th class="py-3.5 px-3">เลขรับ</th>
                            <th class="py-3.5 px-3">วันที่หนังสือ</th>
                            <th class="py-3.5 px-3">เส้นทาง (Route)</th>
                            <th class="py-3.5 px-3">ขั้นตอนปัจจุบัน</th>
                            <th class="py-3.5 px-3">เวลาที่ค้าง</th>
                            <th class="py-3.5 px-3">ผู้รับเข้า</th>
                            <th class="py-3.5 px-3 text-right">ดำเนินการ (Actions)</th>
                        </tr>
                        @endif
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800">
                        @forelse($movements as $m)
                            @if($activeTab === 'completed')
                            <tr class="hover:bg-emerald-50/60 transition {{ $loop->even ? 'bg-slate-50/60' : 'bg-white' }}">
                                <!-- Doc Number & Round -->
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('history.show', $m->document_id) }}" class="font-black text-blue-700 hover:text-blue-900 font-mono text-base md:text-lg underline-offset-2 hover:underline">
                                            {{ $m->document->doc_number }}
                                        </a>
                                        @if($m->round_number > 1)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-black bg-purple-100 text-purple-800 border border-purple-300">
                                            รอบ {{ $m->round_number }}
                                        </span>
                                        @endif

                                        @if($m->canCorrectIntake())
                                        <!-- Quick Edit Doc Number button -->
                                        <button type="button" @click="openEditDocNumber({{ $m->id }}, '{{ $m->document->doc_number }}')"
                                            class="p-1 rounded-md text-slate-400 hover:text-blue-700 hover:bg-blue-100 transition cursor-pointer text-xs"
                                            title="แก้ไขเลขรับ (กรณีพิมพ์ผิด)">
                                            ✏️
                                        </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Doc Date -->
                                <td class="py-3.5 px-3 whitespace-nowrap text-sm font-semibold text-slate-700">
                                    {{ $m->document->doc_date_thai }}
                                </td>

                                <!-- Route -->
                                <td class="py-3.5 px-3">
                                    <span class="font-bold text-sm text-slate-900 block truncate max-w-[170px]" title="{{ $m->route->route_name }}">
                                        {{ $m->route->route_name }}
                                    </span>
                                </td>

                                <!-- In Datetime -->
                                <td class="py-3.5 px-3 whitespace-nowrap text-sm font-semibold text-slate-600">
                                    {{ $m->in_datetime_thai }}
                                </td>

                                <!-- Out Datetime -->
                                <td class="py-3.5 px-3 whitespace-nowrap text-sm font-bold text-slate-900">
                                    {{ $m->out_datetime_thai }}
                                </td>

                                <!-- Out User -->
                                <td class="py-3.5 px-3 whitespace-nowrap text-sm font-bold text-slate-900">
                                    {{ $m->outUser?->name ?? '-' }}
                                </td>

                                <!-- Status Badge (✓ ลงระบบเสร็จสิ้น) -->
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        ✓ ลงระบบเสร็จสิ้น
                                    </span>
                                </td>

                                <!-- Actions & Admin Recovery -->
                                <td class="py-3.5 px-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('history.show', $m->document_id) }}"
                                            class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs md:text-sm font-bold transition">
                                            ดูประวัติ
                                        </a>

                                        @if(auth()->check() && auth()->user()->isAdmin() && $m->canAdminUndo())
                                        <button type="button"
                                            @click="openRecovery('{{ route('admin.recovery.reopen', $m) }}', 'ยกเลิกการส่งออกเลขรับ {{ $m->document->doc_number }}')"
                                            class="px-3 py-1.5 rounded-lg bg-purple-100 hover:bg-purple-200 text-purple-900 font-black text-xs md:text-sm border-2 border-purple-300 transition cursor-pointer"
                                            title="Admin: ยกเลิกการส่งออกและดึงกลับมาเป็นงานค้าง">
                                            ↩ กู้คืน
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @else
                            <tr class="hover:bg-blue-50/70 transition {{ $loop->even ? 'bg-slate-50/60' : 'bg-white' }}">
                                <!-- Large Checkbox -->
                                <td class="py-3.5 px-3 text-center">
                                    <input type="checkbox" value="{{ $m->id }}" x-model.number="selectedIds"
                                        class="row-checkbox w-5 h-5 rounded border-2 border-slate-400 text-blue-600 focus:ring-0 cursor-pointer">
                                </td>

                                <!-- Doc Number & Round (Large Font) -->
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('history.show', $m->document_id) }}" class="font-black text-blue-700 hover:text-blue-900 font-mono text-base md:text-lg underline-offset-2 hover:underline">
                                            {{ $m->document->doc_number }}
                                        </a>
                                        @if($m->round_number > 1)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-black bg-purple-100 text-purple-800 border border-purple-300">
                                            รอบ {{ $m->round_number }}
                                        </span>
                                        @endif

                                        @if($m->canCorrectIntake())
                                        <!-- Quick Edit Doc Number button -->
                                        <button type="button" @click="openEditDocNumber({{ $m->id }}, '{{ $m->document->doc_number }}')"
                                            class="p-1 rounded-md text-slate-400 hover:text-blue-700 hover:bg-blue-100 transition cursor-pointer text-xs"
                                            title="แก้ไขเลขรับ (กรณีพิมพ์ผิด)">
                                            ✏️
                                        </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Doc Date (Thai Buddhist Era) -->
                                <td class="py-3.5 px-3 whitespace-nowrap text-sm font-semibold text-slate-700">
                                    {{ $m->document->doc_date_thai }}
                                </td>

                                <!-- Route -->
                                <td class="py-3.5 px-3">
                                    <span class="font-bold text-sm text-slate-900 block truncate max-w-[170px]" title="{{ $m->route->route_name }}">
                                        {{ $m->route->route_name }}
                                    </span>
                                </td>

                                <!-- Current Step -->
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="font-black text-emerald-950 bg-emerald-100 border border-emerald-300 px-2.5 py-1 rounded-md text-xs md:text-sm">
                                            {{ $m->current_step_name ?: 'รับเข้าระบบ' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Waiting Duration -->
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <span class="text-amber-950 font-bold bg-amber-100 px-2 py-1 rounded-md text-xs border border-amber-300">
                                        ⏳ {{ $m->waiting_duration }}
                                    </span>
                                </td>

                                <!-- Intake User -->
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <span class="text-slate-900 font-bold text-sm block leading-tight">{{ $m->inUser?->name ?? '-' }}</span>
                                    <span class="text-xs text-slate-500 block">{{ $m->in_datetime_thai }}</span>
                                </td>

                                <!-- Chunky Action Buttons (Easy to Click for Seniors) -->
                                <td class="py-3.5 px-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-2">

                                        <!-- If Multi-step Pipeline -> Single action button: Advance step by step -->
                                        @if($m->route->isPipeline() && $m->current_step_index < count($m->route->steps_json ?? []) - 1)
                                            @php
                                                $steps = $m->route->steps_json;
                                                $nextStepIndex = $m->current_step_index + 1;
                                                $nextStepName = $steps[$nextStepIndex] ?? 'ขั้นตอนถัดไป';
                                                $isFinalStep = ($nextStepIndex === count($steps) - 1);
                                            @endphp
                                            <form id="advance_form_{{ $m->id }}" method="POST" action="{{ route('dashboard.advance', $m) }}" class="inline">
                                                @csrf
                                                <button type="button" @click="submitAction('advance_form_{{ $m->id }}', {{ $m->id }})"
                                                    :disabled="actionSubmittingId === {{ $m->id }}"
                                                    class="px-3.5 py-2 rounded-xl {{ $isFinalStep ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white font-extrabold text-xs md:text-sm shadow-sm transition flex items-center space-x-1.5 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                                    title="{{ $isFinalStep ? 'ส่งต่อขั้นตอนสุดท้ายและสิ้นสุดกระบวนการ (ลงระบบเสร็จสิ้น)' : 'ส่งต่อไปยังขั้นตอนถัดไป' }}">
                                                    <span x-show="actionSubmittingId !== {{ $m->id }}" class="flex items-center space-x-1">
                                                        @if($isFinalStep)
                                                            <span>✓ ส่งต่อเสร็จสิ้น:</span>
                                                            <strong class="underline">{{ $nextStepName }}</strong>
                                                        @else
                                                            <span>⏩ ส่งต่อ:</span>
                                                            <strong class="underline">{{ $nextStepName }}</strong>
                                                        @endif
                                                    </span>
                                                    <span x-show="actionSubmittingId === {{ $m->id }}" x-cloak class="flex items-center">
                                                        ⏳ กำลังบันทึก...
                                                    </span>
                                                </button>
                                            </form>
                                        @else
                                            <!-- Point-to-Point Route OR already at final step -> Chunky Check-Out Button -->
                                            <form id="checkout_form_{{ $m->id }}" method="POST" action="{{ route('dashboard.checkout', $m) }}" class="inline">
                                                @csrf
                                                <button type="button" @click="submitAction('checkout_form_{{ $m->id }}', {{ $m->id }})"
                                                    :disabled="actionSubmittingId === {{ $m->id }}"
                                                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm shadow-sm transition flex items-center space-x-1.5 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                                    title="ส่งออกหนังสือ (ลงระบบเสร็จสิ้น)">
                                                    <span x-show="actionSubmittingId !== {{ $m->id }}">
                                                        📤 ส่งออกหนังสือ
                                                    </span>
                                                    <span x-show="actionSubmittingId === {{ $m->id }}" x-cloak class="flex items-center">
                                                        ⏳ กำลังส่งออก...
                                                    </span>
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Void Intake (Staff <= 30 min, Admin same day, Step 0) -->
                                        @if($m->canVoidIntake())
                                        <button type="button"
                                            @click="openVoidIntake({{ $m->id }}, '{{ $m->document->doc_number }}')"
                                            class="px-2.5 py-2 rounded-xl bg-red-100 hover:bg-red-200 text-red-950 font-black text-xs md:text-sm border-2 border-red-300 transition cursor-pointer"
                                            title="ยกเลิก/ลบรายการรับเข้า (กรณีพิมพ์ผิดเล่มหรือรับผิด)">
                                            🗑️ ยกเลิก
                                        </button>
                                        @endif

                                        <!-- Admin Recovery (Same-day Single Undo) -->
                                        @if(auth()->check() && auth()->user()->isAdmin() && $m->canAdminUndo() && $m->current_step_index > 0)
                                        <button type="button"
                                            @click="openRecovery('{{ route('admin.recovery.rollback', $m) }}', 'ย้อนขั้นตอนเลขรับ {{ $m->document->doc_number }}')"
                                            class="px-3 py-2 rounded-xl bg-purple-100 hover:bg-purple-200 text-purple-950 font-black text-xs md:text-sm border-2 border-purple-300 transition cursor-pointer"
                                            title="Admin: ย้อนขั้นตอน 1 ขั้น (เฉพาะรายการในวันเดียวกัน)">
                                            ↩ ย้อนขั้น
                                        </button>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                            @endif
                        @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-slate-400">
                                @if($activeTab === 'completed')
                                    <div class="text-4xl mb-3">📭</div>
                                    <div class="text-base font-bold text-slate-700">ยังไม่มีหนังสือที่ส่งออกเสร็จสิ้นในวันนี้</div>
                                    <div class="text-sm text-slate-400 mt-1">เมื่อมีการส่งออกหนังสือ รายการจะปรากฏที่นี่พร้อมสถานะ "ลงระบบเสร็จสิ้น"</div>
                                @else
                                    <div class="text-4xl mb-3">🎉</div>
                                    <div class="text-base font-bold text-slate-700">ไม่มีหนังสือค้างอยู่ระหว่างทาง</div>
                                    <div class="text-sm text-slate-400 mt-1">รับหนังสือเข้าใหม่โดยใช้ฟอร์มฝั่งซ้ายหรือกด <kbd class="bg-slate-200 px-1.5 py-0.5 rounded text-xs font-bold text-slate-800">Alt + N</kbd></div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            @if($movements->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50 shrink-0">
                {{ $movements->links() }}
            </div>
            @endif
        </div>

    </div>

    <!-- Edit Document Number Modal -->
    <div x-show="editDocModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="edit-doc-title" role="dialog" aria-modal="true">
        <div x-show="editDocModal" x-transition.opacity class="fixed inset-0 bg-slate-950/70 transition-opacity" @click="if(!editDocSubmitting) editDocModal = false"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto flex min-h-full items-center justify-center p-4 text-center sm:p-0 pointer-events-none">
            <div x-show="editDocModal" x-transition.scale.origin-center
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border-2 border-blue-400 pointer-events-auto z-20">
                <form :action="'{{ url('/dashboard/movements') }}/' + editDocMovementId + '/edit-doc-number'" method="POST"
                    @submit="if(editDocSubmitting || editDocReason.trim() === '' || newDocNumber.trim() === '') { $event.preventDefault(); return false; } editDocSubmitting = true;">
                    @csrf
                    <div class="bg-gradient-to-r from-blue-900 to-indigo-950 px-6 py-4 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <span class="text-2xl">✏️</span>
                            <h3 id="edit-doc-title" class="text-lg font-black tracking-tight">แก้ไขเลขรับ (Typo Correction)</h3>
                        </div>
                        <span class="text-xs bg-blue-700/80 text-blue-100 px-2.5 py-1 rounded-md font-bold font-mono border border-blue-600">แก้ไขเลขที่</span>
                    </div>

                    <div class="bg-white p-6 space-y-4">
                        <div class="p-4 bg-amber-50 border-2 border-amber-200 rounded-xl text-sm text-amber-950">
                            <strong class="text-base font-black block mb-1">ℹ️ กฎความถูกต้องและการตรวจสอบ:</strong>
                            <ul class="list-disc list-inside space-y-1 text-xs md:text-sm text-amber-900 font-medium">
                                <li>ผู้รับเข้าแก้ไขได้ภายใน <strong>30 นาที</strong> (Admin แก้ไขได้ตลอดวัน)</li>
                                <li>เลขรับใหม่ต้อง <strong>ไม่ซ้ำ</strong> กับหนังสือเล่มอื่นในระบบ</li>
                                <li>ระบบจะบันทึกประวัติเลขเดิมและผู้แก้ไขลง Audit Log</li>
                            </ul>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">เลขรับปัจจุบัน (เดิม):</label>
                            <div class="px-3.5 py-2.5 bg-slate-100 border border-slate-300 rounded-xl font-mono text-lg font-black text-slate-700" x-text="oldDocNumber"></div>
                        </div>

                        <div>
                            <label for="new_doc_number_input" class="block text-base font-extrabold text-slate-900 mb-1.5">
                                ระบุเลขรับที่ถูกต้อง <span class="text-red-600 font-black">*</span>
                            </label>
                            <input type="text" id="new_doc_number_input" name="new_doc_number" required x-model="newDocNumber"
                                class="mt-1 block w-full rounded-xl border-2 border-slate-300 shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-lg p-3 text-slate-900 font-mono font-black bg-white"
                                placeholder="เช่น 69-01-0005">
                        </div>

                        <div>
                            <label for="edit_doc_reason_input" class="block text-base font-extrabold text-slate-900 mb-1.5">
                                ระบุเหตุผลในการแก้ไข <span class="text-red-600 font-black">* (จำเป็น)</span>
                            </label>
                            <textarea id="edit_doc_reason_input" name="reason" rows="3" required x-model="editDocReason"
                                class="mt-1 block w-full rounded-xl border-2 border-slate-300 shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-base p-3 text-slate-900 font-medium bg-white"
                                placeholder="เช่น พิมพ์เลข 5 สลับเป็นเลข 6, ดูเลขที่จากปกหนังสือผิด"></textarea>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-3">
                        <button type="submit" :disabled="editDocSubmitting || editDocReason.trim() === '' || newDocNumber.trim() === '' || newDocNumber.trim() === oldDocNumber"
                            class="w-full inline-flex justify-center rounded-xl shadow-md px-6 py-3 bg-blue-700 text-base font-extrabold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition">
                            <span x-show="!editDocSubmitting">✓ บันทึกแก้ไขเลขรับ</span>
                            <span x-show="editDocSubmitting" x-cloak class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                ⏳ กำลังบันทึก...
                            </span>
                        </button>
                        <button type="button" @click="editDocModal = false" :disabled="editDocSubmitting"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border-2 border-slate-300 shadow-xs px-5 py-3 bg-white text-base font-bold text-slate-700 hover:bg-slate-100 sm:mt-0 sm:w-auto cursor-pointer transition">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Void Intake Modal -->
    <div x-show="voidModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="void-modal-title" role="dialog" aria-modal="true">
        <div x-show="voidModal" x-transition.opacity class="fixed inset-0 bg-slate-950/70 transition-opacity" @click="if(!voidSubmitting) voidModal = false"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto flex min-h-full items-center justify-center p-4 text-center sm:p-0 pointer-events-none">
            <div x-show="voidModal" x-transition.scale.origin-center
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border-2 border-red-400 pointer-events-auto z-20">
                <form :action="'{{ url('/dashboard/movements') }}/' + voidMovementId + '/void'" method="POST"
                    @submit="if(voidSubmitting || voidReason.trim() === '') { $event.preventDefault(); return false; } voidSubmitting = true;">
                    @csrf
                    <div class="bg-gradient-to-r from-red-900 to-rose-950 px-6 py-4 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <span class="text-2xl">🗑️</span>
                            <h3 id="void-modal-title" class="text-lg font-black tracking-tight">ยกเลิกรายการรับเข้า (Void Intake)</h3>
                        </div>
                        <span class="text-xs bg-red-700/90 text-red-100 px-2.5 py-1 rounded-md font-bold font-mono border border-red-600">ลบรายการรับ</span>
                    </div>

                    <div class="bg-white p-6 space-y-4">
                        <div class="p-4 bg-red-50 border-2 border-red-200 rounded-xl text-sm text-red-950">
                            <strong class="text-base font-black block mb-1">⚠️ คำเตือนการยกเลิกรายการ:</strong>
                            <ul class="list-disc list-inside space-y-1 text-xs md:text-sm text-red-900 font-medium">
                                <li>ยกเลิกได้เฉพาะหนังสือที่ <strong>ยังอยู่ที่ขั้นตอนรับเข้า (ขั้นแรก)</strong> เท่านั้น</li>
                                <li>ผู้รับเข้าทำได้ภายใน <strong>30 นาที</strong> (Admin ทำได้ตลอดวัน)</li>
                                <li>รายการรับเข้าและประวัติรอบนี้จะถูกลบออกถาวร</li>
                                <li>บันทึกประวัติการยกเลิกลง Audit Log เสมอ</li>
                            </ul>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">เลขรับหนังสือที่จะยกเลิก:</label>
                            <div class="px-3.5 py-2.5 bg-red-50 border border-red-200 rounded-xl font-mono text-xl font-black text-red-700" x-text="voidDocNumber"></div>
                        </div>

                        <div>
                            <label for="void_reason_input" class="block text-base font-extrabold text-slate-900 mb-1.5">
                                ระบุเหตุผลในการยกเลิก <span class="text-red-600 font-black">* (จำเป็น)</span>
                            </label>
                            <textarea id="void_reason_input" name="reason" rows="3" required x-model="voidReason"
                                class="mt-1 block w-full rounded-xl border-2 border-slate-300 shadow-sm focus:border-red-600 focus:ring-2 focus:ring-red-200 text-base p-3 text-slate-900 font-medium bg-white"
                                placeholder="เช่น รับผิดเล่ม, เจ้าหน้าที่ยื่นเอกสารซ้ำซ้อน, ยกเลิกการนำส่ง"></textarea>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-3">
                        <button type="submit" :disabled="voidSubmitting || voidReason.trim() === ''"
                            class="w-full inline-flex justify-center rounded-xl shadow-md px-6 py-3 bg-red-700 text-base font-extrabold text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition">
                            <span x-show="!voidSubmitting">🗑️ ยืนยันยกเลิกรายการรับ</span>
                            <span x-show="voidSubmitting" x-cloak class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                ⏳ กำลังยกเลิก...
                            </span>
                        </button>
                        <button type="button" @click="voidModal = false" :disabled="voidSubmitting"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border-2 border-slate-300 shadow-xs px-5 py-3 bg-white text-base font-bold text-slate-700 hover:bg-slate-100 sm:mt-0 sm:w-auto cursor-pointer transition">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
