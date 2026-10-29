<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Display listing of users.
     */
    public function index(): View
    {
        $users = User::orderBy('id', 'asc')->get();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    /**
     * Store new user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'name' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,staff'],
        ], [
            'username.required' => 'กรุณากรอกชื่อผู้ใช้งาน',
            'username.unique' => 'ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว',
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล / ตำแหน่ง',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร',
        ]);

        User::create([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'เพิ่มผู้ใช้งานใหม่เรียบร้อยแล้ว');
    }

    /**
     * Update user (username, name, role, is_active).
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', 'in:admin,staff'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'username.required' => 'กรุณากรอกชื่อผู้ใช้งาน (Username)',
            'username.unique' => 'ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว กรุณาใช้ชื่ออื่น',
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล / ตำแหน่ง',
            'role.required' => 'กรุณาเลือกบทบาท',
        ]);

        $user->update([
            'username' => trim($validated['username']),
            'name' => trim($validated['name']),
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.users.index')->with('success', "แก้ไขข้อมูลผู้ใช้งาน '{$user->username}' เรียบร้อยแล้ว");
    }

    /**
     * Admin directly resets password for a user.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ], [
            'new_password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'new_password.min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร',
        ]);

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('success', "รีเซ็ตรหัสผ่านสำหรับ {$user->username} เรียบร้อยแล้ว");
    }

    /**
     * Toggle active status.
     */
    public function toggle(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'ไม่สามารถปิดการใช้งานบัญชีที่กำลังล็อกอินอยู่ได้');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'เปิดใช้งาน' : 'ระงับการใช้งาน';

        return back()->with('success', "เปลี่ยนสถานะผู้ใช้งาน {$user->username} เป็น {$status} เรียบร้อยแล้ว");
    }
}
