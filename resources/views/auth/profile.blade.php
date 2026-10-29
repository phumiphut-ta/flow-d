@extends('layouts.app', ['title' => 'ข้อมูลส่วนตัว & เปลี่ยนรหัสผ่าน'])

@section('content')
<div class="max-w-2xl mx-auto py-6" x-data="{ submitting: false }">
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
            <h2 class="text-base font-bold flex items-center space-x-2">
                <span>👤</span>
                <span>ข้อมูลผู้ใช้งาน & เปลี่ยนรหัสผ่าน</span>
            </h2>
            <span class="text-xs px-2.5 py-0.5 rounded-full font-bold {{ $user->isAdmin() ? 'bg-purple-600 text-white' : 'bg-slate-700 text-slate-200' }}">
                {{ $user->isAdmin() ? 'ผู้ดูแลระบบ (Admin)' : 'เจ้าหน้าที่ (Staff)' }}
            </span>
        </div>

        <div class="p-6 space-y-6">
            <!-- User Information Display -->
            <div class="grid grid-cols-2 gap-4 pb-6 border-b border-slate-200 text-sm">
                <div>
                    <span class="text-xs text-slate-500 block">ชื่อผู้ใช้งาน (Username)</span>
                    <strong class="text-slate-800 text-base font-mono">{{ $user->username }}</strong>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">ชื่อ-นามสกุล / ตำแหน่ง</span>
                    <strong class="text-slate-800 text-base">{{ $user->name }}</strong>
                </div>
            </div>

            <!-- Password Change Form -->
            <form method="POST" action="{{ route('profile.password') }}" @submit="if(submitting) { $event.preventDefault(); return false; } submitting = true;">
                @csrf
                <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center space-x-1.5">
                    <span>🔑</span>
                    <span>เปลี่ยนรหัสผ่านใหม่ (Self-Service)</span>
                </h3>

                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-xs font-semibold text-slate-700 mb-1">
                            รหัสผ่านปัจจุบัน <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="current_password" name="current_password" required
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">
                            รหัสผ่านใหม่ <span class="text-red-500">* (อย่างน้อย 6 ตัวอักษร)</span>
                        </label>
                        <input type="password" id="password" name="password" required minlength="6"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">
                            ยืนยันรหัสผ่านใหม่อีกครั้ง <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="pt-2">
                        <button type="submit" :disabled="submitting"
                            class="w-full py-2.5 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition shadow-sm flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!submitting">บันทึกรหัสผ่านใหม่</span>
                            <span x-show="submitting" x-cloak class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                ⏳ กำลังบันทึก...
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

