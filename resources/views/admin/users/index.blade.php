@extends('layouts.app', ['title' => 'จัดการผู้ใช้งาน'])

@section('content')
<div class="max-w-5xl mx-auto py-4 space-y-4" x-data="{
    createModal: false,
    editModal: false,
    resetModal: false,
    targetUser: { id: null, username: '', name: '' },
    editUser: { id: null, username: '', name: '', role: 'staff', is_active: true },
    openReset(user) {
        this.targetUser = user;
        this.resetModal = true;
    },
    openEdit(user) {
        this.editUser = {
            id: user.id,
            username: user.username,
            name: user.name,
            role: user.role,
            is_active: Boolean(user.is_active)
        };
        this.editModal = true;
    }
}">
    <!-- Header -->
    <div class="bg-white p-5 rounded-2xl border-2 border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-black text-slate-900 flex items-center space-x-2">
                <span>👥</span>
                <span>จัดการผู้ใช้งานระบบ (User Management)</span>
            </h1>
            <p class="text-sm font-semibold text-slate-500 mt-0.5">จัดการชื่อผู้ใช้งาน (Username), ชื่อนามสกุล, บทบาท และรีเซ็ตรหัสผ่าน</p>
        </div>
        <button type="button" @click="createModal = true"
            class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-extrabold shadow-sm transition flex items-center space-x-1.5 cursor-pointer shrink-0">
            <span>+ เพิ่มผู้ใช้งานใหม่</span>
        </button>
    </div>

    <!-- Users Table (Senior-Friendly Large Text & Buttons) -->
    <div class="bg-white border-2 border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse text-sm">
            <thead class="bg-slate-100 text-slate-800 uppercase font-black text-xs md:text-sm border-b-2 border-slate-200">
                <tr>
                    <th class="py-3.5 px-4 w-12 text-center">#</th>
                    <th class="py-3.5 px-4">ชื่อผู้ใช้งาน (Username)</th>
                    <th class="py-3.5 px-4">ชื่อ-นามสกุล / ตำแหน่ง</th>
                    <th class="py-3.5 px-4 text-center">บทบาท (Role)</th>
                    <th class="py-3.5 px-4 text-center">สถานะ</th>
                    <th class="py-3.5 px-4 text-right">การจัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-slate-800">
                @foreach($users as $u)
                <tr class="hover:bg-blue-50/60 transition {{ $loop->even ? 'bg-slate-50/50' : 'bg-white' }}">
                    <td class="py-3.5 px-4 text-center text-slate-400 font-mono font-bold">{{ $u->id }}</td>
                    <td class="py-3.5 px-4 font-mono font-black text-blue-900 text-base">
                        {{ $u->username }}
                    </td>
                    <td class="py-3.5 px-4 font-bold text-slate-900 text-sm md:text-base">
                        {{ $u->name }}
                    </td>
                    <td class="py-3.5 px-4 text-center">
                        <span class="px-2.5 py-1 rounded-full text-xs font-black {{ $u->isAdmin() ? 'bg-purple-100 text-purple-900 border border-purple-300' : 'bg-slate-200 text-slate-800' }}">
                            {{ $u->isAdmin() ? '★ ผู้ดูแลระบบ (Admin)' : 'เจ้าหน้าที่ (Staff)' }}
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-center">
                        @if($u->is_active)
                            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300">✓ เปิดใช้งาน</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-red-100 text-red-900 border border-red-300">✕ ระงับการใช้งาน</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end space-x-2">
                            <!-- Edit User Button (Change username, name, role) -->
                            <button type="button" @click="openEdit({{ $u }})"
                                class="px-3 py-1.5 rounded-lg bg-blue-100 hover:bg-blue-200 text-blue-950 font-black text-xs md:text-sm border border-blue-300 transition cursor-pointer"
                                title="แก้ไขชื่อผู้ใช้งาน และชื่อ-นามสกุล">
                                ✏️ แก้ไข
                            </button>

                            <!-- Direct Reset Password Button -->
                            <button type="button" @click="openReset({{ $u }})"
                                class="px-3 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-950 font-black text-xs md:text-sm border border-amber-300 transition cursor-pointer"
                                title="กำหนดรหัสผ่านใหม่">
                                🔑 รีเซ็ตรหัส
                            </button>

                            @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="inline">
                                @csrf
                                <button type="submit"
                                    class="px-2.5 py-1.5 rounded-lg {{ $u->is_active ? 'bg-slate-200 text-slate-700 hover:bg-slate-300' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' }} font-bold text-xs cursor-pointer transition">
                                    {{ $u->is_active ? 'ระงับ' : 'เปิด' }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Edit User Modal (Change Username, Full Name, Role, Status) -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div x-show="editModal" x-transition.opacity class="fixed inset-0 bg-slate-950/70 transition-opacity" @click="editModal = false"></div>

        <!-- Centering Wrapper -->
        <div class="fixed inset-0 z-10 overflow-y-auto flex min-h-full items-center justify-center p-4 text-center sm:p-0 pointer-events-none">
            <!-- Modal Card -->
            <div x-show="editModal" x-transition.scale.origin-center
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border-2 border-blue-400 pointer-events-auto z-20">
                <form :action="'{{ url('/admin/users') }}/' + editUser.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-gradient-to-r from-blue-900 to-indigo-900 px-6 py-4 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl">✏️</span>
                            <h3 class="text-lg font-black tracking-tight">แก้ไขข้อมูลผู้ใช้งานระบบ</h3>
                        </div>
                        <button type="button" @click="editModal = false" class="text-blue-200 hover:text-white text-xl font-bold cursor-pointer">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Username -->
                        <div>
                            <label for="edit_user_username" class="block font-black text-sm text-slate-800 mb-1">
                                ชื่อผู้ใช้งาน (Username) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="edit_user_username" name="username" required x-model="editUser.username"
                                class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-300 text-base font-mono font-black focus:border-blue-600 focus:ring-0 text-slate-900 bg-white"
                                placeholder="เช่น admin, somchai">
                            <span class="text-xs text-slate-500 mt-1 block font-medium">ใช้สำหรับล็อกอินเข้าสู่ระบบ (ภาษาอังกฤษหรือตัวเลข)</span>
                        </div>

                        <!-- Full Name -->
                        <div>
                            <label for="edit_user_name" class="block font-black text-sm text-slate-800 mb-1">
                                ชื่อ-นามสกุล / ตำแหน่ง <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="edit_user_name" name="name" required x-model="editUser.name"
                                class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-300 text-base font-bold focus:border-blue-600 focus:ring-0 text-slate-900 bg-white"
                                placeholder="เช่น นายสมหมาย ใจดี (งานสารบรรณ)">
                            <span class="text-xs text-slate-500 mt-1 block font-medium">ชื่อที่จะแสดงในประวัติรับ-ส่งหนังสือและสมุดคุม</span>
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="edit_user_role" class="block font-black text-sm text-slate-800 mb-1">
                                บทบาทสิทธิ์การใช้งาน (Role) <span class="text-red-500">*</span>
                            </label>
                            <select id="edit_user_role" name="role" required x-model="editUser.role"
                                class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-0 text-slate-900 bg-white">
                                <option value="staff">เจ้าหน้าที่ทั่วไป (Staff)</option>
                                <option value="admin">ผู้ดูแลระบบ (Admin - จัดการระบบและกู้คืนสถานะ)</option>
                            </select>
                        </div>

                        <!-- Is Active -->
                        <div class="pt-2">
                            <label class="inline-flex items-center space-x-2.5 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" x-model="editUser.is_active"
                                    class="w-5 h-5 rounded border-2 border-slate-400 text-blue-600 focus:ring-0 cursor-pointer">
                                <span class="font-bold text-sm text-slate-800">เปิดใช้งานบัญชีนี้ (Active)</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-3">
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold rounded-xl shadow-md transition cursor-pointer text-sm">
                            ✓ บันทึกการแก้ไข
                        </button>
                        <button type="button" @click="editModal = false"
                            class="mt-3 sm:mt-0 w-full sm:w-auto px-5 py-3 border-2 border-slate-300 bg-white text-slate-700 font-bold rounded-xl hover:bg-slate-100 transition cursor-pointer text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div x-show="createModal" x-transition.opacity class="fixed inset-0 bg-slate-950/70 transition-opacity" @click="createModal = false"></div>

        <!-- Centering Wrapper -->
        <div class="fixed inset-0 z-10 overflow-y-auto flex min-h-full items-center justify-center p-4 text-center sm:p-0 pointer-events-none">
            <!-- Modal Card -->
            <div x-show="createModal" x-transition.scale.origin-center
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border-2 border-blue-400 pointer-events-auto z-20">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="bg-gradient-to-r from-blue-900 to-indigo-900 px-6 py-4 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl">👤</span>
                            <h3 class="text-lg font-black tracking-tight">เพิ่มผู้ใช้งานใหม่</h3>
                        </div>
                        <button type="button" @click="createModal = false" class="text-blue-200 hover:text-white text-xl font-bold cursor-pointer">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block font-black text-sm text-slate-800 mb-1">
                                ชื่อผู้ใช้งาน (Username) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="username" required placeholder="เช่น staff01"
                                class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-300 text-base font-mono font-black focus:border-blue-600 focus:ring-0 text-slate-900 bg-white">
                        </div>
                        <div>
                            <label class="block font-black text-sm text-slate-800 mb-1">
                                ชื่อ-นามสกุล / ตำแหน่ง <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" required placeholder="เช่น นายสมหมาย ใจดี (ธุรการ)"
                                class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-300 text-base font-bold focus:border-blue-600 focus:ring-0 text-slate-900 bg-white">
                        </div>
                        <div>
                            <label class="block font-black text-sm text-slate-800 mb-1">
                                รหัสผ่านเริ่มต้น <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="password" required minlength="6" placeholder="อย่างน้อย 6 ตัวอักษร"
                                class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-300 text-base focus:border-blue-600 focus:ring-0 text-slate-900 bg-white">
                        </div>
                        <div>
                            <label class="block font-black text-sm text-slate-800 mb-1">
                                บทบาท (Role) <span class="text-red-500">*</span>
                            </label>
                            <select name="role" required class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-0 text-slate-900 bg-white">
                                <option value="staff">เจ้าหน้าที่ทั่วไป (Staff)</option>
                                <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-3">
                        <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold rounded-xl shadow-md transition cursor-pointer text-sm">
                            ✓ สร้างบัญชีผู้ใช้
                        </button>
                        <button type="button" @click="createModal = false" class="mt-3 sm:mt-0 w-full sm:w-auto px-5 py-3 border-2 border-slate-300 bg-white text-slate-700 font-bold rounded-xl hover:bg-slate-100 transition cursor-pointer text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin Direct Reset Password Modal -->
    <div x-show="resetModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div x-show="resetModal" x-transition.opacity class="fixed inset-0 bg-slate-950/70 transition-opacity" @click="resetModal = false"></div>

        <!-- Centering Wrapper -->
        <div class="fixed inset-0 z-10 overflow-y-auto flex min-h-full items-center justify-center p-4 text-center sm:p-0 pointer-events-none">
            <!-- Modal Card -->
            <div x-show="resetModal" x-transition.scale.origin-center
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border-2 border-amber-400 pointer-events-auto z-20">
                <form :action="'{{ url('/admin/users') }}/' + targetUser.id + '/reset-password'" method="POST">
                    @csrf
                    <div class="bg-gradient-to-r from-amber-800 to-amber-900 px-6 py-4 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl">🔑</span>
                            <h3 class="text-lg font-black tracking-tight">รีเซ็ตรหัสผ่านโดยตรง</h3>
                        </div>
                        <button type="button" @click="resetModal = false" class="text-amber-200 hover:text-white text-xl font-bold cursor-pointer">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="p-3.5 bg-amber-50 border-2 border-amber-200 rounded-xl text-sm text-amber-950">
                            กำหนดรหัสผ่านใหม่ให้กับ: <strong class="text-slate-950 font-black text-base" x-text="targetUser.name + ' (' + targetUser.username + ')'"></strong>
                        </div>
                        <div>
                            <label class="block font-black text-sm text-slate-800 mb-1">
                                รหัสผ่านใหม่ <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="new_password" required minlength="6" placeholder="กำหนดรหัสผ่านใหม่ (อย่างน้อย 6 ตัวอักษร)"
                                class="w-full px-3.5 py-2.5 rounded-xl border-2 border-slate-300 text-base focus:border-amber-600 focus:ring-0 text-slate-900 bg-white">
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-200 gap-3">
                        <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-extrabold rounded-xl shadow-md transition cursor-pointer text-sm">
                            ✓ รีเซ็ตรหัสผ่านทันที
                        </button>
                        <button type="button" @click="resetModal = false" class="mt-3 sm:mt-0 w-full sm:w-auto px-5 py-3 border-2 border-slate-300 bg-white text-slate-700 font-bold rounded-xl hover:bg-slate-100 transition cursor-pointer text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

