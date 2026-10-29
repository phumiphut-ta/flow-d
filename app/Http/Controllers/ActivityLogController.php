<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\RegisteredDevice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display activity logs and registered devices.
     */
    public function index(Request $request): View
    {
        $query = ActivityLog::with(['user', 'device']);

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        $logs = $query->orderBy('id', 'desc')->paginate(30)->withQueryString();
        $devices = RegisteredDevice::orderBy('last_seen_at', 'desc')->paginate(20, ['*'], 'devices_page');

        return view('admin.logs.index', [
            'logs' => $logs,
            'devices' => $devices,
        ]);
    }
}
