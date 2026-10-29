@extends('layouts.app', ['title' => 'จัดการเส้นทางเดินเอกสาร (Routes)'])

@section('content')
<div class="max-w-6xl mx-auto py-4 space-y-4" x-data="{
    createModal: false,
    editModal: false,
    editRoute: { id: null, route_name: '', description: '', steps_input: '', is_active: true },
    openEdit(rt) {
        this.editRoute = {
            id: rt.id,
            route_name: rt.route_name,
            description: rt.description || '',
            steps_input: (rt.steps_json || []).join('\n'),
            is_active: rt.is_active
        };
        this.editModal = true;
    }
}">
    <!-- Header -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
        <div>
            <h1 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>🛣️</span>
                <span>จัดการเส้นทางเดินเอกสาร (Route Management)</span>
            </h1>
            <p class="text-xs text-slate-500">กำหนดเส้นทาง จุดรับส่ง และขั้นตอนย่อยสำหรับกระบวนการเดินหนังสือ</p>
        </div>
        <button type="button" @click="createModal = true"
            class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1">
            <span>+ เพิ่มเส้นทางใหม่</span>
        </button>
    </div>

    <!-- Routes Table -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <table class="w-full text-left border-collapse text-xs">
            <thead class="bg-slate-100 text-slate-700 uppercase font-bold text-[11px] border-b border-slate-200">
                <tr>
                    <th class="py-2.5 px-4 w-12 text-center">#</th>
                    <th class="py-2.5 px-4">ชื่อเส้นทาง (Route Name)</th>
                    <th class="py-2.5 px-4">ขั้นตอนย่อย (Pipeline Steps)</th>
                    <th class="py-2.5 px-4">คำอธิบาย</th>
                    <th class="py-2.5 px-4 text-center">ใช้งาน (ครั้ง)</th>
                    <th class="py-2.5 px-4 text-center">สถานะ</th>
                    <th class="py-2.5 px-4 text-right">การจัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @foreach($routes as $rt)
                <tr class="hover:bg-slate-50/60 transition">
                    <td class="py-3 px-4 text-center text-slate-400 font-mono">{{ $rt->id }}</td>
                    <td class="py-3 px-4 font-bold text-slate-900">
                        {{ $rt->route_name }}
                    </td>
                    <td class="py-3 px-4">
                        @if($rt->isPipeline())
                            <div class="flex flex-wrap items-center gap-1">
                                @foreach($rt->steps_json as $idx => $st)
                                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[11px]">
                                        {{ $idx + 1 }}. {{ $st }}
                                    </span>
                                    @if(!$loop->last)
                                        <span class="text-slate-400">&rarr;</span>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <span class="text-slate-500 italic">จุดเดียว (Point-to-Point) ส่งออกหนังสือจบรอบทันที</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-slate-500">
                        {{ $rt->description ?: '-' }}
                    </td>
                    <td class="py-3 px-4 text-center font-bold text-slate-700">
                        {{ number_format($rt->movements_count) }}
                    </td>
                    <td class="py-3 px-4 text-center">
                        @if($rt->is_active)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">เปิดใช้งาน</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-600">ปิดใช้งาน</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-right">
                        <div class="flex items-center justify-end space-x-1.5">
                            <button type="button" @click="openEdit({{ $rt }})"
                                class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-[11px]">
                                แก้ไข
                            </button>
                            <form method="POST" action="{{ route('admin.routes.toggle', $rt) }}" class="inline">
                                @csrf
                                <button type="submit"
                                    class="px-2 py-1 rounded {{ $rt->is_active ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' }} font-medium text-[11px]">
                                    {{ $rt->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Create Route Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/60" @click="createModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 z-10">
                <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <span>🛣️</span>
                    <span>เพิ่มเส้นทางเดินเอกสารใหม่</span>
                </h3>
                <form method="POST" action="{{ route('admin.routes.store') }}">
                    @csrf
                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">ชื่อเส้นทาง <span class="text-red-500">*</span></label>
                            <input type="text" name="route_name" required placeholder="เช่น ลงเลขรับ-หอบ. ลงนาม-สารบรรณ-เสร็จสิ้น"
                                class="w-full px-3 py-2 border rounded-md text-xs focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">คำอธิบาย</label>
                            <input type="text" name="description" placeholder="รายละเอียดหรือวัตถุประสงค์ของเส้นทาง"
                                class="w-full px-3 py-2 border rounded-md text-xs focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                ขั้นตอนย่อยในเส้นทาง (1 บรรทัด หรือคั่นด้วยเครื่องหมายจุลภาค <code>,</code> ต่อ 1 ขั้นตอน)
                            </label>
                            <textarea name="steps_input" rows="4" placeholder="บันทึกรับหนังสือเข้า&#10;รอลงนาม&#10;สารบรรณกลาง&#10;นำจ่ายหนังสือ"
                                class="w-full px-3 py-2 border rounded-md text-xs font-mono focus:border-blue-500"></textarea>
                            <span class="text-[11px] text-blue-700 font-bold block mt-0.5">* ขั้นตอนที่ 1 "บันทึกรับหนังสือเข้า" จะถูกกำหนดเป็นขั้นตอนแรกของทุกเส้นทางโดยอัตโนมัติ</span>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end space-x-2">
                        <button type="button" @click="createModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-md text-xs">ยกเลิก</button>
                        <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white font-bold rounded-md text-xs hover:bg-blue-700">บันทึกเส้นทาง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Route Modal -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/60" @click="editModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 z-10" x-show="editModal">
                <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <span>✏️</span>
                    <span>แก้ไขเส้นทางเดินเอกสาร</span>
                </h3>
                <form :action="'{{ url('/admin/routes') }}/' + editRoute.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">ชื่อเส้นทาง <span class="text-red-500">*</span></label>
                            <input type="text" name="route_name" x-model="editRoute.route_name" required
                                class="w-full px-3 py-2 border rounded-md text-xs focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">คำอธิบาย</label>
                            <input type="text" name="description" x-model="editRoute.description"
                                class="w-full px-3 py-2 border rounded-md text-xs focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">ขั้นตอนย่อย</label>
                            <textarea name="steps_input" rows="4" x-model="editRoute.steps_input"
                                class="w-full px-3 py-2 border rounded-md text-xs font-mono focus:border-blue-500"></textarea>
                        </div>
                        <div>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" :checked="editRoute.is_active" class="rounded text-blue-600">
                                <span class="font-bold text-slate-700">เปิดใช้งานเส้นทางนี้</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end space-x-2">
                        <button type="button" @click="editModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-md text-xs">ยกเลิก</button>
                        <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white font-bold rounded-md text-xs hover:bg-blue-700">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

