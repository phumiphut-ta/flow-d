@extends('layouts.app', ['title' => 'ประวัติหนังสือเลขรับ ' . $document->doc_number])

@section('content')
<div class="max-w-5xl mx-auto py-4 space-y-4">
    <!-- Top Back button & Title Card -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('history.index') }}" class="hover:underline flex items-center">
                    &larr; กลับหน้ารายการสมุดคุม
                </a>
                <span>•</span>
                <span>รหัสเอกสาร #{{ $document->id }}</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 font-mono tracking-tight">
                    {{ $document->doc_number }}
                </h1>
                @if($document->current_status === 'completed')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                        ✓ ลงระบบเสร็จสิ้น
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                        ⏳ อยู่ระหว่างทาง
                    </span>
                @endif
            </div>
            <div class="text-xs text-slate-500 mt-1">
                ลงวันที่ในหนังสือ: <strong class="text-slate-700">{{ $document->doc_date_readable }}</strong> ({{ $document->doc_date_thai }})
                • บันทึกเข้าระบบครั้งแรกโดย: {{ $document->creator?->name ?? '-' }}
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('liff.tracking', ['doc_number' => $document->doc_number]) }}" target="_blank"
                class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs flex items-center space-x-1 transition">
                <span>📱 เปิดหน้าค้นหาสถานะหนังสือ</span>
            </a>
        </div>
    </div>

    <!-- Movement Rounds Card List -->
    <div class="space-y-4">
        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
            <span>🔄</span>
            <span>ประวัติรอบการเดินเรื่อง (รวมทั้งหมด {{ $document->movements->count() }} รอบ)</span>
        </h2>

        @foreach($document->movements as $m)
        <div class="bg-white rounded-xl border {{ $m->is_returned ? 'border-slate-200' : 'border-blue-400 ring-2 ring-blue-100' }} shadow-xs overflow-hidden">
            <!-- Round Header -->
            <div class="{{ $m->is_returned ? 'bg-slate-50 border-b border-slate-200' : 'bg-gradient-to-r from-blue-900 to-indigo-900 text-white' }} px-5 py-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center space-x-3">
                    <span class="px-2.5 py-0.5 rounded-md text-xs font-black {{ $m->is_returned ? 'bg-slate-200 text-slate-800' : 'bg-blue-600 text-white' }}">
                        รอบที่ {{ $m->round_number }}
                    </span>
                    <strong class="text-sm font-bold {{ $m->is_returned ? 'text-slate-800' : 'text-white' }}">
                        {{ $m->route->route_name }}
                    </strong>
                </div>

                <div class="flex items-center space-x-2 text-xs">
                    @if($m->is_returned)
                        <span class="text-emerald-800 font-bold bg-emerald-100 px-3 py-1 rounded-md border border-emerald-300">
                            ✓ ส่งออกหนังสือแล้ว (ลงระบบเสร็จสิ้น เมื่อ {{ $m->out_datetime_thai }})
                        </span>

                        <!-- Admin Re-open button -->
                        @if(auth()->check() && auth()->user()->isAdmin() && $m->canAdminUndo())
                        <button type="button"
                            @click="openRecovery('{{ route('admin.recovery.reopen', $m) }}', 'ยกเลิกการส่งออกเลขรับ {{ $document->doc_number }} รอบที่ {{ $m->round_number }}')"
                            class="px-3 py-1.5 rounded-lg bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs shadow-xs transition">
                            ↩ ยกเลิกการส่งออก (Re-open)
                        </button>
                        @endif
                    @else
                        <span class="text-amber-300 font-bold bg-amber-950/60 px-2 py-0.5 rounded border border-amber-800">
                            🟢 กำลังดำเนินการ: {{ $m->current_step_name }} (ค้างอยู่ {{ $m->waiting_duration }})
                        </span>

                        <!-- Admin Rollback button -->
                        @if(auth()->check() && auth()->user()->isAdmin() && $m->canAdminUndo() && $m->current_step_index > 0)
                        <button type="button"
                            @click="openRecovery('{{ route('admin.recovery.rollback', $m) }}', 'ย้อนขั้นตอนเลขรับ {{ $document->doc_number }} รอบที่ {{ $m->round_number }}')"
                            class="px-2.5 py-1 rounded bg-purple-600 hover:bg-purple-700 text-white font-bold text-[11px] shadow-xs transition">
                            ↩ ย้อนขั้นตอนล่าสุด
                        </button>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Round Body & Timeline -->
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-4 mb-4 border-b border-slate-100 text-xs text-slate-600">
                    <div>
                        <span class="text-slate-400 block text-[11px]">วันเวลาและผู้รับเข้ารอบนี้</span>
                        <strong class="text-slate-800 font-medium">{{ $m->in_datetime_thai }}</strong>
                        <span class="text-slate-500">โดย {{ $m->inUser?->name ?? '-' }} (IP: {{ $m->in_ip_address ?: '-' }})</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">วันเวลาและผู้ส่งออก</span>
                        @if($m->out_datetime)
                            <strong class="text-slate-800 font-medium">{{ $m->out_datetime_thai }}</strong>
                            <span class="text-slate-500">โดย {{ $m->outUser?->name ?? '-' }} (IP: {{ $m->out_ip_address ?: '-' }})</span>
                        @else
                            <span class="text-amber-600 italic">ยังไม่ได้ส่งออกหนังสือ (อยู่ระหว่างทาง)</span>
                        @endif
                    </div>
                </div>

                <!-- Step Logs Checkpoint Timeline -->
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                        ไทม์ไลน์ขั้นตอนการเดินเรื่อง (Checkpoint Logs):
                    </h3>

                    <div class="relative pl-6 space-y-4 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        @forelse($m->stepLogs as $log)
                        <div class="relative">
                            <!-- Bullet Icon -->
                            <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full border-2 border-white shadow-xs flex items-center justify-center text-[10px]
                                {{ $log->status === 'completed' ? 'bg-blue-600' : ($log->status === 'current' ? 'bg-emerald-500 animate-pulse' : ($log->status === 'rollbacked' ? 'bg-red-500' : 'bg-slate-300')) }}">
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-slate-900 text-sm">
                                        {{ $log->step_index + 1 }}. {{ $log->step_name }}
                                    </span>
                                    @if($log->status === 'completed')
                                        <span class="ml-2 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-1.5 py-0.2 rounded">เสร็จสิ้น</span>
                                    @elseif($log->status === 'current')
                                        <span class="ml-2 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.2 rounded">ตำแหน่งปัจจุบัน</span>
                                    @elseif($log->status === 'rollbacked')
                                        <span class="ml-2 text-[10px] font-bold text-red-700 bg-red-50 border border-red-200 px-1.5 py-0.2 rounded">ย้อนคืนแล้ว</span>
                                    @endif
                                </div>

                                <div class="text-slate-500 text-[11px] mt-0.5 sm:mt-0">
                                    <span>📅 {{ $log->action_datetime_thai }}</span>
                                    <span class="mx-1">•</span>
                                    <span>👤 {{ $log->actionUser?->name ?? 'ระบบ' }}</span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-xs text-slate-400 italic">ไม่มีบันทึกขั้นตอนย่อย (Point-to-Point)</div>
                        @endforelse

                        @if($m->is_returned && $m->out_datetime)
                        <div class="relative">
                            <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-emerald-600 border-2 border-white shadow-xs flex items-center justify-center text-white text-[10px]">
                                ✓
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-emerald-950 text-sm">
                                        ✓ ส่งออกหนังสือ (ลงระบบเสร็จสิ้น)
                                    </span>
                                    <span class="ml-2 text-[10px] font-bold text-emerald-800 bg-emerald-100 border border-emerald-300 px-1.5 py-0.5 rounded">เสร็จสิ้น</span>
                                </div>
                                <div class="text-slate-500 text-[11px] mt-0.5 sm:mt-0">
                                    <span>📅 {{ $m->out_datetime_thai }}</span>
                                    <span class="mx-1">•</span>
                                    <span>👤 {{ $m->outUser?->name ?? 'ระบบ' }}</span>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

