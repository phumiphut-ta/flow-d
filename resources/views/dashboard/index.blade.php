@extends('layouts.app', ['title' => 'สมุดรับ-งานค้าง (Dual-Pane)'])

@section('content')
<div class="space-y-3" x-data="{
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
    <!-- Explicit Layout Styles to guarantee side-by-side display regardless of asset compilation/cache -->
    <style>
        .dual-pane-layout {
            display: flex;
            flex-direction: row;
            gap: 1rem;
            align-items: flex-start;
            width: 100%;
        }
        .left-pane-column {
            width: 320px;
            min-width: 280px;
            max-width: 340px;
            flex-shrink: 0;
        }
        .right-pane-column {
            flex: 1 1 0%;
            min-width: 0;
            min-height: 550px;
        }
        @media (max-width: 820px) {
            .dual-pane-layout {
                flex-direction: column;
            }
            .left-pane-column {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>

    <!-- Dual-Pane Layout: Left (~25% / w-72 to w-80), Right (Full remaining width min-w-0) -->
    <div class="dual-pane-layout flex flex-col md:flex-row gap-4 items-start w-full">

        <!-- LEFT PANE: Narrower (~24-25% / w-72 to w-80) -->
        <div class="left-pane-column w-full md:w-72 lg:w-80 shrink-0 flex flex-col space-y-3">
            <!-- Fast Intake Box -->
            <div class="bg-white border-2 border-blue-200 rounded-2xl shadow-sm flex flex-col overflow-hidden">
                <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 px-4 py-2.5 text-white flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-xl">📥</span>
                        <h2 class="font-extrabold text-sm md:text-base">รับหนังสือเข้าด่วน (Intake)</h2>
                    </div>
                    <kbd class="bg-blue-800 text-[11px] text-amber-300 font-bold px-1.5 py-0.5 rounded font-mono border border-blue-700">Alt+N</kbd>
                </div>

                <div class="p-3.5 space-y-3">
                    <form id="intake_form" method="POST" action="{{ route('dashboard.intake') }}"
                        @submit="if(intakeSubmitting) { $event.preventDefault(); return false; } intakeSubmitting = true;">
                        @csrf
                        <!-- Hidden automatic doc date (defaults to today) -->
                        <input type="hidden" name="doc_date" value="{{ date('Y-m-d') }}">

                        <!-- Doc Number (Extra Large, High Contrast) -->
                        <div class="mb-3">
                            <label for="intake_doc_number" class="block text-xs md:text-sm font-extrabold text-slate-800 mb-1">
                                เลขรับหนังสือ <span class="text-red-600">*</span>
                            </label>
                            <input type="text" id="intake_doc_number" name="doc_number" value="{{ old('doc_number') }}" required autofocus
                                autocomplete="off"
                                class="w-full px-3 py-2 rounded-xl border-2 border-blue-500 bg-blue-50/20 text-slate-950 font-mono text-lg font-black focus:border-blue-700 focus:bg-white focus:ring-2 focus:ring-blue-300 placeholder-slate-400 transition"
                                placeholder="เช่น 123/69">
                            <span class="text-[11px] font-semibold text-slate-500 mt-0.5 block">พิมพ์เสร็จกด <kbd class="bg-slate-200 px-1 py-0.5 rounded border border-slate-300 font-bold text-slate-800">Enter</kbd> รับเข้าทันที</span>
                        </div>

                        <!-- Route Selection -->
                        <div class="mb-3.5">
                            <label for="intake_route_id" class="block text-xs md:text-sm font-extrabold text-slate-800 mb-1">
                                เส้นทางเดินเอกสาร (Route) <span class="text-red-600">*</span>
                            </label>
                            <select id="intake_route_id" name="route_id" required
                                class="w-full h-10 px-3 rounded-xl border-2 border-slate-300 text-xs md:text-sm font-bold text-slate-900 focus:border-blue-600 focus:ring-0 bg-white">
                                @foreach($activeRoutes as $rt)
                                <option value="{{ $rt->id }}" {{ old('route_id') == $rt->id ? 'selected' : '' }}>
                                    {{ $rt->route_name }} ({{ $rt->isPipeline() ? count($rt->steps_json).' ขั้น' : 'จุดเดียว' }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Submit Button with Anti-Double Click -->
                        <button type="submit" :disabled="intakeSubmitting"
                            class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm md:text-base transition shadow-md shadow-blue-500/20 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <span x-show="!intakeSubmitting" class="flex items-center space-x-1.5">
                                <span>📥 บันทึกรับหนังสือเข้า</span>
                                <span class="text-xs font-normal opacity-90">(Enter)</span>
                            </span>
                            <span x-show="intakeSubmitting" x-cloak class="flex items-center text-sm">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                ⏳ กำลังบันทึก...
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Statistics Cards Section (Moved to Bottom of Left Pane) -->
            <div class="flex flex-col space-y-2">
                <!-- 1. Active Pending Card -->
                <a href="{{ route('dashboard', ['tab' => 'active']) }}"
                    class="bg-white border-2 {{ $activeTab === 'active' ? 'border-amber-500 ring-2 ring-amber-300' : 'border-amber-200 hover:border-amber-400' }} rounded-xl p-3 shadow-xs flex items-center justify-between transition cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-800 text-lg">
                            ⏳
                        </div>
                        <div>
                            <span class="text-xs font-bold text-amber-800 block">งานค้างอยู่ระหว่างทาง</span>
                            <div class="flex items-baseline space-x-1">
                                <span class="text-xl font-black text-slate-900">{{ number_format($totalActive) }}</span>
                                <span class="text-xs font-semibold text-slate-500">เรื่อง</span>
                            </div>
                        </div>
                    </div>
                    <span class="text-slate-400 text-xs font-bold">→</span>
                </a>

                <!-- 2. Today's Intake Card -->
                <div class="bg-white border-2 border-blue-200 rounded-xl p-3 shadow-xs flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-800 text-lg">
                            📥
                        </div>
                        <div>
                            <span class="text-xs font-bold text-blue-800 block">รับเข้าวันนี้</span>
                            <div class="flex items-baseline space-x-1">
                                <span class="text-xl font-black text-slate-900">{{ number_format($todayIntake) }}</span>
                                <span class="text-xs font-semibold text-slate-500">เรื่อง</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Completed Today Card -->
                <a href="{{ route('dashboard', ['tab' => 'completed']) }}"
                    class="bg-white border-2 {{ $activeTab === 'completed' ? 'border-emerald-500 ring-2 ring-emerald-300' : 'border-emerald-200 hover:border-emerald-400' }} rounded-xl p-3 shadow-xs flex items-center justify-between transition cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-800 text-lg">
                            ✅
                        </div>
                        <div>
                            <span class="text-xs font-bold text-emerald-800 block">ส่งออกเสร็จสิ้นวันนี้</span>
                            <div class="flex items-baseline space-x-1">
                                <span class="text-xl font-black text-slate-900">{{ number_format($todayCompleted) }}</span>
                                <span class="text-xs font-semibold text-slate-500">เรื่อง</span>
                            </div>
                        </div>
                    </div>
                    <span class="text-slate-400 text-xs font-bold">→</span>
                </a>
            </div>
        </div>

        <!-- RIGHT PANE: Worklist Table (Always visible, min-w-0, min-h-[500px]) -->
        <div class="right-pane-column w-full md:flex-1 min-w-0 bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col min-h-[500px]">
            <!-- Mode Tabs: Active Worklist vs Today's Completed -->
            <div class="flex items-center border-b-2 border-slate-200 bg-slate-100 px-3 pt-2 gap-2 shrink-0">
                <a href="{{ route('dashboard', array_filter(['tab' => 'active', 'route_id' => $currentRouteFilter, 'step' => $currentStepFilter, 'search' => $currentSearch])) }}"
                    class="px-4 py-2 rounded-t-xl font-black text-xs md:text-sm flex items-center space-x-2 transition border-t-2 border-x-2 {{ $activeTab === 'active' ? 'bg-white border-blue-500 text-blue-900 shadow-sm -mb-[2px]' : 'bg-slate-200/80 border-transparent text-slate-600 hover:bg-slate-200 hover:text-slate-900' }}">
                    <span>⏳ งานค้างอยู่ระหว่างทาง</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-black {{ $activeTab === 'active' ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-slate-300 text-slate-700' }}">{{ number_format($totalActive) }}</span>
                </a>

                <a href="{{ route('dashboard', array_filter(['tab' => 'completed', 'route_id' => $currentRouteFilter, 'step' => $currentStepFilter, 'search' => $currentSearch])) }}"
                    class="px-4 py-2 rounded-t-xl font-black text-xs md:text-sm flex items-center space-x-2 transition border-t-2 border-x-2 {{ $activeTab === 'completed' ? 'bg-white border-emerald-500 text-emerald-900 shadow-sm -mb-[2px]' : 'bg-slate-200/80 border-transparent text-slate-600 hover:bg-slate-200 hover:text-slate-900' }}">
                    <span>✅ ส่งออกหนังสือเสร็จสิ้นวันนี้</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-black {{ $activeTab === 'completed' ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-slate-300 text-slate-700' }}">{{ number_format($todayCompleted) }}</span>
                </a>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="p-2.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 shrink-0">
                <form id="search_form" method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2 flex-1">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">

                    <!-- Route Filter -->
                    <select name="route_id" onchange="this.form.submit()"
                        class="h-9 px-2.5 rounded-lg border border-slate-300 text-xs font-bold bg-white text-slate-800 focus:border-blue-500 focus:ring-0 cursor-pointer">
                        <option value="">-- กรองทุกเส้นทาง --</option>
                        @foreach($activeRoutes as $rt)
                        <option value="{{ $rt->id }}" {{ $currentRouteFilter == $rt->id ? 'selected' : '' }}>
                            {{ $rt->route_name }}
                        </option>
                        @endforeach
                    </select>

                    <!-- Current Step Filter (New Requirement 4) -->
                    @if($activeTab === 'active' && !empty($availableSteps))
                    <select name="step" onchange="this.form.submit()"
                        class="h-9 px-2.5 rounded-lg border border-slate-300 text-xs font-bold bg-white text-slate-800 focus:border-blue-500 focus:ring-0 cursor-pointer">
                        <option value="">-- กรองทุกขั้นตอน --</option>
                        @foreach($availableSteps as $st)
                        <option value="{{ $st }}" {{ $currentStepFilter === $st ? 'selected' : '' }}>
                            📍 {{ $st }}
                        </option>
                        @endforeach
                    </select>
                    @endif

                    <!-- Instant Real-Time Quick Search Box (HotKey: /) -->
                    <div class="relative flex-1 min-w-[200px] max-w-xs">
                        <input type="text" id="quick_search_input" name="search" value="{{ $currentSearch }}"
                            placeholder="พิมพ์ค้นหาเลขรับทันที... (กด /)"
                            autocomplete="off"
                            oninput="
                                const q = this.value.toLowerCase().trim();
                                document.querySelectorAll('.worklist-row').forEach(function(r) {
                                    const text = (r.getAttribute('data-search') || '').toLowerCase();
                                    r.style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
                                });
                                const clearBtn = document.getElementById('search_clear_btn');
                                if (clearBtn) clearBtn.style.display = q ? 'flex' : 'none';
                            "
                            class="w-full h-9 pl-8 pr-7 rounded-lg border border-slate-300 text-xs font-semibold focus:border-blue-500 focus:ring-0 text-slate-900">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none">🔍</span>
                        <button type="button" id="search_clear_btn"
                            style="display: {{ $currentSearch ? 'flex' : 'none' }};"
                            onclick="
                                document.getElementById('quick_search_input').value = '';
                                document.querySelectorAll('.worklist-row').forEach(function(r) { r.style.display = ''; });
                                this.style.display = 'none';
                                if ('{{ $currentSearch }}') { document.getElementById('search_form').submit(); }
                            "
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 font-bold text-[10px] bg-slate-200 hover:bg-slate-300 w-4 h-4 rounded-full items-center justify-center cursor-pointer"
                            title="ล้างข้อความค้นหา">
                            ✕
                        </button>
                    </div>

                    @if($currentRouteFilter || $currentSearch || $currentStepFilter)
                    <a href="{{ route('dashboard', ['tab' => $activeTab]) }}" class="h-9 px-2 text-xs font-bold text-slate-500 hover:text-slate-800 underline flex items-center">
                        ล้างตัวกรอง
                    </a>
                    @endif
                </form>

                <div class="text-xs font-semibold text-slate-600">
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
                class="px-4 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 border-b border-blue-300 flex items-center justify-between transition shrink-0">
                <div class="flex items-center space-x-2 text-xs md:text-sm font-bold text-blue-950">
                    <span>✓ เลือกแล้ว: <span x-text="selectedIds.length" class="text-blue-800 font-black text-sm"></span> รายการ</span>
                </div>

                <div class="flex items-center space-x-2">
                    <!-- Batch Check-out -->
                    <form method="POST" action="{{ route('dashboard.batch.checkout') }}"
                        @submit="if(batchSubmitting) { $event.preventDefault(); return false; } batchSubmitting = true;">
                        @csrf
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="movement_ids[]" :value="id">
                        </template>
                        <button type="submit" :disabled="batchSubmitting"
                            class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-xs transition flex items-center space-x-1 disabled:opacity-50 cursor-pointer">
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
                            class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow-xs transition flex items-center space-x-1 disabled:opacity-50 cursor-pointer">
                            <span>⏩ ส่งต่อขั้นตอนทั้งชุด</span>
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- High-Density Compact Data Grid -->
            <div class="overflow-x-auto w-full">
                <table id="worklist_table" class="w-full text-left border-collapse">
                    <thead class="bg-slate-100 text-slate-800 uppercase font-bold text-xs border-b border-slate-200">
                        @if($activeTab === 'completed')
                        <tr>
                            <th class="py-2.5 px-3">เลขรับ</th>
                            <th class="py-2.5 px-3">วันที่หนังสือ</th>
                            <th class="py-2.5 px-3">เส้นทาง (Route)</th>
                            <th class="py-2.5 px-3">รับเข้าเมื่อ</th>
                            <th class="py-2.5 px-3">ส่งออกเมื่อ</th>
                            <th class="py-2.5 px-3">ผู้ส่งออก</th>
                            <th class="py-2.5 px-3">สถานะ</th>
                            <th class="py-2.5 px-3 text-right">การจัดการ</th>
                        </tr>
                        @else
                        <tr>
                            <th class="py-2.5 px-2.5 w-8 text-center">
                                <input type="checkbox" @click="toggleSelectAll()" :checked="selectAll"
                                    class="w-4 h-4 rounded border-2 border-slate-400 text-blue-600 focus:ring-0 cursor-pointer">
                            </th>
                            <th class="py-2.5 px-2.5">เลขรับ</th>
                            <th class="py-2.5 px-2.5">วันที่หนังสือ</th>
                            <th class="py-2.5 px-2.5">เส้นทาง (Route)</th>
                            <th class="py-2.5 px-2.5">ขั้นตอนปัจจุบัน</th>
                            <th class="py-2.5 px-2.5">เวลาที่ค้าง</th>
                            <th class="py-2.5 px-2.5">ผู้รับเข้า</th>
                            <th class="py-2.5 px-2.5 text-right">ดำเนินการ (Actions)</th>
                        </tr>
                        @endif
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800">
                        @forelse($movements as $m)
                            @if($activeTab === 'completed')
                            <tr class="worklist-row hover:bg-emerald-50/60 transition {{ $loop->even ? 'bg-slate-50/60' : 'bg-white' }}"
                                data-search="{{ $m->document->doc_number }} {{ $m->current_step_name }} {{ $m->route->route_name }} {{ $m->outUser?->name ?? '' }}">
                                <!-- Doc Number & Round -->
                                <td class="py-2 px-3 whitespace-nowrap">
                                    <div class="flex items-center space-x-1.5">
                                        <a href="{{ route('history.show', $m->document_id) }}" class="font-black text-blue-700 hover:text-blue-900 font-mono text-sm md:text-base underline-offset-2 hover:underline">
                                            {{ $m->document->doc_number }}
                                        </a>
                                        @if($m->round_number > 1)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-purple-100 text-purple-800 border border-purple-300">
                                            รอบ {{ $m->round_number }}
                                        </span>
                                        @endif

                                        @if($m->canCorrectIntake())
                                        <!-- Quick Edit Doc Number button -->
                                        <button type="button" @click="openEditDocNumber({{ $m->id }}, '{{ $m->document->doc_number }}')"
                                            class="p-0.5 rounded text-slate-400 hover:text-blue-700 hover:bg-blue-100 transition cursor-pointer text-xs"
                                            title="แก้ไขเลขรับ (กรณีพิมพ์ผิด)">
                                            ✏️
                                        </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Doc Date -->
                                <td class="py-2 px-3 whitespace-nowrap text-xs font-semibold text-slate-700">
                                    {{ $m->document->doc_date_thai }}
                                </td>

                                <!-- Route -->
                                <td class="py-2 px-3">
                                    <span class="font-bold text-xs text-slate-900 block truncate max-w-[150px]" title="{{ $m->route->route_name }}">
                                        {{ $m->route->route_name }}
                                    </span>
                                </td>

                                <!-- In Datetime -->
                                <td class="py-2 px-3 whitespace-nowrap text-xs font-semibold text-slate-600">
                                    {{ $m->in_datetime_thai }}
                                </td>

                                <!-- Out Datetime -->
                                <td class="py-2 px-3 whitespace-nowrap text-xs font-bold text-slate-900">
                                    {{ $m->out_datetime_thai }}
                                </td>

                                <!-- Out User -->
                                <td class="py-2 px-3 whitespace-nowrap text-xs font-bold text-slate-900">
                                    {{ $m->outUser?->name ?? '-' }}
                                </td>

                                <!-- Status Badge -->
                                <td class="py-2 px-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        ✓ ลงระบบเสร็จสิ้น
                                    </span>
                                </td>

                                <!-- Actions & Admin Recovery -->
                                <td class="py-2 px-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <a href="{{ route('history.show', $m->document_id) }}"
                                            class="px-2.5 py-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold transition">
                                            ดูประวัติ
                                        </a>

                                        @if(auth()->check() && auth()->user()->isAdmin() && $m->canAdminUndo())
                                        <button type="button"
                                            @click="openRecovery('{{ route('admin.recovery.reopen', $m) }}', 'ยกเลิกการส่งออกเลขรับ {{ $m->document->doc_number }}')"
                                            class="px-2.5 py-1 rounded-lg bg-purple-100 hover:bg-purple-200 text-purple-900 font-bold text-xs border border-purple-300 transition cursor-pointer"
                                            title="Admin: ยกเลิกการส่งออกและดึงกลับมาเป็นงานค้าง">
                                            ↩ กู้คืน
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @else
                            <tr class="worklist-row hover:bg-blue-50/70 transition {{ $loop->even ? 'bg-slate-50/60' : 'bg-white' }}"
                                data-search="{{ $m->document->doc_number }} {{ $m->current_step_name }} {{ $m->route->route_name }} {{ $m->inUser?->name ?? '' }}">
                                <!-- Checkbox -->
                                <td class="py-2 px-2.5 text-center">
                                    <input type="checkbox" value="{{ $m->id }}" x-model.number="selectedIds"
                                        class="row-checkbox w-4 h-4 rounded border-2 border-slate-400 text-blue-600 focus:ring-0 cursor-pointer">
                                </td>

                                <!-- Doc Number & Round -->
                                <td class="py-2 px-2.5 whitespace-nowrap">
                                    <div class="flex items-center space-x-1.5">
                                        <a href="{{ route('history.show', $m->document_id) }}" class="font-black text-blue-700 hover:text-blue-900 font-mono text-sm md:text-base underline-offset-2 hover:underline">
                                            {{ $m->document->doc_number }}
                                        </a>
                                        @if($m->round_number > 1)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-purple-100 text-purple-800 border border-purple-300">
                                            รอบ {{ $m->round_number }}
                                        </span>
                                        @endif

                                        @if($m->canCorrectIntake())
                                        <!-- Quick Edit Doc Number button -->
                                        <button type="button" @click="openEditDocNumber({{ $m->id }}, '{{ $m->document->doc_number }}')"
                                            class="p-0.5 rounded text-slate-400 hover:text-blue-700 hover:bg-blue-100 transition cursor-pointer text-xs"
                                            title="แก้ไขเลขรับ (กรณีพิมพ์ผิด)">
                                            ✏️
                                        </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Doc Date (Thai Buddhist Era) -->
                                <td class="py-2 px-2.5 whitespace-nowrap text-xs font-semibold text-slate-700">
                                    {{ $m->document->doc_date_thai }}
                                </td>

                                <!-- Route -->
                                <td class="py-2 px-2.5">
                                    <span class="font-bold text-xs text-slate-900 block truncate max-w-[140px]" title="{{ $m->route->route_name }}">
                                        {{ $m->route->route_name }}
                                    </span>
                                </td>

                                <!-- Current Step -->
                                <td class="py-2 px-2.5 whitespace-nowrap">
                                    <div class="flex items-center space-x-1">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="font-bold text-emerald-950 bg-emerald-100 border border-emerald-300 px-2 py-0.5 rounded text-xs">
                                            {{ $m->current_step_name ?: 'รับเข้าระบบ' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Waiting Duration -->
                                <td class="py-2 px-2.5 whitespace-nowrap">
                                    <span class="text-amber-950 font-bold bg-amber-100 px-1.5 py-0.5 rounded text-[11px] border border-amber-300">
                                        ⏳ {{ $m->waiting_duration }}
                                    </span>
                                </td>

                                <!-- Intake User -->
                                <td class="py-2 px-2.5 whitespace-nowrap">
                                    <span class="text-slate-900 font-bold text-xs block leading-tight">{{ $m->inUser?->name ?? '-' }}</span>
                                    <span class="text-[10px] text-slate-500 block">{{ $m->in_datetime_thai }}</span>
                                </td>

                                <!-- Compact Action Buttons -->
                                <td class="py-2 px-2.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-1.5">

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
                                                    class="px-2.5 py-1 rounded-lg {{ $isFinalStep ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white font-bold text-xs shadow-xs transition flex items-center space-x-1 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
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
                                            <!-- Point-to-Point Route OR already at final step -> Check-Out Button -->
                                            <form id="checkout_form_{{ $m->id }}" method="POST" action="{{ route('dashboard.checkout', $m) }}" class="inline">
                                                @csrf
                                                <button type="button" @click="submitAction('checkout_form_{{ $m->id }}', {{ $m->id }})"
                                                    :disabled="actionSubmittingId === {{ $m->id }}"
                                                    class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center space-x-1 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
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
                                            class="px-2 py-1 rounded-lg bg-red-100 hover:bg-red-200 text-red-950 font-bold text-xs border border-red-300 transition cursor-pointer"
                                            title="ยกเลิก/ลบรายการรับเข้า (กรณีพิมพ์ผิดเล่มหรือรับผิด)">
                                            🗑️ ยกเลิก
                                        </button>
                                        @endif

                                        <!-- Admin Recovery (Same-day Single Undo) -->
                                        @if(auth()->check() && auth()->user()->isAdmin() && $m->canAdminUndo() && $m->current_step_index > 0)
                                        <button type="button"
                                            @click="openRecovery('{{ route('admin.recovery.rollback', $m) }}', 'ย้อนขั้นตอนเลขรับ {{ $m->document->doc_number }}')"
                                            class="px-2 py-1 rounded-lg bg-purple-100 hover:bg-purple-200 text-purple-950 font-bold text-xs border border-purple-300 transition cursor-pointer"
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
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                @if($activeTab === 'completed')
                                    <div class="text-3xl mb-2">📭</div>
                                    <div class="text-sm font-bold text-slate-700">ยังไม่มีหนังสือที่ส่งออกเสร็จสิ้นในวันนี้</div>
                                    <div class="text-xs text-slate-400 mt-0.5">เมื่อมีการส่งออกหนังสือ รายการจะปรากฏที่นี่พร้อมสถานะ "ลงระบบเสร็จสิ้น"</div>
                                @else
                                    <div class="text-3xl mb-2">🎉</div>
                                    <div class="text-sm font-bold text-slate-700">ไม่มีหนังสือค้างอยู่ระหว่างทาง</div>
                                    <div class="text-xs text-slate-400 mt-0.5">รับหนังสือเข้าใหม่โดยใช้ฟอร์มฝั่งซ้ายหรือกด <kbd class="bg-slate-200 px-1 py-0.5 rounded text-[11px] font-bold text-slate-800">Alt + N</kbd></div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            @if($movements->hasPages())
            <div class="p-2.5 border-t border-slate-200 bg-slate-50 shrink-0">
                {{ $movements->links() }}
            </div>
            @endif
        </div>

    </div>

    <!-- Edit Doc Number Modal -->
    <div x-show="editDocModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/60" @click="editDocModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 z-10 border border-slate-200" x-show="editDocModal">
                <div class="flex items-center space-x-2.5 text-blue-900 mb-3">
                    <span class="text-2xl">✏️</span>
                    <h3 class="text-lg font-black">แก้ไขเลขรับหนังสือ (Typo Correction)</h3>
                </div>
                <p class="text-xs text-slate-600 mb-4">
                    แก้ไขเลขรับหนังสือที่พิมพ์ผิดพลาด ระบบจะบันทึกประวัติการแก้ไขและเหตุผลลงใน Audit Log โดยอัตโนมัติ
                </p>

                <form :action="'{{ url('/dashboard/movements') }}/' + editDocMovementId + '/edit-doc-number'" method="POST"
                    @submit="if(editDocSubmitting || newDocNumber.trim() === '') { $event.preventDefault(); return false; } editDocSubmitting = true;">
                    @csrf
                    <div class="space-y-3 text-xs md:text-sm">
                        <div>
                            <label class="block font-bold text-slate-500 mb-1">เลขรับเดิม</label>
                            <input type="text" :value="oldDocNumber" disabled
                                class="w-full px-3 py-2 border rounded-lg bg-slate-100 font-mono font-bold text-slate-600">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">เลขรับที่ถูกต้อง <span class="text-red-500">*</span></label>
                            <input type="text" id="new_doc_number_input" name="new_doc_number" x-model="newDocNumber" required
                                class="w-full px-3 py-2 border-2 border-blue-500 rounded-lg font-mono font-black text-slate-900 text-base focus:border-blue-700 focus:ring-0">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">เหตุผลในการแก้ไข</label>
                            <textarea name="reason" x-model="editDocReason" rows="2" placeholder="เช่น พิมพ์เลขท้ายสลับกัน, ระบุเลขผิดเล่ม"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-0 text-xs"></textarea>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end space-x-2">
                        <button type="button" @click="editDocModal = false" class="px-3.5 py-2 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-200">
                            ยกเลิก
                        </button>
                        <button type="submit" :disabled="editDocSubmitting || newDocNumber.trim() === ''"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold rounded-lg text-xs disabled:opacity-50">
                            <span x-show="!editDocSubmitting">บันทึกการแก้ไข</span>
                            <span x-show="editDocSubmitting" x-cloak>กำลังบันทึก...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Void Intake Modal -->
    <div x-show="voidModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/60" @click="voidModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 z-10 border border-slate-200" x-show="voidModal">
                <div class="flex items-center space-x-2.5 text-red-600 mb-3">
                    <span class="text-2xl">🗑️</span>
                    <h3 class="text-lg font-black">ยกเลิกการรับหนังสือเข้า (Void Intake)</h3>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4">
                    <p class="text-xs text-red-800 font-medium">
                        คุณกำลังจะยกเลิกการรับเข้าของเลขรับ <strong class="font-mono text-red-950 font-bold" x-text="voidDocNumber"></strong>
                        ระบบจะลบข้อมูลรอบการเดินนี้ออกจากงานค้าง และบันทึกประวัติไว้ใน Audit Log
                    </p>
                </div>

                <form :action="'{{ url('/dashboard/movements') }}/' + voidMovementId + '/void'" method="POST"
                    @submit="if(voidSubmitting || voidReason.trim() === '') { $event.preventDefault(); return false; } voidSubmitting = true;">
                    @csrf
                    <div class="space-y-3 text-xs md:text-sm">
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">เหตุผลในการยกเลิก <span class="text-red-500">*</span></label>
                            <textarea id="void_reason_input" name="reason" x-model="voidReason" required rows="3"
                                placeholder="ระบุเหตุผล เช่น รับซ้ำ, บันทึกผิดฉบับ, ยกเลิกตามคำสั่งหัวหน้างาน"
                                class="w-full px-3 py-2 border-2 border-red-300 rounded-lg focus:border-red-500 focus:ring-0 text-xs"></textarea>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end space-x-2">
                        <button type="button" @click="voidModal = false" class="px-3.5 py-2 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-200">
                            ยกเลิก
                        </button>
                        <button type="submit" :disabled="voidSubmitting || voidReason.trim() === ''"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold rounded-lg text-xs disabled:opacity-50">
                            <span x-show="!voidSubmitting">ยืนยันการยกเลิกรับเข้า</span>
                            <span x-show="voidSubmitting" x-cloak>กำลังยกเลิก...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
