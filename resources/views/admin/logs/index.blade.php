@extends('layouts.app', ['title' => 'Activity & Device Logs'])

@section('content')
<div class="max-w-6xl mx-auto py-4 space-y-4">
    <!-- Header -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
        <div>
            <h1 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>📜</span>
                <span>บันทึกประวัติการกระทำ & ทะเบียนเครื่อง (Audit & Device Logs)</span>
            </h1>
            <p class="text-xs text-slate-500">ตรวจสอบประวัติการกู้คืน (Recovery Audit Trail), การทำรายการแบบชุด (Batch), และอุปกรณ์ที่เข้าใช้งาน</p>
        </div>
    </div>

    <!-- Activity & Recovery Logs Table -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
                <span>🛡️</span>
                <span>บันทึกการกระทำในระบบ (Activity & Recovery Logs)</span>
            </h2>
            <form method="GET" action="{{ route('admin.logs.index') }}" class="flex items-center space-x-2 text-xs">
                <select name="action" onchange="this.form.submit()" class="px-2 py-1 border border-slate-300 rounded text-xs bg-white">
                    <option value="">-- กรองประเภท Action --</option>
                    <option value="doc_number_edit" {{ request('action') === 'doc_number_edit' ? 'selected' : '' }}>แก้ไขเลขรับ (Typo Correction)</option>
                    <option value="intake_void" {{ request('action') === 'intake_void' ? 'selected' : '' }}>ยกเลิกรายการรับเข้า (Void Intake)</option>
                    <option value="admin_reopen_checkout" {{ request('action') === 'admin_reopen_checkout' ? 'selected' : '' }}>Admin Reopen (กู้คืนการส่งออก)</option>
                    <option value="admin_step_rollback" {{ request('action') === 'admin_step_rollback' ? 'selected' : '' }}>Admin Rollback (ย้อนขั้นตอน)</option>
                    <option value="batch_checkout" {{ request('action') === 'batch_checkout' ? 'selected' : '' }}>Batch Checkout (ส่งออกหนังสือทั้งชุด)</option>
                    <option value="batch_advance" {{ request('action') === 'batch_advance' ? 'selected' : '' }}>Batch Advance (ส่งต่อทั้งชุด)</option>
                </select>
                @if(request('action'))
                <a href="{{ route('admin.logs.index') }}" class="text-slate-500 hover:underline">ล้าง</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-100 text-slate-700 uppercase font-bold text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-4 w-12 text-center">#</th>
                        <th class="py-2.5 px-4">วันเวลา (พ.ศ.)</th>
                        <th class="py-2.5 px-4">ผู้ทำรายการ</th>
                        <th class="py-2.5 px-4">Action</th>
                        <th class="py-2.5 px-4">รายละเอียด & เหตุผลการกู้คืน</th>
                        <th class="py-2.5 px-4">IP Address / เครื่อง</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/60 transition {{ str_contains($log->action, 'admin_') ? 'bg-purple-50/30' : '' }}">
                        <td class="py-2.5 px-4 text-center text-slate-400 font-mono">{{ $log->id }}</td>
                        <td class="py-2.5 px-4 whitespace-nowrap text-slate-600 font-mono">
                            {{ $log->created_at_thai }}
                        </td>
                        <td class="py-2.5 px-4 font-semibold text-slate-800">
                            {{ $log->user?->name ?? 'ระบบ' }}
                        </td>
                        <td class="py-2.5 px-4 whitespace-nowrap">
                            @if(str_contains($log->action, 'admin_'))
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                    {{ $log->action }}
                                </span>
                            @elseif($log->action === 'doc_number_edit')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                    ✏️ {{ $log->action }}
                                </span>
                            @elseif($log->action === 'intake_void')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-900 border border-red-300">
                                    🗑️ {{ $log->action }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                    {{ $log->action }}
                                </span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-slate-800 font-medium">
                            {{ $log->description }}
                        </td>
                        <td class="py-2.5 px-4 whitespace-nowrap text-slate-500 font-mono text-[11px]">
                            {{ $log->ip_address ?: '-' }}
                            @if($log->device)
                                <span class="text-slate-400">({{ $log->device->device_name ?: substr($log->device->device_token, 0, 8) }})</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">ยังไม่มีบันทึกประวัติการกระทำ</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="p-2 border-t border-slate-200 bg-slate-50">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

    <!-- Registered Devices Table -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-200">
            <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
                <span>💻</span>
                <span>ทะเบียนเครื่องและอุปกรณ์ที่เข้าใช้งาน (Registered Devices)</span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-100 text-slate-700 uppercase font-bold text-[11px] border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-4 w-12 text-center">#</th>
                        <th class="py-2.5 px-4">Device Token (UUID)</th>
                        <th class="py-2.5 px-4">ชื่อเครื่อง / โต๊ะทำงาน</th>
                        <th class="py-2.5 px-4">IP ล่าสุด</th>
                        <th class="py-2.5 px-4">ใช้งานล่าสุดเมื่อ (พ.ศ.)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($devices as $dev)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-2.5 px-4 text-center text-slate-400 font-mono">{{ $dev->id }}</td>
                        <td class="py-2.5 px-4 font-mono text-slate-800 font-bold">
                            {{ $dev->device_token }}
                        </td>
                        <td class="py-2.5 px-4 font-medium text-slate-700">
                            {{ $dev->device_name ?: 'เครื่องทั่วไป' }}
                        </td>
                        <td class="py-2.5 px-4 font-mono text-slate-600">
                            {{ $dev->last_ip_address ?: '-' }}
                        </td>
                        <td class="py-2.5 px-4 text-slate-600 font-mono">
                            {{ $dev->last_seen_at_thai }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-400">ยังไม่มีข้อมูลอุปกรณ์ที่บันทึกไว้</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

