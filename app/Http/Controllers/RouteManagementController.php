<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteManagementController extends Controller
{
    /**
     * Display a listing of routes.
     */
    public function index(): View
    {
        $routes = Route::withCount('movements')->orderBy('id', 'asc')->get();

        return view('admin.routes.index', [
            'routes' => $routes,
        ]);
    }

    /**
     * Store a newly created route.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'route_name' => ['required', 'string', 'max:255', 'unique:routes,route_name'],
            'description' => ['nullable', 'string', 'max:255'],
            'steps_input' => ['nullable', 'string'],
        ], [
            'route_name.required' => 'กรุณากรอกชื่อเส้นทาง',
            'route_name.unique' => 'ชื่อเส้นทางนี้มีอยู่ในระบบแล้ว',
        ]);

        $steps = [];
        if (! empty($validated['steps_input'])) {
            $rawSteps = preg_split('/[\r\n,]+/', $validated['steps_input']);
            $steps = array_values(array_filter(array_map('trim', $rawSteps)));
        }

        Route::create([
            'route_name' => $validated['route_name'],
            'description' => $validated['description'],
            'steps_json' => $steps,
            'is_active' => true,
        ]);

        return redirect()->route('admin.routes.index')->with('success', 'เพิ่มเส้นทางใหม่เรียบร้อยแล้ว');
    }

    /**
     * Update the specified route.
     */
    public function update(Request $request, Route $route): RedirectResponse
    {
        $validated = $request->validate([
            'route_name' => ['required', 'string', 'max:255', 'unique:routes,route_name,'.$route->id],
            'description' => ['nullable', 'string', 'max:255'],
            'steps_input' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $steps = [];
        if (! empty($validated['steps_input'])) {
            $rawSteps = preg_split('/[\r\n,]+/', $validated['steps_input']);
            $steps = array_values(array_filter(array_map('trim', $rawSteps)));
        }

        $route->update([
            'route_name' => $validated['route_name'],
            'description' => $validated['description'],
            'steps_json' => $steps,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.routes.index')->with('success', 'แก้ไขข้อมูลเส้นทางเรียบร้อยแล้ว');
    }

    /**
     * Toggle route status.
     */
    public function toggle(Route $route): RedirectResponse
    {
        $route->update(['is_active' => ! $route->is_active]);

        $status = $route->is_active ? 'เปิดใช้งาน' : 'ปิดการใช้งาน';

        return back()->with('success', "เปลี่ยนสถานะเส้นทาง {$route->route_name} เป็น {$status} เรียบร้อยแล้ว");
    }
}
